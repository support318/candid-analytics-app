<?php

declare(strict_types=1);

namespace CandidAnalytics\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

/**
 * Two-Factor Authentication Controller
 * Handles 2FA setup, verification, and management
 */
class TwoFactorController
{
    private $db;
    private $logger;

    public function __construct($container)
    {
        $this->db = $container->get('db');
        $this->logger = $container->get('logger');
    }

    /**
     * Generate 2FA secret and QR code
     * POST /api/v1/users/me/2fa/setup
     */
    public function setup(Request $request, Response $response, $jwt): Response
    {
        $userId = $jwt['sub'];

        try {
            // Check if user already has 2FA enabled
            $user = $this->db->queryOne(
                "SELECT two_factor_enabled, two_factor_secret FROM users WHERE id = :id",
                ['id' => $userId]
            );

            if (!$user) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => 'USER_NOT_FOUND',
                        'message' => 'User not found'
                    ]
                ], 404);
            }

            if ($user['two_factor_enabled']) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => '2FA_ALREADY_ENABLED',
                        'message' => '2FA is already enabled. Disable it first to set up again.'
                    ]
                ], 400);
            }

            // Generate new TOTP secret
            $totp = TOTP::create();
            $totp->setLabel($jwt['email']);
            $totp->setIssuer($_ENV['APP_NAME'] ?? 'Candid Analytics');

            // Generate backup codes (8 codes, 8 characters each)
            $backupCodes = $this->generateBackupCodes(8);
            $hashedBackupCodes = array_map('password_hash', $backupCodes, array_fill(0, count($backupCodes), PASSWORD_DEFAULT));

            // Store secret and backup codes in database (not enabled yet)
            $this->db->execute(
                "UPDATE users
                 SET two_factor_secret = :secret,
                     two_factor_backup_codes = :backup_codes,
                     two_factor_enabled = FALSE
                 WHERE id = :id",
                [
                    'id' => $userId,
                    'secret' => $totp->getSecret(),
                    'backup_codes' => json_encode($hashedBackupCodes)
                ]
            );

            $this->logger->info('2FA setup initiated', ['user_id' => $userId]);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'secret' => $totp->getSecret(),
                    'qr_code_uri' => $totp->getQrCodeUri(
                        'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
                    ),
                    'manual_entry_key' => $totp->getSecret(),
                    'backup_codes' => $backupCodes // Only shown once!
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('2FA setup failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to set up 2FA'
                ]
            ], 500);
        }
    }

    /**
     * Verify 2FA code and enable 2FA
     * POST /api/v1/users/me/2fa/verify
     */
    public function verify(Request $request, Response $response, $jwt): Response
    {
        $userId = $jwt['sub'];
        $data = $request->getParsedBody();
        $code = $data['code'] ?? '';

        if (empty($code)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => '2FA code is required'
                ]
            ], 400);
        }

        try {
            // Get user's 2FA secret
            $user = $this->db->queryOne(
                "SELECT two_factor_secret, two_factor_enabled FROM users WHERE id = :id",
                ['id' => $userId]
            );

            if (!$user || empty($user['two_factor_secret'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => '2FA_NOT_SETUP',
                        'message' => '2FA has not been set up yet'
                    ]
                ], 400);
            }

            // Verify the code
            $totp = TOTP::create($user['two_factor_secret']);
            if (!$totp->verify($code, null, 1)) { // Allow 1 window (30s) of tolerance
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CODE',
                        'message' => 'Invalid 2FA code'
                    ]
                ], 401);
            }

            // Enable 2FA
            $this->db->execute(
                "UPDATE users
                 SET two_factor_enabled = TRUE,
                     two_factor_confirmed_at = NOW()
                 WHERE id = :id",
                ['id' => $userId]
            );

            $this->logger->info('2FA enabled successfully', ['user_id' => $userId]);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'message' => '2FA has been enabled successfully',
                    'enabled' => true
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('2FA verification failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to verify 2FA code'
                ]
            ], 500);
        }
    }

    /**
     * Disable 2FA
     * POST /api/v1/users/me/2fa/disable
     */
    public function disable(Request $request, Response $response, $jwt): Response
    {
        $userId = $jwt['sub'];
        $data = $request->getParsedBody();
        $password = $data['password'] ?? '';

        if (empty($password)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'Password is required to disable 2FA'
                ]
            ], 400);
        }

        try {
            // Verify password first
            $user = $this->db->queryOne(
                "SELECT password_hash, two_factor_enabled FROM users WHERE id = :id",
                ['id' => $userId]
            );

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PASSWORD',
                        'message' => 'Incorrect password'
                    ]
                ], 401);
            }

            if (!$user['two_factor_enabled']) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => '2FA_NOT_ENABLED',
                        'message' => '2FA is not currently enabled'
                    ]
                ], 400);
            }

            // Disable 2FA and clear secret/backup codes
            $this->db->execute(
                "UPDATE users
                 SET two_factor_enabled = FALSE,
                     two_factor_secret = NULL,
                     two_factor_backup_codes = NULL,
                     two_factor_confirmed_at = NULL
                 WHERE id = :id",
                ['id' => $userId]
            );

            $this->logger->warning('2FA disabled', ['user_id' => $userId]);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'message' => '2FA has been disabled',
                    'enabled' => false
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('2FA disable failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to disable 2FA'
                ]
            ], 500);
        }
    }

    /**
     * Get 2FA status
     * GET /api/v1/users/me/2fa/status
     */
    public function status(Request $request, Response $response, $jwt): Response
    {
        $userId = $jwt['sub'];

        try {
            $user = $this->db->queryOne(
                "SELECT two_factor_enabled, two_factor_confirmed_at FROM users WHERE id = :id",
                ['id' => $userId]
            );

            if (!$user) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => 'USER_NOT_FOUND',
                        'message' => 'User not found'
                    ]
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'enabled' => (bool)$user['two_factor_enabled'],
                    'confirmed_at' => $user['two_factor_confirmed_at']
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to get 2FA status'
                ]
            ], 500);
        }
    }

    /**
     * Verify 2FA code during login (called from AuthController)
     */
    public static function verifyLoginCode($db, $userId, $code): bool
    {
        try {
            $user = $db->queryOne(
                "SELECT two_factor_secret, two_factor_backup_codes FROM users WHERE id = :id",
                ['id' => $userId]
            );

            if (!$user || empty($user['two_factor_secret'])) {
                return false;
            }

            // Try TOTP code first
            $totp = TOTP::create($user['two_factor_secret']);
            if ($totp->verify($code, null, 1)) {
                return true;
            }

            // Try backup codes if TOTP failed
            if (!empty($user['two_factor_backup_codes'])) {
                $backupCodes = json_decode($user['two_factor_backup_codes'], true);
                foreach ($backupCodes as $index => $hashedCode) {
                    if (password_verify($code, $hashedCode)) {
                        // Remove used backup code
                        array_splice($backupCodes, $index, 1);
                        $db->execute(
                            "UPDATE users SET two_factor_backup_codes = :codes WHERE id = :id",
                            [
                                'codes' => json_encode($backupCodes),
                                'id' => $userId
                            ]
                        );
                        return true;
                    }
                }
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate random backup codes
     */
    private function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))); // 8 character hex codes
        }
        return $codes;
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
