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

    /**
     * Receive project/booking webhook
     * POST /api/webhooks/projects
     *
     * Expected GHL payload when contact moves to "customer" or opportunity status changes to "booked":
     * - id: GHL contact ID
     * - firstName, lastName, email, phone: Standard fields
     * - customField[AFX1YsPB7QHBP50Ajs1Q]: Event Type
     * - customField[kvDBYw8fixMftjWdF51g]: Event Date
     * - customField[OwkEjGNrbE7Rq0TKBG3M]: Total Value/Budget
     * - customField[nstR5hDlCQJ6jpsFzxi7]: Venue Address
     * - customField[00cH1d6lq8m0U8tf3FHg]: Services (array)
     * - customField[T5nq3eiHUuXM0wFYNNg4]: Photography Hours
     * - customField[nHiHJxfNxRhvUfIu6oD6]: Videography Hours
     * - customField[iQOUEUruaZfPKln4sdKP]: Drone Services
     * - customField[Bz6tmEcB0S0pXupkha84]: Event Start Time
     * - customField[qpyukeOGutkXczPGJOyK]: Contact Name
     * - customField[xV2dxG35gDY1Vqb00Ql1]: Project Notes
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
                    'total_revenue' => floatval($totalRevenue),
                    'venue_address' => $venueAddress,
                    'metadata' => json_encode($metadata),
                    'notes' => $notes,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($eventDate) {
                    $updateData['event_date'] = $eventDate;
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
                    'total_revenue' => floatval($totalRevenue),
                    'metadata' => json_encode($metadata),
                    'notes' => $notes,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $projectId = $this->db->insert('projects', $projectData);
                $this->logger->info('Created new project', [
                    'project_id' => $projectId,
                    'client_id' => $clientId,
                    'project_name' => $projectName
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
            $existingInquiry = $this->db->queryOne(
                "SELECT id FROM inquiries WHERE client_id = ? AND inquiry_date = ?",
                [$clientId, $data['dateAdded'] ? date('Y-m-d', strtotime($data['dateAdded'])) : date('Y-m-d')]
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
