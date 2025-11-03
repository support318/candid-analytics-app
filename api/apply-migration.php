#!/usr/bin/env php
<?php
/**
 * Emergency Migration Script - Apply 2FA Database Changes
 * Run this via: railway run php apply-migration.php
 */

echo "Starting 2FA database migration...\n\n";

try {
    // Get DATABASE_URL from environment
    $databaseUrl = getenv('DATABASE_URL');

    if (!$databaseUrl) {
        die("ERROR: DATABASE_URL environment variable not found!\n");
    }

    echo "✓ Found DATABASE_URL\n";

    // Create PDO connection
    $pdo = new PDO($databaseUrl, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ Connected to database\n\n";

    // Read migration SQL
    $migrationFile = __DIR__ . '/database/migrations/add_2fa_support.sql';

    if (!file_exists($migrationFile)) {
        die("ERROR: Migration file not found at: $migrationFile\n");
    }

    $sql = file_get_contents($migrationFile);
    echo "✓ Read migration file (" . strlen($sql) . " bytes)\n\n";

    echo "Executing migration...\n";
    echo str_repeat('-', 60) . "\n";

    // Execute migration
    $pdo->exec($sql);

    echo "✓ Migration executed successfully!\n\n";

    // Verify columns were added
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'users'
        AND column_name LIKE 'two_factor%'
        ORDER BY column_name
    ");

    $columns = $stmt->fetchAll();

    if (!empty($columns)) {
        echo "✓ Verified new 2FA columns:\n";
        foreach ($columns as $col) {
            echo "  - {$col['column_name']} ({$col['data_type']}";
            if ($col['is_nullable'] === 'NO') {
                echo ", NOT NULL";
            }
            echo ")\n";
        }
    } else {
        echo "⚠ Warning: Could not verify columns (may already exist)\n";
    }

    echo "\n✓ Migration completed successfully!\n";
    echo "\nYou can now use 2FA features in the application.\n";

} catch (PDOException $e) {
    // Check if columns already exist
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "✓ Migration already applied (columns already exist)\n";
        echo "Database is up to date.\n";
        exit(0);
    }

    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
