<?php

declare(strict_types=1);

namespace CandidAnalytics\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * KPI Controller
 * Handles priority KPIs dashboard endpoint
 */
class KpiController
{
    private $db;
    private $cache;
    private $logger;

    public function __construct($container)
    {
        $this->db = $container->get('db');
        $this->cache = $container->get('redis');
        $this->logger = $container->get('logger');
    }

    /**
     * Get priority KPIs
     * GET /api/v1/kpis/priority
     */
    public function getPriorityKpis(Request $request, Response $response): Response
    {
        $cacheKey = 'kpis:priority';

        try {
            // Try cache first
            if ($cached = $this->cache->get($cacheKey)) {
                $this->logger->info('Priority KPIs served from cache');

                return $this->jsonResponse($response, [
                    'success' => true,
                    'data' => json_decode($cached, true),
                    'meta' => [
                        'cached' => true,
                        'timestamp' => date('c')
                    ]
                ]);
            }

            // Query database
            $startTime = microtime(true);
            $kpis = $this->db->getPriorityKpis();
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            if (!$kpis) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => 'NO_DATA',
                        'message' => 'No KPI data available'
                    ]
                ], 404);
            }

            // Cache for 5 minutes
            $this->cache->setex($cacheKey, 300, json_encode($kpis));

            $this->logger->info('Priority KPIs fetched from database', [
                'query_time_ms' => $queryTime
            ]);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $kpis,
                'meta' => [
                    'cached' => false,
                    'query_time_ms' => $queryTime,
                    'timestamp' => date('c')
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error fetching priority KPIs', [
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to fetch KPIs'
                ]
            ], 500);
        }
    }

    /**
     * Helper: JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
