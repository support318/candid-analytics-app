#!/usr/bin/env php
<?php
/**
 * Quick database verification script
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CandidAnalytics\Services\Database;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Database connection
try {
    $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);

    if ($databaseUrl) {
        $parsed = parse_url($databaseUrl);
        $dbHost = $parsed['host'] ?? 'localhost';
        $dbPort = (string)($parsed['port'] ?? '5432');
        $dbName = ltrim($parsed['path'] ?? '/candid_analytics', '/');
        $dbUser = $parsed['user'] ?? 'candid_analytics_user';
        $dbPassword = $parsed['pass'] ?? '';
    } else {
        $dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
        $dbPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432');
        $dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'candid_analytics');
        $dbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'candid_analytics_user');
        $dbPassword = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
    }

    $db = new Database($dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    echo "✅ Connected to database\n\n";

} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Count records
echo "==============================================\n";
echo "DATABASE POPULATION\n";
echo "==============================================\n\n";

$tables = ['clients', 'inquiries', 'projects', 'staff_assignments', 'deliverables', 'reviews'];
foreach ($tables as $table) {
    $result = $db->queryOne("SELECT COUNT(*) as count FROM $table");
    printf("%-20s %d\n", ucfirst($table) . ':', $result['count']);
}

// Sample data
echo "\n==============================================\n";
echo "SAMPLE CLIENT DATA (with tags)\n";
echo "==============================================\n\n";

$clients = $db->query("SELECT first_name, last_name, email, tags FROM clients WHERE tags IS NOT NULL LIMIT 3");
foreach ($clients as $client) {
    echo "• {$client['first_name']} {$client['last_name']} ({$client['email']})\n";
    echo "  Tags: {$client['tags']}\n";
}

echo "\n==============================================\n";
echo "SAMPLE INQUIRY DATA (with status)\n";
echo "==============================================\n\n";

$inquiries = $db->query("SELECT event_type, status, budget FROM inquiries LIMIT 5");
foreach ($inquiries as $inq) {
    $budget = $inq['budget'] ? '$' . number_format($inq['budget'], 2) : 'N/A';
    echo "• {$inq['event_type']} - Status: {$inq['status']} - Budget: $budget\n";
}

echo "\n✅ Verification complete!\n";
