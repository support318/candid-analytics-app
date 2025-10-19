<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Manual sync trigger endpoint
 * GET /api/sync/ghl-historical?dry_run=1
 */
$app->get('/api/sync/ghl-historical', function (Request $request, Response $response) use ($container) {
    $params = $request->getQueryParams();
    $dryRun = isset($params['dry_run']) && $params['dry_run'] === '1';

    $logger = $container->get('logger');
    $db = $container->get('db');

    // Initialize GHL client
    $ghlApiKey = $_ENV['GHL_API_KEY'] ?? '';
    $ghlLocationId = $_ENV['GHL_LOCATION_ID'] ?? '';

    if (empty($ghlApiKey) || empty($ghlLocationId)) {
        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->getBody()->write(json_encode([
                'success' => false,
                'error' => 'GHL credentials not configured'
            ]));
    }

    // Run the sync directly (simplified version - just run the script)
    $output = [];
    $return_var = 0;

    $scriptPath = __DIR__ . '/../../scripts/sync-ghl-historical.php';
    $command = 'php ' . escapeshellarg($scriptPath) . ($dryRun ? ' --dry-run' : '');

    exec($command . ' 2>&1', $output, $return_var);

    $logger->info('GHL Historical sync triggered', [
        'dry_run' => $dryRun,
        'exit_code' => $return_var,
        'output' => implode("\n", $output)
    ]);

    $response->getBody()->write(json_encode([
        'success' => $return_var === 0,
        'dry_run' => $dryRun,
        'output' => implode("\n", $output),
        'exit_code' => $return_var
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Clear Redis cache endpoint
 * POST /api/sync/clear-cache
 */
$app->post('/api/sync/clear-cache', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');

    try {
        $redis = $container->get('redis');
        $redis->flushdb();

        $logger->info('Redis cache cleared via API');

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Redis cache cleared successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $logger->error('Failed to clear Redis cache', ['error' => $e->getMessage()]);

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Failed to clear cache: ' . $e->getMessage()
        ]));

        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    }
});

/**
 * Create materialized views endpoint
 * POST /api/sync/create-views
 */
$app->post('/api/sync/create-views', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');

    $output = [];
    $return_var = 0;

    $scriptPath = __DIR__ . '/../../scripts/create-materialized-views.php';
    $command = 'php ' . escapeshellarg($scriptPath);

    exec($command . ' 2>&1', $output, $return_var);

    $logger->info('Materialized views creation triggered', [
        'exit_code' => $return_var,
        'output' => implode("\n", $output)
    ]);

    $response->getBody()->write(json_encode([
        'success' => $return_var === 0,
        'output' => implode("\n", $output),
        'exit_code' => $return_var
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Refresh materialized views endpoint
 * POST /api/sync/refresh-views
 */
$app->post('/api/sync/refresh-views', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');
    $db = $container->get('db');

    try {
        $views = [
            'mv_priority_kpis',
            'mv_revenue_analytics',
            'mv_revenue_by_location',
            'mv_sales_funnel',
            'mv_lead_source_performance',
            'mv_operational_efficiency',
            'mv_staff_productivity',
            'mv_client_satisfaction',
            'mv_client_retention',
            'mv_marketing_performance',
            'mv_venue_performance',
            'mv_time_allocation',
            'mv_seasonal_patterns'
        ];

        $refreshed = [];
        $errors = [];

        foreach ($views as $view) {
            try {
                // Use regular refresh (locks view but doesn't require unique index)
                $db->execute("REFRESH MATERIALIZED VIEW $view");
                $refreshed[] = $view;
                $logger->info("Refreshed materialized view: $view");
            } catch (\Exception $e) {
                // View doesn't exist - skip silently, other errors - log
                if (strpos($e->getMessage(), 'does not exist') === false) {
                    $errors[$view] = $e->getMessage();
                    $logger->error("Failed to refresh view: $view", ['error' => $e->getMessage()]);
                }
            }
        }

        $response->getBody()->write(json_encode([
            'success' => count($errors) === 0,
            'refreshed' => $refreshed,
            'errors' => $errors,
            'total_views' => count($views),
            'successful' => count($refreshed)
        ]));

        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $logger->error('Failed to refresh materialized views', ['error' => $e->getMessage()]);

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]));

        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    }
});

/**
 * Check projects detail
 * GET /api/sync/projects-detail
 */
$app->get('/api/sync/projects-detail', function (Request $request, Response $response) use ($container) {
    $db = $container->get('db');

    $data = [
        'total' => $db->queryScalar("SELECT COUNT(*) FROM projects"),
        'by_status' => $db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status"),
        'all_projects' => $db->query("SELECT project_name, status, total_revenue, booking_date, event_date, created_at FROM projects ORDER BY created_at DESC"),
        'clients_with_projects' => $db->query("SELECT c.first_name, c.last_name, c.email, COUNT(p.id) as project_count FROM clients c LEFT JOIN projects p ON c.id = p.client_id GROUP BY c.id, c.first_name, c.last_name, c.email HAVING COUNT(p.id) > 0 ORDER BY project_count DESC")
    ];

    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Direct raw database query endpoint
 * GET /api/sync/raw-db-check
 */
$app->get('/api/sync/raw-db-check', function (Request $request, Response $response) use ($container) {
    $db = $container->get('db');

    // Raw queries to see actual database state
    $data = [
        'clients_count' => $db->queryScalar("SELECT COUNT(*) FROM clients"),
        'clients_with_email' => $db->queryScalar("SELECT COUNT(*) FROM clients WHERE email IS NOT NULL AND email != ''"),
        'clients_with_name' => $db->queryScalar("SELECT COUNT(*) FROM clients WHERE first_name IS NOT NULL AND first_name != ''"),
        'sample_clients' => $db->query("SELECT id, ghl_contact_id, first_name, last_name, email, phone, created_at FROM clients ORDER BY id DESC LIMIT 10"),
        'table_stats' => [
            'clients' => $db->queryScalar("SELECT COUNT(*) FROM clients"),
            'projects' => $db->queryScalar("SELECT COUNT(*) FROM projects"),
            'inquiries' => $db->queryScalar("SELECT COUNT(*) FROM inquiries"),
            'consultations' => $db->queryScalar("SELECT COUNT(*) FROM consultations"),
            'revenue' => $db->queryScalar("SELECT COUNT(*) FROM revenue")
        ],
        'materialized_view_data' => [
            'priority_kpis' => $db->queryOne("SELECT * FROM mv_priority_kpis"),
            'sales_funnel_sample' => $db->query("SELECT * FROM mv_sales_funnel ORDER BY month DESC LIMIT 3"),
            'revenue_analytics_sample' => $db->query("SELECT * FROM mv_revenue_analytics ORDER BY month DESC LIMIT 3")
        ],
        'projects_sample' => $db->query("SELECT id, project_name, status, total_revenue, booking_date, event_date FROM projects ORDER BY booking_date DESC LIMIT 5")
    ];

    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Debug endpoint to check database contents
 * GET /api/sync/data-check
 */
$app->get('/api/sync/data-check', function (Request $request, Response $response) use ($container) {
    $db = $container->get('db');

    $data = [
        'clients' => [
            'total' => $db->queryScalar("SELECT COUNT(*) FROM clients"),
            'active' => $db->queryScalar("SELECT COUNT(*) FROM clients WHERE status = 'active'"),
            'sample' => $db->query("SELECT id, first_name, last_name, email, lifecycle_stage, created_at FROM clients LIMIT 5")
        ],
        'projects' => [
            'total' => $db->queryScalar("SELECT COUNT(*) FROM projects"),
            'by_status' => $db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status"),
            'sample' => $db->query("SELECT id, project_name, status, total_revenue, booking_date, created_at FROM projects LIMIT 5")
        ],
        'inquiries' => [
            'total' => $db->queryScalar("SELECT COUNT(*) FROM inquiries"),
            'sample' => $db->query("SELECT id, client_id, inquiry_date, source, event_type, status, created_at FROM inquiries LIMIT 3")
        ],
        'consultations' => [
            'total' => $db->queryScalar("SELECT COUNT(*) FROM consultations"),
        ],
        'revenue' => [
            'total_records' => $db->queryScalar("SELECT COUNT(*) FROM revenue"),
            'total_amount' => $db->queryScalar("SELECT COALESCE(SUM(amount), 0) FROM revenue"),
        ],
        'materialized_views' => [
            'priority_kpis' => $db->queryOne("SELECT * FROM mv_priority_kpis LIMIT 1"),
            'sales_funnel' => $db->query("SELECT * FROM mv_sales_funnel LIMIT 5"),
        ]
    ];

    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Debug GHL API structure
 * GET /api/sync/debug-ghl-api
 */
$app->get('/api/sync/debug-ghl-api', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');

    $output = [];
    $return_var = 0;

    $scriptPath = __DIR__ . '/../../scripts/debug-ghl-api.php';
    $command = 'php ' . escapeshellarg($scriptPath);

    exec($command . ' 2>&1', $output, $return_var);

    $logger->info('GHL API debug triggered', [
        'exit_code' => $return_var,
        'output' => implode("\n", $output)
    ]);

    $response->getBody()->write(json_encode([
        'success' => $return_var === 0,
        'output' => implode("\n", $output),
        'exit_code' => $return_var
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Debug single GHL contact
 * GET /api/sync/debug-ghl-single
 */
$app->get('/api/sync/debug-ghl-single', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');

    $output = [];
    $return_var = 0;

    $scriptPath = __DIR__ . '/../../scripts/debug-ghl-single-contact.php';
    $command = 'php ' . escapeshellarg($scriptPath);

    exec($command . ' 2>&1', $output, $return_var);

    $logger->info('GHL single contact debug triggered', [
        'exit_code' => $return_var,
        'output' => implode("\n", $output)
    ]);

    $response->getBody()->write(json_encode([
        'success' => $return_var === 0,
        'output' => implode("\n", $output),
        'exit_code' => $return_var
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Analyze GHL data quality
 * GET /api/sync/analyze-data-quality
 */
$app->get('/api/sync/analyze-data-quality', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');

    $output = [];
    $return_var = 0;

    $scriptPath = __DIR__ . '/../../scripts/analyze-ghl-data-quality.php';
    $command = 'php ' . escapeshellarg($scriptPath);

    exec($command . ' 2>&1', $output, $return_var);

    $logger->info('GHL data quality analysis triggered', [
        'exit_code' => $return_var,
        'output' => implode("\n", $output)
    ]);

    $response->getBody()->write(json_encode([
        'success' => $return_var === 0,
        'output' => implode("\n", $output),
        'exit_code' => $return_var
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Clear database and re-sync with clean data
 * POST /api/sync/clear-and-resync
 */
$app->post('/api/sync/clear-and-resync', function (Request $request, Response $response) use ($container) {
    $logger = $container->get('logger');
    $db = $container->get('db');

    try {
        $logger->info('Starting database clear and re-sync');

        // Clear all existing data (CASCADE will delete related records)
        $db->execute("TRUNCATE clients, projects, inquiries, consultations, revenue RESTART IDENTITY CASCADE");
        $logger->info('Database cleared');

        // Run historical sync
        $output = [];
        $return_var = 0;
        $scriptPath = __DIR__ . '/../../scripts/sync-ghl-historical.php';
        $command = 'php ' . escapeshellarg($scriptPath);

        exec($command . ' 2>&1', $output, $return_var);

        $syncOutput = implode("\n", $output);
        $logger->info('Historical sync completed', [
            'exit_code' => $return_var,
            'output' => $syncOutput
        ]);

        // Refresh materialized views
        $views = [
            'mv_priority_kpis',
            'mv_revenue_analytics',
            'mv_revenue_by_location',
            'mv_sales_funnel',
            'mv_lead_source_performance',
            'mv_operational_efficiency',
            'mv_staff_productivity',
            'mv_client_satisfaction',
            'mv_client_retention',
            'mv_marketing_performance',
            'mv_venue_performance',
            'mv_time_allocation',
            'mv_seasonal_patterns'
        ];

        $refreshed = [];
        foreach ($views as $view) {
            try {
                $db->execute("REFRESH MATERIALIZED VIEW $view");
                $refreshed[] = $view;
            } catch (\Exception $e) {
                // Skip if view doesn't exist
                if (strpos($e->getMessage(), 'does not exist') === false) {
                    $logger->error("Failed to refresh view: $view", ['error' => $e->getMessage()]);
                }
            }
        }

        $logger->info('Materialized views refreshed', ['refreshed' => $refreshed]);

        $response->getBody()->write(json_encode([
            'success' => $return_var === 0,
            'message' => 'Database cleared and re-synced with clean GHL data',
            'sync_output' => $syncOutput,
            'views_refreshed' => count($refreshed)
        ]));

        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $logger->error('Clear and re-sync failed', ['error' => $e->getMessage()]);

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]));

        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    }
});
