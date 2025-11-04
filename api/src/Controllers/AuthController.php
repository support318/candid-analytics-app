<?php

declare(strict_types=1);

namespace CandidAnalytics\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Authentication Controller
 * Handles user login, logout, and token refresh
 */
class AuthController
{
    private $db;
    private $logger;

    public function __construct($container)
    {
        $this->db = $container->get('db');
        $this->logger = $container->get('logger');
    }

    /**
     * Login endpoint
     * POST /api/auth/login
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $twoFactorCode = $data['two_factor_code'] ?? null;

        // Validate input
        if (empty($username) || empty($password)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'Username and password are required'
                ]
            ], 400);
        }

        // Query user from database (include 2FA fields)
        $user = $this->db->queryOne(
            "SELECT id, username, email, password_hash, role, two_factor_enabled
             FROM users
             WHERE username = :username AND status = 'active'",
            ['username' => $username]
        );

        // DEBUG: Log user lookup result
        if (!$user) {
            $this->logger->error('Login failed - user not found', ['username' => $username]);
        } else {
            $hash_preview = substr($user['password_hash'], 0, 20);
            $hash_length = strlen($user['password_hash']);
            $verify_result = password_verify($password, $user['password_hash']);
            $this->logger->info('Login attempt details', [
                'username' => $username,
                'user_found' => true,
                'hash_preview' => $hash_preview,
                'hash_length' => $hash_length,
                'verify_result' => $verify_result
            ]);
        }

        // Verify user exists and password is correct
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->logger->warning('Failed login attempt', ['username' => $username]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid username or password'
                ]
            ], 401);
        }

        // Check if 2FA is enabled
        if ($user['two_factor_enabled']) {
            // If 2FA code not provided, request it
            if (empty($twoFactorCode)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => '2FA_REQUIRED',
                        'message' => 'Two-factor authentication code required'
                    ],
                    'data' => [
                        'two_factor_required' => true
                    ]
                ], 401);
            }

            // Verify 2FA code
            if (!TwoFactorController::verifyLoginCode($this->db, $user['id'], $twoFactorCode)) {
                $this->logger->warning('Failed 2FA verification', [
                    'username' => $username,
                    'user_id' => $user['id']
                ]);

                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_2FA_CODE',
                        'message' => 'Invalid two-factor authentication code'
                    ]
                ], 401);
            }

            $this->logger->info('2FA verification successful', ['user_id' => $user['id']]);
        }

        // Generate JWT tokens
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        // Store refresh token in database
        $this->db->execute(
            "INSERT INTO refresh_tokens (user_id, token, expires_at)
             VALUES (:user_id, :token, :expires_at)",
            [
                'user_id' => $user['id'],
                'token' => $refreshToken,
                'expires_at' => date('Y-m-d H:i:s', time() + intval($_ENV['JWT_REFRESH_EXPIRES_IN'] ?? 2592000))
            ]
        );

        $this->logger->info('User logged in', ['user_id' => $user['id']]);

        return $this->jsonResponse($response, [
            'success' => true,
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => intval($_ENV['JWT_EXPIRES_IN'] ?? 3600),
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]
        ]);
    }

    /**
     * Refresh token endpoint
     * POST /api/auth/refresh
     */
    public function refresh(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $refreshToken = $data['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'Refresh token is required'
                ]
            ], 400);
        }

        // Verify refresh token exists and is valid
        $tokenData = $this->db->queryOne(
            "SELECT rt.*, u.id as user_id, u.username, u.email, u.role
             FROM refresh_tokens rt
             JOIN users u ON rt.user_id = u.id
             WHERE rt.token = :token
             AND rt.expires_at > NOW()
             AND rt.revoked_at IS NULL",
            ['token' => $refreshToken]
        );

        if (!$tokenData) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Invalid or expired refresh token'
                ]
            ], 401);
        }

        // Generate new access token
        $user = [
            'id' => $tokenData['user_id'],
            'username' => $tokenData['username'],
            'email' => $tokenData['email'],
            'role' => $tokenData['role']
        ];

        $accessToken = $this->generateAccessToken($user);

        return $this->jsonResponse($response, [
            'success' => true,
            'data' => [
                'access_token' => $accessToken,
                'expires_in' => intval($_ENV['JWT_EXPIRES_IN'] ?? 3600)
            ]
        ]);
    }

    /**
     * Logout endpoint
     * POST /api/auth/logout
     */
    public function logout(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $refreshToken = $data['refresh_token'] ?? '';

        if (!empty($refreshToken)) {
            // Revoke refresh token
            $this->db->execute(
                "UPDATE refresh_tokens
                 SET revoked_at = NOW()
                 WHERE token = :token",
                ['token' => $refreshToken]
            );
        }

        return $this->jsonResponse($response, [
            'success' => true,
            'data' => [
                'message' => 'Successfully logged out'
            ]
        ]);
    }

    /**
     * Generate JWT access token
     */
    private function generateAccessToken(array $user): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + intval($_ENV['JWT_EXPIRES_IN'] ?? 3600);

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], $_ENV['JWT_ALGORITHM'] ?? 'HS256');
    }

    /**
     * Generate refresh token
     */
    private function generateRefreshToken(array $user): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * Helper: JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
