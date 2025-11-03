#!/usr/bin/env php
<?php
/**
 * Test Login Script - Debug Authentication
 */

echo "=== Testing Login Authentication ===\n\n";

// Get DATABASE_URL from environment
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    echo "ERROR: DATABASE_URL not found. Run with: railway run php test-login.php\n";
    exit(1);
}

try {
    // Connect to database
    $pdo = new PDO($databaseUrl, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ Connected to database\n\n";

    // Test credentials
    $testUsername = 'admin';
    $testPassword = 'Admin2025!';

    echo "Testing login for username: $testUsername\n";
    echo "Testing password: $testPassword\n\n";

    // Query user (exact same query as AuthController)
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, role, two_factor_enabled
        FROM users
        WHERE username = :username AND status = 'active'
    ");
    $stmt->execute(['username' => $testUsername]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "✗ User not found or not active\n";
        exit(1);
    }

    echo "✓ User found:\n";
    echo "  - ID: {$user['id']}\n";
    echo "  - Username: {$user['username']}\n";
    echo "  - Email: {$user['email']}\n";
    echo "  - Role: {$user['role']}\n";
    echo "  - 2FA Enabled: " . ($user['two_factor_enabled'] ? 'true' : 'false') . "\n";
    echo "  - Password Hash: {$user['password_hash']}\n\n";

    // Verify password (exact same logic as AuthController)
    $passwordVerifies = password_verify($testPassword, $user['password_hash']);

    if ($passwordVerifies) {
        echo "✓ Password verification: SUCCESS\n";
        echo "\nLogin should work! If it doesn't, the issue is elsewhere.\n";
    } else {
        echo "✗ Password verification: FAILED\n";
        echo "\nPassword does not match hash in database.\n";

        // Try verifying with a fresh hash
        echo "\nGenerating fresh hash for comparison...\n";
        $freshHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "Fresh hash: $freshHash\n";
        $freshVerifies = password_verify($testPassword, $freshHash);
        echo "Fresh hash verifies: " . ($freshVerifies ? 'YES' : 'NO') . "\n";
    }

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
