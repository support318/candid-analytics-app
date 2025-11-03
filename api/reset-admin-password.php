#!/usr/bin/env php
<?php
/**
 * Reset Admin Password Script
 */

echo "=== Admin Password Reset ===\n\n";

// New password
$newPassword = 'Admin2025!';

// Generate hash
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "New password: $newPassword\n";
echo "Generated hash: $hash\n\n";

// Verify the hash works
if (password_verify($newPassword, $hash)) {
    echo "✓ Hash verification: SUCCESS\n\n";
} else {
    echo "✗ Hash verification: FAILED\n\n";
    exit(1);
}

// Get DATABASE_URL from environment
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    echo "ERROR: DATABASE_URL not found. Run with: railway run php reset-admin-password.php\n";
    exit(1);
}

try {
    // Connect to database
    $pdo = new PDO($databaseUrl, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✓ Connected to database\n";

    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);

    echo "✓ Admin password updated successfully!\n\n";
    echo "You can now log in with:\n";
    echo "  Username: admin\n";
    echo "  Password: $newPassword\n\n";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
