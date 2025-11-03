<?php
/**
 * TEMPORARY DEBUG ENDPOINT - DELETE AFTER USE
 * Checks password hash in database
 */

header('Content-Type: application/json');

// Get DATABASE_URL from environment
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    echo json_encode([
        'error' => 'DATABASE_URL not found'
    ]);
    exit(1);
}

try {
    // Connect to database
    $pdo = new PDO($databaseUrl, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Query admin user
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, two_factor_enabled
        FROM users
        WHERE username = 'admin'
    ");
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'error' => 'Admin user not found'
        ]);
        exit(1);
    }

    // Test passwords
    $passwords = [
        'testpass123',
        'Admin2025!'
    ];

    $results = [];
    foreach ($passwords as $password) {
        $verifies = password_verify($password, $user['password_hash']);
        $results[$password] = $verifies;
    }

    // Output results
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'two_factor_enabled' => $user['two_factor_enabled'],
            'password_hash' => $user['password_hash']
        ],
        'password_tests' => $results,
        'notes' => 'If both are false, password needs to be reset'
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    exit(1);
}
