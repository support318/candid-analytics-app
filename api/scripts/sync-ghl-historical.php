#!/usr/bin/env php
<?php

/**
 * Historical GHL Data Sync Script
 * Fetches all historical data from GoHighLevel and populates the analytics database
 *
 * Usage:
 *   php sync-ghl-historical.php [--dry-run]
 */

declare(strict_types=1);

// Load dependencies
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Parse CLI arguments
$dryRun = in_array('--dry-run', $argv);

// Configuration
$ghlApiKey = $_ENV['GHL_API_KEY'] ?? '';
$ghlLocationId = $_ENV['GHL_LOCATION_ID'] ?? '';

if (empty($ghlApiKey) || empty($ghlLocationId)) {
    echo "❌ Error: GHL_API_KEY and GHL_LOCATION_ID environment variables are required\n";
    exit(1);
}

// Initialize database connection
try {
    $db = new \CandidAnalytics\Services\Database(
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD']
    );
    echo "✅ Connected to database\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Initialize GHL API client
class GHLClient {
    private $apiKey;
    private $locationId;
    private $baseUrl = 'https://services.leadconnectorhq.com';

    public function __construct(string $apiKey, string $locationId) {
        $this->apiKey = $apiKey;
        $this->locationId = $locationId;
    }

    private function request(string $method, string $endpoint, array $params = []): array {
        $url = $this->baseUrl . $endpoint;

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Version: 2021-07-28',
                'Accept: application/json'
            ],
            CURLOPT_CUSTOMREQUEST => $method
        ]);

        if ($method === 'POST' && !empty($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
                curl_getopt($ch, CURLOPT_HTTPHEADER),
                ['Content-Type: application/json']
            ));
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            throw new Exception("GHL API request failed: $endpoint (Status: $statusCode)");
        }

        return json_decode($response, true) ?? [];
    }

    public function getContacts(int $limit = 100, string $startAfter = ''): array {
        $params = [
            'locationId' => $this->locationId,
            'limit' => $limit
        ];

        if (!empty($startAfter)) {
            $params['startAfter'] = $startAfter;
        }

        return $this->request('GET', '/contacts/', $params);
    }

    public function getOpportunities(string $pipelineId = '', int $limit = 100, string $startAfter = ''): array {
        $params = [
            'location_id' => $this->locationId,
            'limit' => $limit
        ];

        if (!empty($pipelineId)) {
            $params['pipelineId'] = $pipelineId;
        }

        if (!empty($startAfter)) {
            $params['startAfterId'] = $startAfter;
        }

        return $this->request('GET', '/opportunities/search', $params);
    }

    public function getAppointments(string $startDate, string $endDate): array {
        $params = [
            'locationId' => $this->locationId,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];

        return $this->request('GET', '/calendars/events', $params);
    }
}

$ghl = new GHLClient($ghlApiKey, $ghlLocationId);

// Statistics
$stats = [
    'clients_created' => 0,
    'clients_updated' => 0,
    'projects_created' => 0,
    'inquiries_created' => 0,
    'consultations_created' => 0,
    'errors' => []
];

echo "🚀 Starting GHL Historical Data Sync\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no data will be saved)" : "LIVE") . "\n";
echo "=" . str_repeat("=", 60) . "\n\n";

/**
 * Pipeline Stage Mapping
 */
$stageMapping = [
    'lead' => 'lead',
    'planning' => 'booked',
    'planning (booked)' => 'booked',
    'booked' => 'booked',
    'photo editing' => 'in-progress',
    'video editing' => 'in-progress',
    'delivery' => 'completed',
    'delivery (archived)' => 'completed',
    'archived' => 'completed',
    'cancelled' => 'cancelled',
    'lost' => 'cancelled'
];

/**
 * Sync Contacts → Clients
 */
echo "📇 Fetching contacts from GHL...\n";
$contactsProcessed = 0;
$startAfter = '';

do {
    try {
        $response = $ghl->getContacts(100, $startAfter);
        $contacts = $response['contacts'] ?? [];

        foreach ($contacts as $contact) {
            $contactsProcessed++;
            $ghlContactId = $contact['id'];

            // Check if client already exists
            $existingClient = $db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            // Prepare tags (PostgreSQL array format)
            $tags = null;
            if (!empty($contact['tags'])) {
                $tagArray = is_array($contact['tags']) ? $contact['tags'] : explode(',', $contact['tags']);
                $tagArray = array_filter(array_map('trim', $tagArray));
                if (!empty($tagArray)) {
                    $tags = '{' . implode(',', array_map(function($tag) {
                        return '"' . addslashes($tag) . '"';
                    }, $tagArray)) . '}';
                }
            }

            $clientData = [
                'ghl_contact_id' => $ghlContactId,
                'first_name' => $contact['firstName'] ?? $contact['first_name'] ?? 'Unknown',
                'last_name' => $contact['lastName'] ?? $contact['last_name'] ?? '',
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'lead_source' => $contact['source'] ?? 'unknown',
                'status' => 'active',
                'lifecycle_stage' => strtolower($contact['type'] ?? 'lead'),
                'tags' => $tags,
                'first_inquiry_date' => isset($contact['dateAdded']) ? date('Y-m-d', strtotime($contact['dateAdded'])) : date('Y-m-d'),
                'created_at' => isset($contact['dateAdded']) ? date('Y-m-d H:i:s', strtotime($contact['dateAdded'])) : date('Y-m-d H:i:s')
            ];

            if (!$dryRun) {
                if ($existingClient) {
                    unset($clientData['created_at']); // Don't update created_at
                    $clientData['updated_at'] = date('Y-m-d H:i:s');
                    $db->update('clients', $clientData, ['id' => $existingClient['id']]);
                    $stats['clients_updated']++;
                } else {
                    $db->insert('clients', $clientData);
                    $stats['clients_created']++;
                }
            } else {
                echo "  [DRY RUN] Would " . ($existingClient ? "update" : "create") . " client: " . $clientData['first_name'] . " " . $clientData['last_name'] . "\n";
                $stats['clients_created']++;
            }
        }

        // Get next page cursor
        $startAfter = $response['meta']['nextPageCursor'] ?? '';

        echo "  Processed $contactsProcessed contacts...\n";

    } catch (Exception $e) {
        $stats['errors'][] = "Contact sync error: " . $e->getMessage();
        echo "  ⚠️  Error: " . $e->getMessage() . "\n";
        break;
    }
} while (!empty($startAfter));

echo "✅ Contacts sync complete: {$stats['clients_created']} created, {$stats['clients_updated']} updated\n\n";

/**
 * Sync Opportunities → Projects
 */
echo "📊 Fetching opportunities from GHL...\n";
$opportunitiesProcessed = 0;
$startAfter = '';

do {
    try {
        $response = $ghl->getOpportunities('', 100, $startAfter);
        $opportunities = $response['opportunities'] ?? [];

        foreach ($opportunities as $opp) {
            $opportunitiesProcessed++;
            $ghlContactId = $opp['contact']['id'] ?? null;

            if (!$ghlContactId) {
                continue;
            }

            // Find client
            $client = $db->queryOne(
                "SELECT id FROM clients WHERE ghl_contact_id = ?",
                [$ghlContactId]
            );

            if (!$client) {
                echo "  ⚠️  Client not found for opportunity: {$opp['name']}\n";
                continue;
            }

            // Map stage to status
            $rawStatus = strtolower($opp['status'] ?? $opp['pipelineStage'] ?? 'lead');
            $status = $stageMapping[$rawStatus] ?? 'lead';

            // Determine event type from opportunity name or custom field
            $eventType = 'other';
            $oppName = strtolower($opp['name'] ?? '');
            if (strpos($oppName, 'wedding') !== false) $eventType = 'wedding';
            elseif (strpos($oppName, 'portrait') !== false) $eventType = 'portrait';
            elseif (strpos($oppName, 'event') !== false) $eventType = 'event';
            elseif (strpos($oppName, 'corporate') !== false) $eventType = 'corporate';
            elseif (strpos($oppName, 'real estate') !== false || strpos($oppName, 'real-estate') !== false) $eventType = 'real-estate';

            $projectData = [
                'client_id' => $client['id'],
                'project_name' => $opp['name'] ?? 'Unknown Project',
                'booking_date' => isset($opp['dateAdded']) ? date('Y-m-d', strtotime($opp['dateAdded'])) : date('Y-m-d'),
                'event_date' => isset($opp['customFields']['kvDBYw8fixMftjWdF51g']) ? date('Y-m-d', strtotime($opp['customFields']['kvDBYw8fixMftjWdF51g'])) : date('Y-m-d'),
                'event_type' => $eventType,
                'status' => $status,
                'total_revenue' => floatval($opp['monetaryValue'] ?? 0),
                'metadata' => json_encode([
                    'ghl_opportunity_id' => $opp['id'],
                    'pipeline_id' => $opp['pipelineId'] ?? null,
                    'stage_id' => $opp['pipelineStageId'] ?? null
                ]),
                'created_at' => isset($opp['dateAdded']) ? date('Y-m-d H:i:s', strtotime($opp['dateAdded'])) : date('Y-m-d H:i:s')
            ];

            if (!$dryRun) {
                // Check if project already exists
                $existingProject = $db->queryOne(
                    "SELECT id FROM projects WHERE client_id = ? AND project_name = ?",
                    [$client['id'], $projectData['project_name']]
                );

                if (!$existingProject) {
                    $db->insert('projects', $projectData);
                    $stats['projects_created']++;
                }
            } else {
                echo "  [DRY RUN] Would create project: " . $projectData['project_name'] . " (Status: $status)\n";
                $stats['projects_created']++;
            }
        }

        // Get next page
        $startAfter = $response['meta']['nextStartAfterId'] ?? '';

        echo "  Processed $opportunitiesProcessed opportunities...\n";

    } catch (Exception $e) {
        $stats['errors'][] = "Opportunity sync error: " . $e->getMessage();
        echo "  ⚠️  Error: " . $e->getMessage() . "\n";
        break;
    }
} while (!empty($startAfter));

echo "✅ Opportunities sync complete: {$stats['projects_created']} projects created\n\n";

/**
 * Sync Calendar Appointments → Consultations
 */
echo "📅 Fetching calendar appointments from GHL...\n";
try {
    // Fetch last 12 months of appointments
    $startDate = date('Y-m-d', strtotime('-12 months'));
    $endDate = date('Y-m-d', strtotime('+6 months')); // Include future appointments

    $response = $ghl->getAppointments($startDate, $endDate);
    $appointments = $response['events'] ?? [];

    foreach ($appointments as $appt) {
        $ghlContactId = $appt['contactId'] ?? null;

        if (!$ghlContactId) {
            continue;
        }

        // Find client
        $client = $db->queryOne(
            "SELECT id FROM clients WHERE ghl_contact_id = ?",
            [$ghlContactId]
        );

        if (!$client) {
            continue;
        }

        $consultationData = [
            'client_id' => $client['id'],
            'consultation_date' => date('Y-m-d H:i:s', strtotime($appt['startTime'])),
            'consultation_type' => $appt['title'] ?? 'consultation',
            'attended' => ($appt['status'] ?? 'scheduled') === 'confirmed',
            'outcome' => $appt['status'] ?? null,
            'notes' => $appt['notes'] ?? null,
            'created_at' => isset($appt['dateAdded']) ? date('Y-m-d H:i:s', strtotime($appt['dateAdded'])) : date('Y-m-d H:i:s')
        ];

        if (!$dryRun) {
            $db->insert('consultations', $consultationData);
            $stats['consultations_created']++;
        } else {
            echo "  [DRY RUN] Would create consultation: " . $consultationData['consultation_type'] . "\n";
            $stats['consultations_created']++;
        }
    }

    echo "✅ Appointments sync complete: {$stats['consultations_created']} consultations created\n\n";

} catch (Exception $e) {
    $stats['errors'][] = "Appointment sync error: " . $e->getMessage();
    echo "  ⚠️  Error: " . $e->getMessage() . "\n";
}

/**
 * Summary
 */
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 Sync Summary\n";
echo str_repeat("=", 60) . "\n";
echo "Clients Created:       {$stats['clients_created']}\n";
echo "Clients Updated:       {$stats['clients_updated']}\n";
echo "Projects Created:      {$stats['projects_created']}\n";
echo "Consultations Created: {$stats['consultations_created']}\n";

if (!empty($stats['errors'])) {
    echo "\n⚠️  Errors encountered:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - $error\n";
    }
}

echo "\n" . ($dryRun ? "🔍 DRY RUN COMPLETE - No data was saved" : "✅ SYNC COMPLETE") . "\n";
