<?php

/**
 * Create Materialized Views Script
 *
 * This script creates all materialized views needed for the dashboard analytics.
 * Can be run directly or triggered via API endpoint.
 */

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Database connection
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected to database successfully.\n";

    // Read the SQL file
    $sqlFile = __DIR__ . '/../database/create_all_materialized_views.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: {$sqlFile}");
    }

    echo "Using SQL file: {$sqlFile}\n";

    $sql = file_get_contents($sqlFile);

    if ($sql === false) {
        throw new Exception("Failed to read SQL file");
    }

    echo "Executing materialized views SQL...\n";

    // Execute the entire SQL file
    $pdo->exec($sql);

    echo "\n✅ All materialized views created successfully!\n\n";

    // Verify views were created
    $stmt = $pdo->query("
        SELECT
            matviewname,
            ispopulated,
            hasindexes
        FROM pg_matviews
        WHERE schemaname = 'public'
        ORDER BY matviewname
    ");

    $views = $stmt->fetchAll();

    echo "Materialized Views Created:\n";
    echo str_repeat('-', 70) . "\n";
    printf("%-40s %-15s %-15s\n", "View Name", "Populated", "Has Indexes");
    echo str_repeat('-', 70) . "\n";

    foreach ($views as $view) {
        printf(
            "%-40s %-15s %-15s\n",
            $view['matviewname'],
            $view['ispopulated'] ? 'Yes' : 'No',
            $view['hasindexes'] ? 'Yes' : 'No'
        );
    }

    echo str_repeat('-', 70) . "\n";
    echo "Total views created: " . count($views) . "\n";

    exit(0);

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
