#!/usr/bin/env php
<?php

/**
 * Recreate mv_priority_kpis view with GHL-compatible logic
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use CandidAnalytics\Services\Database;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "🔧 Recreating mv_priority_kpis view for GHL data...\n\n";

// Initialize database
$db = new Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);

// Read SQL file
$sql = file_get_contents(__DIR__ . '/../database/create_priority_kpis_ghl.sql');

try {
    // Execute SQL
    $db->getConnection()->exec($sql);

    echo "✅ View recreated successfully!\n\n";

    // Check the results
    $kpis = $db->queryOne("SELECT * FROM mv_priority_kpis");

    echo "📊 Priority KPIs (New View):\n";
    echo str_repeat("=", 60) . "\n";
    echo "Leads in Pipeline:     {$kpis['leads_in_pipeline']}\n";
    echo "Projects in Progress:  {$kpis['projects_in_progress']}\n";
    echo "Today's Revenue:       \${$kpis['today_revenue']}\n";
    echo "Month Revenue:         \${$kpis['month_revenue']}\n";
    echo "Month Bookings:        {$kpis['month_bookings']}\n";
    echo "Conversion Rate:       {$kpis['conversion_rate']}%\n";
    echo "Avg Booking Value:     \${$kpis['avg_booking_value']}\n";
    echo str_repeat("=", 60) . "\n";

    echo "\n✅ Done!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
