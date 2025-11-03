<?php

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = new PDO(getenv('DATABASE_URL'));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Applying user table migration...\n";

    // Add full_name column if it doesn't exist
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255)");
    echo "✓ Added full_name column\n";

    // Add is_active column if it doesn't exist
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE");
    echo "✓ Added is_active column\n";

    // Update existing users
    $db->exec("UPDATE users SET is_active = (status = 'active') WHERE is_active IS NULL");
    echo "✓ Updated is_active values\n";

    echo "\nMigration completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
