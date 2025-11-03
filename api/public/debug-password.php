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

    // Generate new hash and update
    $newPassword = 'testpass123';
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update using prepared statement to avoid escaping issues
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE username = 'admin'");
    $updateStmt->execute(['hash' => $newHash]);

    // Verify the update
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
    $stmt->execute();
    $updatedUser = $stmt->fetch();

    // Test the new password
    $verifies = password_verify($newPassword, $updatedUser['password_hash']);

    // Output results
    echo json_encode([
        'success' => true,
        'action' => 'Password reset performed',
        'new_hash' => $newHash,
        'stored_hash' => $updatedUser['password_hash'],
        'password_verification' => $verifies ? 'SUCCESS' : 'FAILED',
        'notes' => 'Password has been reset to: testpass123'
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    exit(1);
}
