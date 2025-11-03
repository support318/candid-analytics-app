#!/usr/bin/env php
<?php
/**
 * Check Password Script
 */

echo "=== Checking Password Hash ===\n\n";

// Get DATABASE_URL from environment
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    echo "ERROR: DATABASE_URL not found\n";
    exit(1);
}

try {
    // Connect to database
    $pdo = new PDO($databaseUrl, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ Connected to database\n\n";

    // Query admin user
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, two_factor_enabled
        FROM users
        WHERE username = 'admin'
    ");
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo "✗ Admin user not found\n";
        exit(1);
    }

    echo "Admin user found:\n";
    echo "  - ID: {$user['id']}\n";
    echo "  - Username: {$user['username']}\n";
    echo "  - Email: {$user['email']}\n";
    echo "  - 2FA: " . ($user['two_factor_enabled'] ? 'enabled' : 'disabled') . "\n";
    echo "  - Password Hash: {$user['password_hash']}\n\n";

    // Test passwords
    $passwords = ['testpass123', 'Admin2025!'];

    foreach ($passwords as $password) {
        $verifies = password_verify($password, $user['password_hash']);
        echo "Password '$password': " . ($verifies ? "✓ MATCH" : "✗ NO MATCH") . "\n";
    }

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
