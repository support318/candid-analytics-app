#!/usr/bin/env php
<?php

/**
 * Database Migration Runner
 * Executes SQL migration files on the production database
 */

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Database connection
try {
    // Railway provides DATABASE_URL, so try that first
    if (!empty($_ENV['DATABASE_URL'])) {
        $databaseUrl = $_ENV['DATABASE_URL'];

        // Parse DATABASE_URL
        $dbParts = parse_url($databaseUrl);
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $dbParts['host'],
            $dbParts['port'] ?? 5432,
            ltrim($dbParts['path'], '/')
        );

        $pdo = new PDO(
            $dsn,
            $dbParts['user'],
            $dbParts['pass'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        echo "✓ Connected to database: " . ltrim($dbParts['path'], '/') . "\n\n";
    } else {
        // Fallback to individual env vars
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME']
        );

        $pdo = new PDO(
            $dsn,
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        echo "✓ Connected to database: {$_ENV['DB_NAME']}\n\n";
    }

    // Get migration file from command line argument or use default
    $migrationFile = $argv[1] ?? __DIR__ . '/database/migrations/add_2fa_support.sql';

    if (!file_exists($migrationFile)) {
        die("✗ Error: Migration file not found: {$migrationFile}\n");
    }

    echo "Running migration: " . basename($migrationFile) . "\n";
    echo str_repeat('-', 60) . "\n";

    // Read migration SQL
    $sql = file_get_contents($migrationFile);

    // Execute migration
    $pdo->exec($sql);

    echo "✓ Migration completed successfully!\n\n";

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
            echo "  - {$col['column_name']} ({$col['data_type']})\n";
        }
    } else {
        echo "⚠ Warning: Could not verify new columns\n";
    }

    echo "\n✓ All done!\n";

} catch (PDOException $e) {
    die("✗ Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("✗ Error: " . $e->getMessage() . "\n");
}
