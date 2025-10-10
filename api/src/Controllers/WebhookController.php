<?php

declare(strict_types=1);

namespace CandidAnalytics\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Webhook Controller Base
 * Handles incoming webhooks from Make.com with GHL data
 */
class WebhookController
{
    private $db;
    private $logger;
    private $webhookSecret;

    public function __construct($container)
    {
        $this->db = $container->get('db');
        $this->logger = $container->get('logger');
        $this->webhookSecret = $_ENV['WEBHOOK_SECRET'] ?? '';
    }

    /**
     * Validate webhook signature
     */
    protected function validateWebhook(Request $request): bool
    {
        // TEMPORARY: Skip validation for testing
        $this->logger->info('Webhook validation skipped (testing mode)');
        return true;

        /*
        $signature = $request->getHeaderLine('X-Webhook-Signature');

        if (empty($this->webhookSecret)) {
            $this->logger->warning('Webhook secret not configured');
            return true; // Allow during development
        }

        if (empty($signature)) {
            $this->logger->warning('Webhook signature missing');
            return false;
        }

        $body = (string) $request->getBody();
        $expectedSignature = hash_hmac('sha256', $body, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
        */
    }

    /**
     * Parse webhook payload
     */
    protected function parsePayload(Request $request): ?array
    {
        try {
            $body = (string) $request->getBody();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON payload', [
                    'error' => json_last_error_msg()
                ]);
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Error parsing webhook payload', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Receive project/booking webhook
     * POST /api/webhooks/projects
     */
    public function receiveProject(Request $request, Response $response): Response
    {
        if (!$this->validateWebhook($request)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid webhook signature'
            ], 401);
        }

        $data = $this->parsePayload($request);
        if (!$data) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid payload'
            ], 400);
        }

        try {
            // Extract GHL data
            $ghlContactId = $data['contact_id'] ?? $data['contactId'] ?? null;
            $projectName = $data['opportunity_name'] ?? $data['project_name'] ?? null;
            $bookingDate = $data['booking_date'] ?? $data['event_date'] ?? null;
            $totalRevenue = $data['total_value'] ?? $data['monetary_value'] ?? 0;
            $status = $data['status'] ?? 'booked';

            if (!$ghlContactId || !$projectName) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required fields: contact_id and opportunity_name'
                ], 400);
            }

            // Check if client exists, create if not
            $client = $this->db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                // Create client from webhook data
                $clientData = [
                    'ghl_contact_id' => $ghlContactId,
                    'first_name' => $data['first_name'] ?? 'Unknown',
                    'last_name' => $data['last_name'] ?? '',
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'status' => 'active',
                    'lifecycle_stage' => 'customer',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $clientId = $this->db->insert('clients', $clientData);
                $this->logger->info('Created new client from webhook', [
                    'client_id' => $clientId,
                    'ghl_contact_id' => $ghlContactId
                ]);
            } else {
                $clientId = $client['id'];
            }

            // Check if project already exists
            $existingProject = $this->db->queryOne(
                "SELECT id FROM projects WHERE client_id = ? AND project_name = ?",
                [$clientId, $projectName]
            );

            if ($existingProject) {
                // Update existing project
                $updateData = [
                    'status' => $status,
                    'total_revenue' => $totalRevenue,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($bookingDate) {
                    $updateData['booking_date'] = $bookingDate;
                }

                $this->db->update('projects', $updateData, ['id' => $existingProject['id']]);
                $projectId = $existingProject['id'];

                $this->logger->info('Updated existing project', [
                    'project_id' => $projectId
                ]);
            } else {
                // Create new project
                $projectData = [
                    'client_id' => $clientId,
                    'project_name' => $projectName,
                    'booking_date' => $bookingDate ?? date('Y-m-d'),
                    'event_date' => $data['event_date'] ?? $bookingDate ?? date('Y-m-d'),
                    'event_type' => $data['event_type'] ?? 'other',
                    'venue_name' => $data['location'] ?? $data['venue_name'] ?? null,
                    'status' => $status,
                    'total_revenue' => $totalRevenue,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $projectId = $this->db->insert('projects', $projectData);
                $this->logger->info('Created new project', [
                    'project_id' => $projectId,
                    'client_id' => $clientId
                ]);
            }

            // Refresh materialized views
            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'project_id' => $projectId,
                    'client_id' => $clientId
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing project webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Server error processing webhook'
            ], 500);
        }
    }

    /**
     * Receive revenue/payment webhook
     * POST /api/webhooks/revenue
     */
    public function receiveRevenue(Request $request, Response $response): Response
    {
        if (!$this->validateWebhook($request)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid webhook signature'
            ], 401);
        }

        $data = $this->parsePayload($request);
        if (!$data) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid payload'
            ], 400);
        }

        try {
            $ghlContactId = $data['contact_id'] ?? null;
            $amount = $data['amount'] ?? 0;
            $paymentDate = $data['payment_date'] ?? date('Y-m-d');
            $paymentMethod = $data['payment_method'] ?? 'other';

            if (!$ghlContactId || $amount <= 0) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required fields: contact_id and amount'
                ], 400);
            }

            // Find client
            $client = $this->db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Client not found'
                ], 404);
            }

            // Find most recent project for this client
            $project = $this->db->queryOne(
                "SELECT id FROM projects WHERE client_id = ? ORDER BY created_at DESC LIMIT 1",
                [$client['id']]
            );

            if (!$project) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'No project found for client'
                ], 404);
            }

            // Insert revenue record
            $revenueData = [
                'project_id' => $project['id'],
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'category' => $data['category'] ?? 'booking',
                'notes' => $data['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $revenueId = $this->db->insert('revenue', $revenueData);

            $this->logger->info('Recorded revenue payment', [
                'revenue_id' => $revenueId,
                'project_id' => $project['id'],
                'amount' => $amount
            ]);

            // Refresh materialized views
            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'revenue_id' => $revenueId,
                    'project_id' => $project['id']
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing revenue webhook', [
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Server error processing webhook'
            ], 500);
        }
    }

    /**
     * Receive inquiry/lead webhook
     * POST /api/webhooks/inquiries
     */
    public function receiveInquiry(Request $request, Response $response): Response
    {
        if (!$this->validateWebhook($request)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid webhook signature'
            ], 401);
        }

        $data = $this->parsePayload($request);
        if (!$data) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid payload'
            ], 400);
        }

        try {
            $ghlContactId = $data['contact_id'] ?? null;
            $source = $data['source'] ?? 'website';
            $status = $data['status'] ?? 'new';

            if (!$ghlContactId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required field: contact_id'
                ], 400);
            }

            // Find or create client
            $client = $this->db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                // Create new client
                $clientData = [
                    'ghl_contact_id' => $ghlContactId,
                    'first_name' => $data['first_name'] ?? 'Unknown',
                    'last_name' => $data['last_name'] ?? '',
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'lead_source' => $source,
                    'status' => 'active',
                    'lifecycle_stage' => 'lead',
                    'created_at' => date('Y-m-d H:i:s'),
                    'first_inquiry_date' => date('Y-m-d')
                ];

                $clientId = $this->db->insert('clients', $clientData);
            } else {
                $clientId = $client['id'];
            }

            // Insert inquiry
            $inquiryData = [
                'client_id' => $clientId,
                'inquiry_date' => $data['inquiry_date'] ?? date('Y-m-d'),
                'source' => $source,
                'event_type' => $data['event_type'] ?? null,
                'budget' => $data['budget'] ?? null,
                'status' => $status,
                'outcome' => $data['outcome'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $inquiryId = $this->db->insert('inquiries', $inquiryData);

            $this->logger->info('Recorded new inquiry', [
                'inquiry_id' => $inquiryId,
                'client_id' => $clientId
            ]);

            // Refresh materialized views
            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'inquiry_id' => $inquiryId,
                    'client_id' => $clientId
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing inquiry webhook', [
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Server error processing webhook'
            ], 500);
        }
    }

    /**
     * Refresh materialized views
     */
    private function refreshViews(): void
    {
        try {
            $views = [
                'mv_priority_kpis',
                'mv_revenue_monthly',
                'mv_sales_funnel',
                'mv_operations_efficiency'
            ];

            foreach ($views as $view) {
                $this->db->execute("REFRESH MATERIALIZED VIEW CONCURRENTLY $view");
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error refreshing views', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper: JSON response
     */
    protected function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
