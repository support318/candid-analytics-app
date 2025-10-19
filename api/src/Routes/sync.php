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
                $db->execute("REFRESH MATERIALIZED VIEW CONCURRENTLY $view");
                $refreshed[] = $view;
                $logger->info("Refreshed materialized view: $view");
            } catch (\Exception $e) {
                $errors[$view] = $e->getMessage();
                $logger->error("Failed to refresh view: $view", ['error' => $e->getMessage()]);
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
