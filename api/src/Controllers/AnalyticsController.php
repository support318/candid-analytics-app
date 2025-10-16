<?php

declare(strict_types=1);

namespace CandidAnalytics\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Analytics Controller
 * Handles all analytics endpoints (Revenue, Sales, Operations, Satisfaction, Marketing, Staff, AI)
 */
class AnalyticsController
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

    // =========================================================================
    // REVENUE ANALYTICS
    // =========================================================================

    /**
     * GET /api/v1/revenue
     */
    public function getRevenue(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $months = (int)($params['months'] ?? 12);

        return $this->cachedQuery(
            "revenue:analytics:$months",
            fn() => $this->db->getRevenueAnalytics($months),
            $response
        );
    }

    /**
     * GET /api/v1/revenue/by-location
     */
    public function getRevenueByLocation(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $limit = (int)($params['limit'] ?? 20);

        return $this->cachedQuery(
            "revenue:by-location:$limit",
            fn() => $this->db->getRevenueByLocation($limit),
            $response
        );
    }

    // =========================================================================
    // SALES FUNNEL
    // =========================================================================

    /**
     * GET /api/v1/sales-funnel
     */
    public function getSalesFunnel(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $months = (int)($params['months'] ?? 12);

        return $this->cachedQuery(
            "sales:funnel:$months",
            fn() => $this->db->getSalesFunnel($months),
            $response
        );
    }

    /**
     * GET /api/v1/lead-sources
     */
    public function getLeadSources(Request $request, Response $response): Response
    {
        return $this->cachedQuery(
            "sales:lead-sources",
            fn() => $this->db->getLeadSourcePerformance(),
            $response
        );
    }

    // =========================================================================
    // OPERATIONS
    // =========================================================================

    /**
     * GET /api/v1/operations
     */
    public function getOperations(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $months = (int)($params['months'] ?? 12);

        return $this->cachedQuery(
            "operations:efficiency:$months",
            fn() => $this->db->getOperationalEfficiency($months),
            $response
        );
    }

    // =========================================================================
    // CLIENT SATISFACTION
    // =========================================================================

    /**
     * GET /api/v1/satisfaction
     */
    public function getSatisfaction(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $months = (int)($params['months'] ?? 12);

        return $this->cachedQuery(
            "satisfaction:metrics:$months",
            fn() => $this->db->getClientSatisfaction($months),
            $response
        );
    }

    /**
     * GET /api/v1/satisfaction/retention
     */
    public function getRetention(Request $request, Response $response): Response
    {
        return $this->cachedQuery(
            "satisfaction:retention",
            fn() => $this->db->getClientRetention(),
            $response
        );
    }

    // =========================================================================
    // MARKETING PERFORMANCE
    // =========================================================================

    /**
     * GET /api/v1/marketing
     */
    public function getMarketing(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $months = (int)($params['months'] ?? 12);

        return $this->cachedQuery(
            "marketing:performance:$months",
            fn() => $this->db->getMarketingPerformance($months),
            $response
        );
    }

    // =========================================================================
    // STAFF PRODUCTIVITY
    // =========================================================================

    /**
     * GET /api/v1/staff
     */
    public function getStaff(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $months = (int)($params['months'] ?? 6);

        return $this->cachedQuery(
            "staff:productivity:$months",
            fn() => $this->db->getStaffProductivity($months),
            $response
        );
    }

    // =========================================================================
    // AI INSIGHTS
    // =========================================================================

    /**
     * GET /api/v1/ai/insights
     */
    public function getAiInsights(Request $request, Response $response): Response
    {
        try {
            $insights = [];

            // Get high-value leads
            try {
                $highValueLeads = $this->db->getHighValueLeads(75.0, 5);
                if (!empty($highValueLeads)) {
                    $insights[] = [
                        'insight_type' => 'opportunity',
                        'impact' => 'high',
                        'title' => 'High-Value Leads Identified',
                        'description' => sprintf(
                            'You have %d high-value leads with conversion probability above 75%%. These leads show strong buying signals and are ready for follow-up.',
                            count($highValueLeads)
                        ),
                        'recommendation' => 'Prioritize reaching out to these leads within the next 24-48 hours. Personalize your communication based on their specific interests and event type.',
                        'confidence' => 0.87
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->warning('Could not fetch high value leads', ['error' => $e->getMessage()]);
            }

            // Get client segments
            try {
                $segments = $this->db->getClientSegments();
                if (!empty($segments)) {
                    $topSegment = $segments[0] ?? null;
                    if ($topSegment) {
                        $insights[] = [
                            'insight_type' => 'pattern',
                            'impact' => 'medium',
                            'title' => 'Top Customer Segment Identified',
                            'description' => sprintf(
                                'Your most valuable customer segment is "%s" with %d bookings generating $%s in revenue. This segment shows consistent booking patterns.',
                                $topSegment['segment_name'] ?? 'Premium Clients',
                                $topSegment['client_count'] ?? 0,
                                number_format($topSegment['total_revenue'] ?? 0)
                            ),
                            'recommendation' => 'Focus your marketing efforts on attracting similar clients. Consider creating targeted campaigns for this segment and offering loyalty incentives.',
                            'confidence' => 0.82
                        ];
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning('Could not fetch client segments', ['error' => $e->getMessage()]);
            }

            // Get urgent communications
            try {
                $urgentComms = $this->db->getUrgentCommunications(4, 5);
                if (!empty($urgentComms)) {
                    $insights[] = [
                        'insight_type' => 'alert',
                        'impact' => 'high',
                        'title' => 'Urgent Client Communications Needed',
                        'description' => sprintf(
                            '%d clients require immediate attention. These clients have high urgency scores and may be at risk of dissatisfaction or churn.',
                            count($urgentComms)
                        ),
                        'recommendation' => 'Review these client communications immediately. Address any concerns or issues promptly to maintain client satisfaction and prevent potential negative reviews.',
                        'confidence' => 0.91
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->warning('Could not fetch urgent communications', ['error' => $e->getMessage()]);
            }

            // Add some general insights based on available data
            if (empty($insights)) {
                // No specific insights available, provide general guidance
                $insights[] = [
                    'insight_type' => 'tip',
                    'impact' => 'low',
                    'title' => 'Building Your Analytics Profile',
                    'description' => 'As you continue to use the system and gather more data, AI insights will become more accurate and actionable. Keep tracking your leads, clients, and communications.',
                    'recommendation' => 'Ensure all client touchpoints are being logged in the system. The more data you have, the better our AI can identify patterns and opportunities.',
                    'confidence' => 1.0
                ];
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $insights,
                'meta' => ['timestamp' => date('c')]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error fetching AI insights', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to fetch AI insights: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * POST /api/v1/ai/predict-lead
     */
    public function predictLead(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $inquiryId = $data['inquiry_id'] ?? '';

        if (empty($inquiryId)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'inquiry_id is required'
                ]
            ], 400);
        }

        try {
            $similar = $this->db->findSimilarInquiries($inquiryId, 0.7, 10);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'inquiry_id' => $inquiryId,
                    'similar_inquiries' => $similar,
                    'predicted_conversion' => $this->calculateAvgConversion($similar)
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error predicting lead', [
                'error' => $e->getMessage(),
                'inquiry_id' => $inquiryId
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to predict lead conversion'
                ]
            ], 500);
        }
    }

    /**
     * GET /api/v1/ai/similar-clients/:clientId
     */
    public function getSimilarClients(Request $request, Response $response, array $args): Response
    {
        $clientId = $args['clientId'] ?? '';
        $limit = (int)($request->getQueryParams()['limit'] ?? 10);

        if (empty($clientId)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'clientId is required'
                ]
            ], 400);
        }

        try {
            $similar = $this->db->findSimilarClients($clientId, $limit);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'client_id' => $clientId,
                    'similar_clients' => $similar
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error finding similar clients', [
                'error' => $e->getMessage(),
                'client_id' => $clientId
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to find similar clients'
                ]
            ], 500);
        }
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Execute query with caching
     */
    private function cachedQuery(string $cacheKey, callable $query, Response $response): Response
    {
        try {
            // Try cache first
            if ($cached = $this->cache->get($cacheKey)) {
                return $this->jsonResponse($response, [
                    'success' => true,
                    'data' => json_decode($cached, true),
                    'meta' => ['cached' => true, 'timestamp' => date('c')]
                ]);
            }

            // Query database
            $startTime = microtime(true);
            $data = $query();
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Empty array is valid data, not an error
            // Only return 404 if query returned null (not found)
            if ($data === null) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => [
                        'code' => 'NO_DATA',
                        'message' => 'No data available'
                    ]
                ], 404);
            }

            // Ensure data is always an array
            if (!is_array($data)) {
                $data = [$data];
            }

            // Cache for 5 minutes (even empty arrays)
            $this->cache->setex($cacheKey, 300, json_encode($data));

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $data,
                'meta' => [
                    'cached' => false,
                    'query_time_ms' => $queryTime,
                    'timestamp' => date('c')
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error executing query', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to fetch data'
                ]
            ], 500);
        }
    }

    /**
     * Calculate average conversion from similar inquiries
     */
    private function calculateAvgConversion(array $similar): float
    {
        if (empty($similar)) {
            return 50.0;
        }

        $total = 0;
        $count = 0;

        foreach ($similar as $inquiry) {
            if (isset($inquiry['predicted_conversion'])) {
                $total += $inquiry['predicted_conversion'];
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 2) : 50.0;
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
