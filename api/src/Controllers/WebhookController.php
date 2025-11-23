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

            // Log the raw body for debugging
            $this->logger->info('Received webhook payload', [
                'body' => substr($body, 0, 1000), // First 1000 chars
                'content_type' => $request->getHeaderLine('Content-Type'),
                'body_length' => strlen($body)
            ]);

            if (empty($body)) {
                $this->logger->error('Empty request body');
                return null;
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON payload', [
                    'error' => json_last_error_msg(),
                    'body' => $body
                ]);
                return null;
            }

            // Ensure we got an array (handle case where Make sends just a number)
            if (!is_array($data)) {
                $this->logger->error('Payload is not an array - Make.com may be sending wrong data', [
                    'type' => gettype($data),
                    'value' => $data,
                    'hint' => 'Check Make.com HTTP module data field - should be {{toJSON(1)}} or explicit field mapping'
                ]);
                return null;
            }

            $this->logger->info('Parsed webhook payload successfully', [
                'keys' => array_keys($data),
                'has_id' => isset($data['id']),
                'has_customField' => isset($data['customField'])
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Error parsing webhook payload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    // Pipeline ID constants
    private const PLANNING_PIPELINE_ID = 'L2s9gNWdWzCbutNTC4DE';
    private const BOOKED_STAGE_ID = 'bad101e4-ff48-4ab8-845a-1660f0c0c7da';

    /**
     * Receive project/booking webhook
     * POST /api/webhooks/projects
     *
     * CRITICAL: Only create projects when opportunity is in PLANNING pipeline!
     *
     * Expected payload from n8n/GHL webhook:
     * - id or contact_id: GHL contact ID
     * - opportunity_id: GHL opportunity ID
     * - pipeline_id: Must be L2s9gNWdWzCbutNTC4DE (PLANNING) for booking
     * - stage_id: Stage within the pipeline
     * - stage_name: Human-readable stage name
     * - firstName, lastName, email, phone: Standard fields
     * - customField[...]: Custom field values
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
            $ghlContactId = $data['id'] ?? $data['contact_id'] ?? null;
            $ghlOpportunityId = $data['opportunity_id'] ?? null;
            $pipelineId = $data['pipeline_id'] ?? $data['pipelineId'] ?? null;
            $stageId = $data['stage_id'] ?? $data['stageId'] ?? null;
            $stageName = $data['stage_name'] ?? $data['stageName'] ?? null;

            // CRITICAL: Validate this is a PLANNING pipeline opportunity
            // Only contacts in PLANNING pipeline with signed agreement should become projects
            if ($pipelineId && $pipelineId !== self::PLANNING_PIPELINE_ID) {
                $this->logger->info('Ignoring project webhook - not in PLANNING pipeline', [
                    'pipeline_id' => $pipelineId,
                    'expected_pipeline_id' => self::PLANNING_PIPELINE_ID,
                    'contact_id' => $ghlContactId
                ]);

                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Ignored - opportunity not in PLANNING pipeline',
                    'pipeline_id' => $pipelineId
                ]);
            }

            // Map GHL status to valid project status
            $rawStatus = $data['status'] ?? 'booked';
            $statusMap = [
                'open' => 'booked',
                'won' => 'booked',
                'lost' => 'cancelled',
                'abandoned' => 'cancelled',
                'booked' => 'booked',
                'quoted' => 'quoted',
                'confirmed' => 'confirmed',
                'in-progress' => 'in-progress',
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                'archived' => 'archived'
            ];
            $status = $statusMap[strtolower($rawStatus)] ?? 'booked';

            if (!$ghlContactId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required field: id or contact_id'
                ], 400);
            }

            $this->logger->info('Processing project booking webhook', [
                'contact_id' => $ghlContactId,
                'opportunity_id' => $ghlOpportunityId,
                'pipeline_id' => $pipelineId,
                'stage_id' => $stageId,
                'stage_name' => $stageName
            ]);

            // Extract custom fields
            $customFields = $data['customField'] ?? [];

            $eventType = $customFields['AFX1YsPB7QHBP50Ajs1Q'] ?? 'other';
            $eventDate = $customFields['kvDBYw8fixMftjWdF51g'] ?? null;
            $totalRevenue = $customFields['OwkEjGNrbE7Rq0TKBG3M'] ?? 0;
            $venueAddress = $customFields['nstR5hDlCQJ6jpsFzxi7'] ?? null;
            $services = $customFields['00cH1d6lq8m0U8tf3FHg'] ?? [];
            $photoHours = $customFields['T5nq3eiHUuXM0wFYNNg4'] ?? 0;
            $videoHours = $customFields['nHiHJxfNxRhvUfIu6oD6'] ?? 0;
            $droneServices = $customFields['iQOUEUruaZfPKln4sdKP'] ?? 'No';
            $eventTime = $customFields['Bz6tmEcB0S0pXupkha84'] ?? null;
            $contactName = $customFields['qpyukeOGutkXczPGJOyK'] ?? null;
            $notes = $customFields['xV2dxG35gDY1Vqb00Ql1'] ?? null;

            // Generate project name (handle both camelCase and snake_case from GHL)
            $firstName = $data['firstName'] ?? $data['first_name'] ?? 'Unknown';
            $lastName = $data['lastName'] ?? $data['last_name'] ?? '';
            $projectName = trim("$firstName $lastName - $eventType");

            // Check if client exists, create if not
            $client = $this->db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                // Create client from webhook data
                // Filter out empty tags to avoid PostgreSQL array literal errors
                $tagsArray = $data['tags'] ?? [];

                // Convert comma-separated string to array if needed
                if (is_string($tagsArray)) {
                    $tagsArray = array_map('trim', explode(',', $tagsArray));
                }

                if (is_array($tagsArray)) {
                    $tagsArray = array_filter($tagsArray, function($tag) {
                        return !empty($tag);
                    });
                }

                // Format as PostgreSQL array literal: {value1,value2}
                $tags = !empty($tagsArray) ? '{' . implode(',', array_map(function($tag) {
                    return '"' . addslashes($tag) . '"';
                }, array_values($tagsArray))) . '}' : null;

                $clientData = [
                    'ghl_contact_id' => $ghlContactId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'status' => 'active',
                    'lifecycle_stage' => 'customer',
                    'tags' => $tags,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $clientId = $this->db->insert('clients', $clientData);
                $this->logger->info('Created new client from project booking', [
                    'client_id' => $clientId,
                    'ghl_contact_id' => $ghlContactId
                ]);
            } else {
                $clientId = $client['id'];

                // Update lifecycle stage to customer
                $this->db->update('clients', [
                    'lifecycle_stage' => 'customer',
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $clientId]);
            }

            // Check if project already exists
            $existingProject = $this->db->queryOne(
                "SELECT id FROM projects WHERE client_id = ? AND project_name = ?",
                [$clientId, $projectName]
            );

            // Build metadata JSON
            $metadata = [
                'services' => is_array($services) ? $services : [$services],
                'photo_hours' => floatval($photoHours),
                'video_hours' => floatval($videoHours),
                'drone_services' => $droneServices,
                'event_time' => $eventTime,
                'contact_name' => $contactName
            ];

            if ($existingProject) {
                // Update existing project
                $updateData = [
                    'status' => $status,
                    'estimated_revenue' => floatval($totalRevenue), // This is estimated, not actual
                    'venue_address' => $venueAddress,
                    'ghl_pipeline_id' => $pipelineId,
                    'ghl_stage_id' => $stageId,
                    'ghl_stage_name' => $stageName,
                    'has_photography' => floatval($photoHours) > 0,
                    'has_videography' => floatval($videoHours) > 0,
                    'photo_hours' => floatval($photoHours),
                    'video_hours' => floatval($videoHours),
                    'metadata' => json_encode($metadata),
                    'notes' => $notes,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($eventDate) {
                    $updateData['event_date'] = $eventDate;
                }
                if ($ghlOpportunityId) {
                    $updateData['ghl_opportunity_id'] = $ghlOpportunityId;
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
                    'booking_date' => date('Y-m-d'),
                    'event_date' => $eventDate ?? date('Y-m-d'),
                    'event_type' => $eventType,
                    'venue_address' => $venueAddress,
                    'status' => $status,
                    'estimated_revenue' => floatval($totalRevenue), // Estimated, not actual
                    'actual_revenue' => 0, // Actual revenue comes from Stripe payments only
                    'ghl_opportunity_id' => $ghlOpportunityId,
                    'ghl_pipeline_id' => $pipelineId,
                    'ghl_stage_id' => $stageId,
                    'ghl_stage_name' => $stageName,
                    'has_photography' => floatval($photoHours) > 0,
                    'has_videography' => floatval($videoHours) > 0,
                    'photo_hours' => floatval($photoHours),
                    'video_hours' => floatval($videoHours),
                    'metadata' => json_encode($metadata),
                    'notes' => $notes,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $projectId = $this->db->insert('projects', $projectData);
                $this->logger->info('Created new project', [
                    'project_id' => $projectId,
                    'client_id' => $clientId,
                    'project_name' => $projectName,
                    'pipeline_id' => $pipelineId,
                    'opportunity_id' => $ghlOpportunityId
                ]);
            }

            // Refresh materialized views
            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'project_id' => $projectId,
                    'client_id' => $clientId,
                    'project_name' => $projectName
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
     *
     * Handles GHL Invoice.Paid webhook events
     * Expected payload:
     * - contactId: GHL contact ID
     * - altId: Invoice ID
     * - altType: "invoice"
     * - amount: Total invoice amount
     * - amountPaid: Amount paid
     * - name: Invoice/Customer name
     * - email: Customer email
     * - currency: Currency code (USD, etc.)
     * - status: "paid"
     * - createdAt: Invoice creation date
     * - updatedAt: Invoice update/payment date
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
            // Support both GHL invoice webhooks and generic payment webhooks
            $ghlContactId = $data['contactId'] ?? $data['contact_id'] ?? null;
            $invoiceId = $data['altId'] ?? $data['invoice_id'] ?? null;
            $amount = $data['amountPaid'] ?? $data['amount'] ?? 0;
            $totalAmount = $data['amount'] ?? $amount;
            $paymentDate = isset($data['updatedAt']) ? date('Y-m-d', strtotime($data['updatedAt'])) : ($data['payment_date'] ?? date('Y-m-d'));
            $paymentMethod = $data['payment_method'] ?? 'online';
            $status = $data['status'] ?? 'paid';

            $this->logger->info('Processing invoice payment webhook', [
                'contact_id' => $ghlContactId,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'total_amount' => $totalAmount,
                'status' => $status
            ]);

            if (!$ghlContactId || $amount <= 0) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required fields: contactId/contact_id and amount'
                ], 400);
            }

            // Find client by GHL contact ID
            $client = $this->db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                $this->logger->warning('Client not found, creating from invoice webhook', [
                    'ghl_contact_id' => $ghlContactId
                ]);

                // Create client from invoice data
                $clientData = [
                    'ghl_contact_id' => $ghlContactId,
                    'first_name' => $data['name'] ?? 'Unknown',
                    'last_name' => '',
                    'email' => $data['email'] ?? null,
                    'status' => 'active',
                    'lifecycle_stage' => 'customer',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $clientId = $this->db->insert('clients', $clientData);
                $this->logger->info('Created new client from invoice', ['client_id' => $clientId]);
            } else {
                $clientId = $client['id'];

                // Update client to customer if paying invoice
                $this->db->update('clients', [
                    'lifecycle_stage' => 'customer',
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $clientId]);
            }

            // Find most recent project for this client
            $project = $this->db->queryOne(
                "SELECT id, total_revenue FROM projects WHERE client_id = ? ORDER BY created_at DESC LIMIT 1",
                [$clientId]
            );

            if (!$project) {
                // Create a project if one doesn't exist
                $projectData = [
                    'client_id' => $clientId,
                    'project_name' => $data['name'] ?? 'Project from Invoice',
                    'booking_date' => $paymentDate,
                    'event_date' => $paymentDate,
                    'event_type' => 'other',
                    'status' => 'booked',
                    'total_revenue' => $amount,
                    'metadata' => json_encode(['ghl_invoice_id' => $invoiceId]),
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $projectId = $this->db->insert('projects', $projectData);
                $this->logger->info('Created new project from invoice', [
                    'project_id' => $projectId,
                    'amount' => $amount
                ]);
            } else {
                $projectId = $project['id'];

                // Update project revenue (add to existing revenue)
                $newTotalRevenue = floatval($project['total_revenue']) + floatval($amount);
                $this->db->update('projects', [
                    'total_revenue' => $newTotalRevenue,
                    'status' => 'booked',
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $projectId]);

                $this->logger->info('Updated project revenue', [
                    'project_id' => $projectId,
                    'previous_revenue' => $project['total_revenue'],
                    'payment_amount' => $amount,
                    'new_total_revenue' => $newTotalRevenue
                ]);
            }

            // Check if this invoice payment was already recorded
            if ($invoiceId) {
                $existingRevenue = $this->db->queryOne(
                    "SELECT id FROM revenue WHERE metadata->>'ghl_invoice_id' = ?",
                    [$invoiceId]
                );

                if ($existingRevenue) {
                    $this->logger->info('Invoice payment already recorded', [
                        'revenue_id' => $existingRevenue['id'],
                        'invoice_id' => $invoiceId
                    ]);

                    // Refresh views and return existing record
                    $this->refreshViews();

                    return $this->jsonResponse($response, [
                        'success' => true,
                        'data' => [
                            'revenue_id' => $existingRevenue['id'],
                            'project_id' => $projectId,
                            'already_recorded' => true
                        ]
                    ]);
                }
            }

            // Insert revenue record
            $revenueData = [
                'project_id' => $projectId,
                'client_id' => $clientId,
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'payment_type' => $amount >= $totalAmount ? 'full' : 'deposit',
                'status' => 'completed',
                'metadata' => json_encode([
                    'ghl_invoice_id' => $invoiceId,
                    'currency' => $data['currency'] ?? 'USD',
                    'total_invoice_amount' => $totalAmount,
                    'amount_paid' => $amount
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $revenueId = $this->db->insert('revenue', $revenueData);

            $this->logger->info('Recorded invoice payment', [
                'revenue_id' => $revenueId,
                'project_id' => $projectId,
                'client_id' => $clientId,
                'invoice_id' => $invoiceId,
                'amount' => $amount
            ]);

            // Refresh materialized views
            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'revenue_id' => $revenueId,
                    'project_id' => $projectId,
                    'client_id' => $clientId,
                    'amount' => $amount
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing invoice payment webhook', [
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
     * Receive inquiry/lead webhook
     * POST /api/webhooks/inquiries
     *
     * Expected GHL payload with custom fields:
     * - id: GHL contact ID
     * - firstName, lastName, email, phone: Standard fields
     * - source: Lead source
     * - dateAdded: When contact was created
     * - type: "lead" for inquiries
     * - customField[AFX1YsPB7QHBP50Ajs1Q]: Event Type
     * - customField[kvDBYw8fixMftjWdF51g]: Event Date
     * - customField[OwkEjGNrbE7Rq0TKBG3M]: Estimated Budget
     * - customField[xV2dxG35gDY1Vqb00Ql1]: Project Notes
     * - customField[nstR5hDlCQJ6jpsFzxi7]: Event Location
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
            // GHL contact ID (can be 'id' or 'contact_id')
            $ghlContactId = $data['id'] ?? $data['contact_id'] ?? null;
            $source = $data['source'] ?? 'website';
            $status = $data['status'] ?? 'new';

            if (!$ghlContactId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required field: id or contact_id'
                ], 400);
            }

            // Extract custom fields from GHL payload
            $customFields = $data['customField'] ?? [];

            // Map GHL custom field IDs to values
            $eventType = $customFields['AFX1YsPB7QHBP50Ajs1Q'] ?? null;
            $eventDate = $customFields['kvDBYw8fixMftjWdF51g'] ?? null;
            $budget = $customFields['OwkEjGNrbE7Rq0TKBG3M'] ?? null;
            $notes = $customFields['xV2dxG35gDY1Vqb00Ql1'] ?? null;
            $location = $customFields['nstR5hDlCQJ6jpsFzxi7'] ?? null;

            // Find or create client
            $client = $this->db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                // Create new client
                // Filter out empty tags to avoid PostgreSQL array literal errors
                $tagsArray = $data['tags'] ?? [];

                // Convert comma-separated string to array if needed
                if (is_string($tagsArray)) {
                    $tagsArray = array_map('trim', explode(',', $tagsArray));
                }

                if (is_array($tagsArray)) {
                    $tagsArray = array_filter($tagsArray, function($tag) {
                        return !empty($tag);
                    });
                }

                // Format as PostgreSQL array literal: {value1,value2}
                $tags = !empty($tagsArray) ? '{' . implode(',', array_map(function($tag) {
                    return '"' . addslashes($tag) . '"';
                }, array_values($tagsArray))) . '}' : null;

                $clientData = [
                    'ghl_contact_id' => $ghlContactId,
                    'first_name' => $data['firstName'] ?? $data['first_name'] ?? 'Unknown',
                    'last_name' => $data['lastName'] ?? $data['last_name'] ?? '',
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'lead_source' => $source,
                    'status' => 'active',
                    'lifecycle_stage' => 'lead',
                    'tags' => $tags,
                    'created_at' => date('Y-m-d H:i:s'),
                    'first_inquiry_date' => date('Y-m-d')
                ];

                $clientId = $this->db->insert('clients', $clientData);

                $this->logger->info('Created new client from inquiry', [
                    'client_id' => $clientId,
                    'ghl_contact_id' => $ghlContactId
                ]);
            } else {
                $clientId = $client['id'];
            }

            // Check if inquiry already exists for this client
            $inquiryDate = isset($data['dateAdded']) ? date('Y-m-d', strtotime($data['dateAdded'])) : date('Y-m-d');
            $existingInquiry = $this->db->queryOne(
                "SELECT id FROM inquiries WHERE client_id = ? AND inquiry_date = ?",
                [$clientId, $inquiryDate]
            );

            if ($existingInquiry) {
                $this->logger->info('Inquiry already exists, skipping', [
                    'inquiry_id' => $existingInquiry['id'],
                    'client_id' => $clientId
                ]);

                return $this->jsonResponse($response, [
                    'success' => true,
                    'data' => [
                        'inquiry_id' => $existingInquiry['id'],
                        'client_id' => $clientId,
                        'already_exists' => true
                    ]
                ]);
            }

            // Insert inquiry
            $inquiryData = [
                'client_id' => $clientId,
                'inquiry_date' => $data['dateAdded'] ? date('Y-m-d', strtotime($data['dateAdded'])) : date('Y-m-d'),
                'source' => $source,
                'event_type' => $eventType,
                'event_date' => $eventDate,
                'budget' => $budget ? floatval($budget) : null,
                'status' => $status,
                'outcome' => $data['outcome'] ?? null,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $inquiryId = $this->db->insert('inquiries', $inquiryData);

            $this->logger->info('Recorded new inquiry', [
                'inquiry_id' => $inquiryId,
                'client_id' => $clientId,
                'event_type' => $eventType,
                'budget' => $budget
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
     * Receive consultation/appointment webhook
     * POST /api/webhooks/consultations
     *
     * Expected GHL payload when appointment is created:
     * - contactId: GHL contact ID
     * - title: Appointment title/type
     * - startTime: Appointment start time (ISO format)
     * - endTime: Appointment end time (ISO format)
     * - status: Appointment status (scheduled, confirmed, cancelled)
     * - notes: Appointment notes
     */
    public function receiveConsultation(Request $request, Response $response): Response
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
            $ghlContactId = $data['contactId'] ?? $data['contact_id'] ?? null;

            if (!$ghlContactId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required field: contactId'
                ], 400);
            }

            // Find client
            $client = $this->db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                // Create client if doesn't exist
                $clientData = [
                    'ghl_contact_id' => $ghlContactId,
                    'first_name' => $data['contact']['firstName'] ?? 'Unknown',
                    'last_name' => $data['contact']['lastName'] ?? '',
                    'email' => $data['contact']['email'] ?? null,
                    'phone' => $data['contact']['phone'] ?? null,
                    'status' => 'active',
                    'lifecycle_stage' => 'lead',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $clientId = $this->db->insert('clients', $clientData);

                $this->logger->info('Created new client from consultation webhook', [
                    'client_id' => $clientId,
                    'ghl_contact_id' => $ghlContactId
                ]);
            } else {
                $clientId = $client['id'];
            }

            // Check if consultation already exists
            $consultationDate = isset($data['startTime']) ? date('Y-m-d H:i:s', strtotime($data['startTime'])) : date('Y-m-d H:i:s');

            $existingConsultation = $this->db->queryOne(
                "SELECT id FROM consultations WHERE client_id = ? AND consultation_date = ?",
                [$clientId, $consultationDate]
            );

            if ($existingConsultation) {
                // Update existing consultation
                $updateData = [
                    'consultation_type' => $data['title'] ?? 'consultation',
                    'attended' => in_array(strtolower($data['status'] ?? ''), ['confirmed', 'completed']),
                    'outcome' => $data['status'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $this->db->update('consultations', $updateData, ['id' => $existingConsultation['id']]);
                $consultationId = $existingConsultation['id'];

                $this->logger->info('Updated existing consultation', [
                    'consultation_id' => $consultationId
                ]);
            } else {
                // Create new consultation
                $consultationData = [
                    'client_id' => $clientId,
                    'consultation_date' => $consultationDate,
                    'consultation_type' => $data['title'] ?? 'consultation',
                    'attended' => in_array(strtolower($data['status'] ?? ''), ['confirmed', 'completed']),
                    'outcome' => $data['status'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'metadata' => json_encode([
                        'ghl_appointment_id' => $data['id'] ?? null,
                        'calendar_id' => $data['calendarId'] ?? null,
                        'end_time' => $data['endTime'] ?? null
                    ]),
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $consultationId = $this->db->insert('consultations', $consultationData);

                $this->logger->info('Created new consultation', [
                    'consultation_id' => $consultationId,
                    'client_id' => $clientId,
                    'type' => $consultationData['consultation_type']
                ]);
            }

            // Refresh materialized views
            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'consultation_id' => $consultationId,
                    'client_id' => $clientId
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing consultation webhook', [
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
     * Receive Stripe payment webhook
     * POST /api/webhooks/stripe/payment
     *
     * Handles Stripe payment_intent.succeeded events
     * Expected payload (Stripe webhook format):
     * - id: Event ID
     * - type: "payment_intent.succeeded"
     * - data.object.id: Payment Intent ID
     * - data.object.amount: Amount in cents
     * - data.object.currency: Currency code
     * - data.object.customer: Stripe customer ID
     * - data.object.metadata: Custom metadata (should include ghl_contact_id, project_id)
     */
    public function receiveStripePayment(Request $request, Response $response): Response
    {
        $data = $this->parsePayload($request);
        if (!$data) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid payload'
            ], 400);
        }

        try {
            $this->logger->info('Processing Stripe payment webhook', [
                'event_type' => $data['type'] ?? 'unknown',
                'event_id' => $data['id'] ?? null
            ]);

            // Extract payment data from Stripe event
            $eventType = $data['type'] ?? '';
            $paymentObject = $data['data']['object'] ?? $data;

            if ($eventType !== 'payment_intent.succeeded' && $eventType !== '') {
                // Not a payment success event, acknowledge but skip
                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Event type not handled: ' . $eventType
                ]);
            }

            $stripePaymentId = $paymentObject['id'] ?? null;
            $stripeCustomerId = $paymentObject['customer'] ?? null;
            $amountCents = $paymentObject['amount'] ?? 0;
            $amount = $amountCents / 100; // Convert cents to dollars
            $currency = strtoupper($paymentObject['currency'] ?? 'USD');
            $metadata = $paymentObject['metadata'] ?? [];

            // Get GHL contact ID from metadata or try to find by Stripe customer
            $ghlContactId = $metadata['ghl_contact_id'] ?? $metadata['contact_id'] ?? null;
            $projectId = $metadata['project_id'] ?? null;

            if (!$stripePaymentId || $amount <= 0) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required fields: payment ID and amount'
                ], 400);
            }

            // Check if this payment was already recorded
            $existingPayment = $this->db->queryOne(
                "SELECT id FROM revenue WHERE stripe_payment_id = ?",
                [$stripePaymentId]
            );

            if ($existingPayment) {
                $this->logger->info('Stripe payment already recorded', [
                    'revenue_id' => $existingPayment['id'],
                    'stripe_payment_id' => $stripePaymentId
                ]);

                return $this->jsonResponse($response, [
                    'success' => true,
                    'data' => [
                        'revenue_id' => $existingPayment['id'],
                        'already_recorded' => true
                    ]
                ]);
            }

            // Find client by GHL contact ID or Stripe customer ID
            $client = null;
            if ($ghlContactId) {
                $client = $this->db->queryOne(
                    "SELECT id FROM clients WHERE ghl_contact_id = ?",
                    [$ghlContactId]
                );
            }
            if (!$client && $stripeCustomerId) {
                $client = $this->db->queryOne(
                    "SELECT id FROM clients WHERE stripe_customer_id = ?",
                    [$stripeCustomerId]
                );
            }

            if (!$client) {
                $this->logger->warning('Client not found for Stripe payment', [
                    'ghl_contact_id' => $ghlContactId,
                    'stripe_customer_id' => $stripeCustomerId,
                    'stripe_payment_id' => $stripePaymentId
                ]);

                // Create a placeholder client if we have customer info
                if ($stripeCustomerId || $ghlContactId) {
                    $clientData = [
                        'ghl_contact_id' => $ghlContactId ?? 'stripe_' . $stripeCustomerId,
                        'stripe_customer_id' => $stripeCustomerId,
                        'first_name' => $metadata['customer_name'] ?? 'Stripe Customer',
                        'email' => $metadata['customer_email'] ?? null,
                        'status' => 'active',
                        'lifecycle_stage' => 'customer',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $clientId = $this->db->insert('clients', $clientData);
                    $this->logger->info('Created client from Stripe payment', ['client_id' => $clientId]);
                } else {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'error' => 'Cannot identify client for payment'
                    ], 400);
                }
            } else {
                $clientId = $client['id'];

                // Update client with Stripe customer ID if not set
                if ($stripeCustomerId) {
                    $this->db->execute(
                        "UPDATE clients SET stripe_customer_id = ?, lifecycle_stage = 'customer', updated_at = NOW() WHERE id = ? AND stripe_customer_id IS NULL",
                        [$stripeCustomerId, $clientId]
                    );
                }
            }

            // Find project or use provided project_id
            $project = null;
            if ($projectId) {
                $project = $this->db->queryOne("SELECT id, actual_revenue FROM projects WHERE id = ?", [$projectId]);
            }
            if (!$project) {
                $project = $this->db->queryOne(
                    "SELECT id, actual_revenue FROM projects WHERE client_id = ? ORDER BY created_at DESC LIMIT 1",
                    [$clientId]
                );
            }

            if ($project) {
                $projectId = $project['id'];

                // Update project actual_revenue
                $newActualRevenue = floatval($project['actual_revenue'] ?? 0) + $amount;
                $this->db->update('projects', [
                    'actual_revenue' => $newActualRevenue,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $projectId]);
            }

            // Insert revenue record
            $revenueData = [
                'project_id' => $projectId,
                'client_id' => $clientId,
                'payment_date' => date('Y-m-d'),
                'amount' => $amount,
                'payment_method' => 'credit_card',
                'payment_type' => 'deposit', // Will be determined by n8n workflow
                'status' => 'completed',
                'stripe_payment_id' => $stripePaymentId,
                'stripe_customer_id' => $stripeCustomerId,
                'metadata' => json_encode([
                    'currency' => $currency,
                    'stripe_event_id' => $data['id'] ?? null,
                    'metadata' => $metadata
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $revenueId = $this->db->insert('revenue', $revenueData);

            $this->logger->info('Recorded Stripe payment', [
                'revenue_id' => $revenueId,
                'project_id' => $projectId,
                'client_id' => $clientId,
                'amount' => $amount,
                'stripe_payment_id' => $stripePaymentId
            ]);

            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'revenue_id' => $revenueId,
                    'project_id' => $projectId,
                    'client_id' => $clientId,
                    'amount' => $amount
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing Stripe payment webhook', [
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
     * Receive Stripe refund webhook
     * POST /api/webhooks/stripe/refund
     *
     * Handles charge.refunded events
     */
    public function receiveStripeRefund(Request $request, Response $response): Response
    {
        $data = $this->parsePayload($request);
        if (!$data) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid payload'
            ], 400);
        }

        try {
            $this->logger->info('Processing Stripe refund webhook', [
                'event_type' => $data['type'] ?? 'unknown'
            ]);

            $refundObject = $data['data']['object'] ?? $data;

            $stripeRefundId = $refundObject['id'] ?? null;
            $stripeChargeId = $refundObject['charge'] ?? null;
            $stripePaymentId = $refundObject['payment_intent'] ?? null;
            $amountCents = $refundObject['amount'] ?? 0;
            $refundAmount = $amountCents / 100;
            $reason = $refundObject['reason'] ?? 'requested_by_customer';

            if (!$stripeRefundId || $refundAmount <= 0) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Missing required fields'
                ], 400);
            }

            // Check if this refund was already recorded
            $existingRefund = $this->db->queryOne(
                "SELECT id FROM revenue WHERE stripe_refund_id = ?",
                [$stripeRefundId]
            );

            if ($existingRefund) {
                return $this->jsonResponse($response, [
                    'success' => true,
                    'data' => ['revenue_id' => $existingRefund['id'], 'already_recorded' => true]
                ]);
            }

            // Find original payment
            $originalPayment = null;
            if ($stripePaymentId) {
                $originalPayment = $this->db->queryOne(
                    "SELECT id, project_id, client_id, amount FROM revenue WHERE stripe_payment_id = ?",
                    [$stripePaymentId]
                );
            }

            if (!$originalPayment) {
                $this->logger->warning('Original payment not found for refund', [
                    'stripe_refund_id' => $stripeRefundId,
                    'stripe_payment_id' => $stripePaymentId
                ]);

                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Original payment not found'
                ], 404);
            }

            // Insert refund as negative revenue
            $refundData = [
                'project_id' => $originalPayment['project_id'],
                'client_id' => $originalPayment['client_id'],
                'payment_date' => date('Y-m-d'),
                'amount' => -$refundAmount, // Negative amount for refund
                'payment_method' => 'credit_card',
                'payment_type' => 'refund',
                'status' => 'refunded',
                'stripe_refund_id' => $stripeRefundId,
                'stripe_payment_id' => $stripePaymentId,
                'refund_amount' => $refundAmount,
                'refund_date' => date('Y-m-d'),
                'refund_reason' => $reason,
                'metadata' => json_encode([
                    'original_revenue_id' => $originalPayment['id'],
                    'stripe_charge_id' => $stripeChargeId,
                    'stripe_event_id' => $data['id'] ?? null
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $refundId = $this->db->insert('revenue', $refundData);

            // Update project actual_revenue
            if ($originalPayment['project_id']) {
                $project = $this->db->queryOne(
                    "SELECT actual_revenue FROM projects WHERE id = ?",
                    [$originalPayment['project_id']]
                );
                if ($project) {
                    $newActualRevenue = floatval($project['actual_revenue']) - $refundAmount;
                    $this->db->update('projects', [
                        'actual_revenue' => $newActualRevenue,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], ['id' => $originalPayment['project_id']]);
                }
            }

            $this->logger->info('Recorded Stripe refund', [
                'refund_id' => $refundId,
                'amount' => $refundAmount,
                'original_payment_id' => $originalPayment['id']
            ]);

            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'refund_id' => $refundId,
                    'amount' => $refundAmount,
                    'original_payment_id' => $originalPayment['id']
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing Stripe refund webhook', [
                'error' => $e->getMessage()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Server error'
            ], 500);
        }
    }

    /**
     * Receive delivery status update
     * POST /api/webhooks/deliveries
     */
    public function receiveDeliveryUpdate(Request $request, Response $response): Response
    {
        $data = $this->parsePayload($request);
        if (!$data) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid payload'], 400);
        }

        try {
            $ghlContactId = $data['contact_id'] ?? $data['id'] ?? null;
            $ghlOpportunityId = $data['opportunity_id'] ?? null;

            // Find project
            $project = null;
            if ($ghlOpportunityId) {
                $project = $this->db->queryOne(
                    "SELECT id FROM projects WHERE ghl_opportunity_id = ?",
                    [$ghlOpportunityId]
                );
            }
            if (!$project && $ghlContactId) {
                $client = $this->db->queryOne(
                    "SELECT id FROM clients WHERE ghl_contact_id = ?",
                    [$ghlContactId]
                );
                if ($client) {
                    $project = $this->db->queryOne(
                        "SELECT id FROM projects WHERE client_id = ? ORDER BY created_at DESC LIMIT 1",
                        [$client['id']]
                    );
                }
            }

            if (!$project) {
                return $this->jsonResponse($response, ['success' => false, 'error' => 'Project not found'], 404);
            }

            $projectId = $project['id'];

            // Check if delivery record exists
            $delivery = $this->db->queryOne(
                "SELECT id FROM project_deliveries WHERE project_id = ?",
                [$projectId]
            );

            // Map GHL custom fields to delivery fields
            $customFields = $data['customField'] ?? [];
            $deliveryData = [
                'raw_photos_url' => $customFields['bp5oCoPifWXOOcN7Z79F'] ?? $data['raw_photos_url'] ?? null,
                'raw_video_url' => $customFields['K3fNomA8tFU3wShooTTh'] ?? $data['raw_video_url'] ?? null,
                'final_photos_url' => $customFields['epv4xKKDDS1HqbiRz7Wc'] ?? $data['final_photos_url'] ?? null,
                'final_video_url' => $customFields['QjjCsBRRNu0FlD0ocEJk'] ?? $data['final_video_url'] ?? null,
                'delivery_status' => $data['delivery_status'] ?? 'pending',
                'photographer_notes' => $customFields['Moa0uJbJTUs3gi4d8zw1'] ?? $data['photographer_notes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Set delivery dates if URLs provided
            if (!empty($deliveryData['raw_photos_url'])) {
                $deliveryData['raw_photos_delivered_date'] = $data['raw_photos_date'] ?? date('Y-m-d');
            }
            if (!empty($deliveryData['final_photos_url'])) {
                $deliveryData['final_photos_delivered_date'] = $data['final_photos_date'] ?? date('Y-m-d');
            }
            if (!empty($deliveryData['raw_video_url'])) {
                $deliveryData['raw_video_delivered_date'] = $data['raw_video_date'] ?? date('Y-m-d');
            }
            if (!empty($deliveryData['final_video_url'])) {
                $deliveryData['final_video_delivered_date'] = $data['final_video_date'] ?? date('Y-m-d');
            }

            if ($delivery) {
                $this->db->update('project_deliveries', $deliveryData, ['id' => $delivery['id']]);
                $deliveryId = $delivery['id'];
            } else {
                $deliveryData['project_id'] = $projectId;
                $deliveryData['created_at'] = date('Y-m-d H:i:s');
                $deliveryId = $this->db->insert('project_deliveries', $deliveryData);
            }

            $this->logger->info('Updated delivery status', [
                'delivery_id' => $deliveryId,
                'project_id' => $projectId,
                'status' => $deliveryData['delivery_status']
            ]);

            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => ['delivery_id' => $deliveryId, 'project_id' => $projectId]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing delivery webhook', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Server error'], 500);
        }
    }

    /**
     * Receive client review
     * POST /api/webhooks/reviews
     */
    public function receiveReview(Request $request, Response $response): Response
    {
        $data = $this->parsePayload($request);
        if (!$data) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid payload'], 400);
        }

        try {
            $ghlContactId = $data['contact_id'] ?? $data['id'] ?? null;

            if (!$ghlContactId) {
                return $this->jsonResponse($response, ['success' => false, 'error' => 'Missing contact_id'], 400);
            }

            // Find client
            $client = $this->db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                return $this->jsonResponse($response, ['success' => false, 'error' => 'Client not found'], 404);
            }

            $clientId = $client['id'];

            // Find most recent project
            $project = $this->db->queryOne(
                "SELECT id FROM projects WHERE client_id = ? ORDER BY event_date DESC LIMIT 1",
                [$clientId]
            );

            // Map star ratings (convert "X stars" to integer)
            $parseRating = function($rating) {
                if (is_numeric($rating)) return intval($rating);
                if (preg_match('/(\d+)\s*star/i', $rating, $matches)) {
                    return intval($matches[1]);
                }
                return null;
            };

            $reviewData = [
                'client_id' => $clientId,
                'project_id' => $project ? $project['id'] : null,
                'overall_rating' => $parseRating($data['overall_rating'] ?? null),
                'photographer_rating' => $parseRating($data['photographer_rating'] ?? null),
                'videographer_rating' => $parseRating($data['videographer_rating'] ?? null),
                'nps_score' => isset($data['nps_score']) ? intval($data['nps_score']) : null,
                'would_recommend' => isset($data['would_recommend']) ?
                    (strtolower($data['would_recommend']) === 'yes' || $data['would_recommend'] === true) : null,
                'review_text' => $data['review_text'] ?? null,
                'review_platform' => $data['review_platform'] ?? 'direct',
                'review_link' => $data['review_link'] ?? null,
                'review_date' => $data['review_date'] ?? date('Y-m-d'),
                'ghl_synced' => true,
                'ghl_sync_date' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $reviewId = $this->db->insert('client_reviews', $reviewData);

            $this->logger->info('Recorded client review', [
                'review_id' => $reviewId,
                'client_id' => $clientId,
                'overall_rating' => $reviewData['overall_rating'],
                'nps_score' => $reviewData['nps_score']
            ]);

            $this->refreshViews();

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => ['review_id' => $reviewId, 'client_id' => $clientId]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing review webhook', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Server error'], 500);
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
