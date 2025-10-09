<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

// Get current user profile
$app->get('/api/v1/users/me', function (Request $request, Response $response) {
    $container = $this->get('container');
    $db = $container->get('db');
    $jwt = $container->get('jwt');

    try {
        $userId = $jwt['sub'];

        $stmt = $db->prepare("
            SELECT id, username, email, full_name, role, created_at, last_login
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $data = [
                'success' => false,
                'error' => ['code' => 'USER_NOT_FOUND', 'message' => 'User not found']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = ['success' => true, 'data' => $user];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $data = [
            'success' => false,
            'error' => ['code' => 'SERVER_ERROR', 'message' => $e->getMessage()]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Change own password
$app->put('/api/v1/users/me/password', function (Request $request, Response $response) {
    $container = $this->get('container');
    $db = $container->get('db');
    $jwt = $container->get('jwt');
    $body = $request->getParsedBody();

    try {
        $userId = $jwt['sub'];
        $currentPassword = $body['current_password'] ?? null;
        $newPassword = $body['new_password'] ?? null;

        if (!$currentPassword || !$newPassword) {
            $data = [
                'success' => false,
                'error' => ['code' => 'MISSING_FIELDS', 'message' => 'Current and new password required']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (strlen($newPassword) < 8) {
            $data = [
                'success' => false,
                'error' => ['code' => 'WEAK_PASSWORD', 'message' => 'Password must be at least 8 characters']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $data = [
                'success' => false,
                'error' => ['code' => 'INVALID_PASSWORD', 'message' => 'Current password is incorrect']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newHash, $userId]);

        $data = [
            'success' => true,
            'data' => ['message' => 'Password updated successfully']
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $data = [
            'success' => false,
            'error' => ['code' => 'SERVER_ERROR', 'message' => $e->getMessage()]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// List all users (admin only)
$app->get('/api/v1/users', function (Request $request, Response $response) {
    $container = $this->get('container');
    $db = $container->get('db');
    $jwt = $container->get('jwt');

    try {
        // Check if user is admin
        if ($jwt['role'] !== 'admin') {
            $data = [
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Admin access required']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->query("
            SELECT id, username, email, full_name, role, created_at, last_login, is_active
            FROM users
            ORDER BY created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = ['success' => true, 'data' => $users];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $data = [
            'success' => false,
            'error' => ['code' => 'SERVER_ERROR', 'message' => $e->getMessage()]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Create new user (admin only)
$app->post('/api/v1/users', function (Request $request, Response $response) {
    $container = $this->get('container');
    $db = $container->get('db');
    $jwt = $container->get('jwt');
    $body = $request->getParsedBody();

    try {
        // Check if user is admin
        if ($jwt['role'] !== 'admin') {
            $data = [
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Admin access required']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $username = $body['username'] ?? null;
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;
        $fullName = $body['full_name'] ?? null;
        $role = $body['role'] ?? 'viewer';

        if (!$username || !$email || !$password) {
            $data = [
                'success' => false,
                'error' => ['code' => 'MISSING_FIELDS', 'message' => 'Username, email and password required']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (strlen($password) < 8) {
            $data = [
                'success' => false,
                'error' => ['code' => 'WEAK_PASSWORD', 'message' => 'Password must be at least 8 characters']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Check if username or email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $data = [
                'success' => false,
                'error' => ['code' => 'USER_EXISTS', 'message' => 'Username or email already exists']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        // Create user
        $userId = Uuid::uuid4()->toString();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (id, username, email, password_hash, full_name, role, is_active)
            VALUES (?, ?, ?, ?, ?, ?, true)
        ");
        $stmt->execute([$userId, $username, $email, $passwordHash, $fullName, $role]);

        $data = [
            'success' => true,
            'data' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
                'role' => $role
            ]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $data = [
            'success' => false,
            'error' => ['code' => 'SERVER_ERROR', 'message' => $e->getMessage()]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Update user (admin only)
$app->put('/api/v1/users/{id}', function (Request $request, Response $response, array $args) {
    $container = $this->get('container');
    $db = $container->get('db');
    $jwt = $container->get('jwt');
    $body = $request->getParsedBody();
    $userId = $args['id'];

    try {
        // Check if user is admin
        if ($jwt['role'] !== 'admin') {
            $data = [
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Admin access required']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        // Build update query dynamically
        $updates = [];
        $params = [];

        if (isset($body['email'])) {
            $updates[] = "email = ?";
            $params[] = $body['email'];
        }
        if (isset($body['full_name'])) {
            $updates[] = "full_name = ?";
            $params[] = $body['full_name'];
        }
        if (isset($body['role'])) {
            $updates[] = "role = ?";
            $params[] = $body['role'];
        }
        if (isset($body['is_active'])) {
            $updates[] = "is_active = ?";
            $params[] = $body['is_active'];
        }
        if (isset($body['password'])) {
            if (strlen($body['password']) < 8) {
                $data = [
                    'success' => false,
                    'error' => ['code' => 'WEAK_PASSWORD', 'message' => 'Password must be at least 8 characters']
                ];
                $response->getBody()->write(json_encode($data));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            $updates[] = "password_hash = ?";
            $params[] = password_hash($body['password'], PASSWORD_DEFAULT);
        }

        if (empty($updates)) {
            $data = [
                'success' => false,
                'error' => ['code' => 'NO_UPDATES', 'message' => 'No fields to update']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $updates[] = "updated_at = NOW()";
        $params[] = $userId;

        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            $data = [
                'success' => false,
                'error' => ['code' => 'USER_NOT_FOUND', 'message' => 'User not found']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = [
            'success' => true,
            'data' => ['message' => 'User updated successfully']
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $data = [
            'success' => false,
            'error' => ['code' => 'SERVER_ERROR', 'message' => $e->getMessage()]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Delete user (admin only)
$app->delete('/api/v1/users/{id}', function (Request $request, Response $response, array $args) {
    $container = $this->get('container');
    $db = $container->get('db');
    $jwt = $container->get('jwt');
    $userId = $args['id'];

    try {
        // Check if user is admin
        if ($jwt['role'] !== 'admin') {
            $data = [
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Admin access required']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        // Prevent deleting yourself
        if ($userId === $jwt['sub']) {
            $data = [
                'success' => false,
                'error' => ['code' => 'CANNOT_DELETE_SELF', 'message' => 'Cannot delete your own account']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            $data = [
                'success' => false,
                'error' => ['code' => 'USER_NOT_FOUND', 'message' => 'User not found']
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = [
            'success' => true,
            'data' => ['message' => 'User deleted successfully']
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $data = [
            'success' => false,
            'error' => ['code' => 'SERVER_ERROR', 'message' => $e->getMessage()]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});
