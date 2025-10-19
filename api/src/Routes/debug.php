<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Debug endpoint to check database contents
 * GET /api/debug/data-check
 */
$app->get('/api/debug/data-check', function (Request $request, Response $response) use ($container) {
    $db = $container->get('db');

    $data = [
        'clients' => [
            'total' => $db->queryScalar("SELECT COUNT(*) FROM clients"),
            'active' => $db->queryScalar("SELECT COUNT(*) FROM clients WHERE status = 'active'"),
            'sample' => $db->query("SELECT id, first_name, last_name, email, lifecycle_stage FROM clients LIMIT 3")
        ],
        'projects' => [
            'total' => $db->queryScalar("SELECT COUNT(*) FROM projects"),
            'by_status' => $db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status"),
            'sample' => $db->query("SELECT id, project_name, status, total_revenue FROM projects LIMIT 3")
        ],
        'inquiries' => [
            'total' => $db->queryScalar("SELECT COUNT(*) FROM inquiries"),
            'sample' => $db->query("SELECT id, inquiry_text, status FROM inquiries LIMIT 3")
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
            'sales_funnel' => $db->query("SELECT * FROM mv_sales_funnel LIMIT 3"),
        ]
    ];

    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});
