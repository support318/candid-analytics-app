<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Admin user creation endpoint (temporary - remove after setup)
$app->post('/api/setup/create-admin', function (Request $request, Response $response) use ($container) {
    $db = $container->get('db');

    try {
        $db->execute(
            "INSERT INTO users (username, email, password_hash, role, status)
             VALUES (:username, :email, :password_hash, :role, :status)
             ON CONFLICT (username) DO NOTHING",
            [
                'username' => 'admin',
                'email' => 'admin@candidstudios.net',
                'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'role' => 'admin',
                'status' => 'active'
            ]
        );

        $data = [
            'success' => true,
            'data' => [
                'message' => 'Admin user created successfully',
                'username' => 'admin',
                'password' => 'password'
            ]
        ];
    } catch (\Exception $e) {
        $data = [
            'success' => false,
            'error' => [
                'code' => 'SETUP_ERROR',
                'message' => $e->getMessage()
            ]
        ];
    }

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
