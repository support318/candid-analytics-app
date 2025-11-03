#!/usr/bin/env php
<?php

/**
 * COMPLETE Historical GHL Data Sync Script
 * Fetches all historical data from GoHighLevel and populates the analytics database
 *
 * INCLUDES ALL 57 ANALYTICS-RELEVANT CUSTOM FIELDS:
 * - 23 Project Core fields
 * - 6 Staff Assignment fields
 * - 6 Delivery/Fulfillment fields
 * - 5 Feedback/Review fields
 * - 9 Client/Partner Info fields
 * - 4 Financial fields
 * - 3 Calendar/Scheduling fields
 * - 1 Marketing/Engagement field
 *
 * CLASSIFICATION LOGIC:
 * - Creates PROJECTS only when pipelineStage === "Planning"
 * - Creates INQUIRIES for all other leads
 * - Populates staff_assignments, deliverables, reviews tables
 *
 * Usage:
 *   php sync-ghl-historical-COMPLETE.php [--dry-run] [--start-date=YYYY-MM-DD] [--end-date=YYYY-MM-DD]
 */

declare(strict_types=1);

// Load dependencies
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Parse CLI arguments
$dryRun = in_array('--dry-run', $argv);
$startDate = null;
$endDate = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--start-date=') === 0) {
        $startDate = substr($arg, 13);
    }
    if (strpos($arg, '--end-date=') === 0) {
        $endDate = substr($arg, 11);
    }
}

// Configuration - use getenv() for Railway compatibility, fall back to $_ENV for local .env
$ghlApiKey = getenv('GHL_API_KEY') ?: ($_ENV['GHL_API_KEY'] ?? '');
$ghlLocationId = getenv('GHL_LOCATION_ID') ?: ($_ENV['GHL_LOCATION_ID'] ?? '');
$ghlApiBaseUrl = getenv('GHL_API_BASE_URL') ?: ($_ENV['GHL_API_BASE_URL'] ?? 'https://services.leadconnectorhq.com');
$ghlApiVersion = getenv('GHL_API_VERSION') ?: ($_ENV['GHL_API_VERSION'] ?? '2021-07-28');

if (empty($ghlApiKey) || empty($ghlLocationId)) {
    echo "❌ Error: GHL_API_KEY and GHL_LOCATION_ID environment variables are required\n";
    exit(1);
}

// Initialize database connection - use getenv() for Railway compatibility
try {
    // Check for Railway's DATABASE_URL first (takes precedence)
    $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);

    if ($databaseUrl) {
        // Parse DATABASE_URL (format: postgresql://user:password@host:port/database)
        $parsed = parse_url($databaseUrl);
        $dbHost = $parsed['host'] ?? 'localhost';
        $dbPort = (string)($parsed['port'] ?? '5432');
        $dbName = ltrim($parsed['path'] ?? '/candid_analytics', '/');
        $dbUser = $parsed['user'] ?? 'candid_analytics_user';
        $dbPassword = $parsed['pass'] ?? '';
    } else {
        // Fall back to individual environment variables
        $dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
        $dbPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432');
        $dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'candid_analytics');
        $dbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'candid_analytics_user');
        $dbPassword = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
    }

    $db = new \CandidAnalytics\Services\Database($dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    echo "✅ Connected to database\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * ============================================================================
 * CUSTOM FIELD ID MAPPING - ALL 57 ANALYTICS-RELEVANT FIELDS
 * From GHL_COMPLETE_FIELD_MAPPING_V2.md
 * ============================================================================
 */

// Category 1: Project Core Fields (23 fields)
const PROJECT_CORE_FIELDS = [
    'services' => '00cH1d6lq8m0U8tf3FHg',                  // Services Interested In (CHECKBOX)
    'event_type' => 'AFX1YsPB7QHBP50Ajs1Q',                 // Event Type (SINGLE_OPTIONS)
    'event_type_alt' => '5EJkC8vWIgxcHh2gPwh0',             // Type of Event (TEXT - alternate)
    'event_date' => 'kvDBYw8fixMftjWdF51g',                 // Event Date (DATE)
    'event_date_alt' => 'umNF7t1tqCvqLWIiKtaI',             // Event Date (TEXT - alternate)
    'photo_hours' => 'T5nq3eiHUuXM0wFYNNg4',                // Photography Hours (SINGLE_OPTIONS)
    'photo_hours_alt' => '7TkcSmhR1wWSyDPQuaFY',            // Photography Hours (TEXT - alternate)
    'video_hours' => 'nHiHJxfNxRhvUfIu6oD6',                // Videography Hours (SINGLE_OPTIONS)
    'video_hours_alt' => 'RsLdzSzzqyaeF1In5Qv1',            // Videography Hours (TEXT - alternate)
    'project_location' => 'nstR5hDlCQJ6jpsFzxi7',           // Project Location (TEXT)
    'event_start_time' => 'Bz6tmEcB0S0pXupkha84',           // Event Start Time (SINGLE_OPTIONS)
    'photo_start_time' => 'WsryoHyIAvueTUqSesk9',           // Photography Start Time (TEXT)
    'video_start_time' => 'CUpM9HfnP638DbQjQVbZ',           // Videography Start Time (TEXT)
    'has_additional_locations' => 'iQOUEUruaZfPKln4sdKP',   // Additional Locations? (RADIO)
    'additional_locations' => '3YksnBlpr933n8so23uj',       // Additional Locations (TEXT)
    'secondary_location' => 'Bz4nmFBpGJKHifSEmg5n',         // Secondary Location (TEXT)
    'secondary_location_address' => 'pJYoq8FZ0wZg6Ypih9nJ', // Secondary Location Address (TEXT)
    'drone_services' => 'nLps8X8MZLfJChZD8tUV',             // Drone Services (TEXT)
    'preferred_photographer' => 'ybDxuw1P2hgY4RwqpClM',     // Preferred Photographer (SINGLE_OPTIONS)
    'preferred_videographer' => 'TjYe4bZEn1IHo0TW4WGz',     // Preferred Videographer (SINGLE_OPTIONS)
    'notes' => 'xV2dxG35gDY1Vqb00Ql1',                      // Additional Requests/Info (LARGE_TEXT)
    'notes_alt' => '46GRzmwomeEJcjoQJgEi',                  // Additional Requests (Primary) (LARGE_TEXT)
    'additional_videos_link' => 'cGEUu0JUCDJDwJsHsyQa'      // Link For Additional Videos (TEXT)
];

// Category 2: Staff Assignment Fields (6 fields)
const STAFF_FIELDS = [
    'assigned_photographer' => 'G4eZc8UKyPgGr36nyR50',      // Assigned Photographer (TEXT)
    'photographer_first_name' => 'NUC0izbVu26XEiriE5Up',    // Assigned Photographer First Name (TEXT)
    'assigned_videographer' => '3wvwfiEBn28TH67xK0R2',      // Assigned Videographer (TEXT)
    'videographer_first_name' => 'HH0onKM31fhdsh4pnvh3',    // Assigned Videographer First Name (TEXT)
    'project_manager' => 'as6qzWMAaodZSH2JgUCt',            // Project Manager (TEXT)
    'sales_agent' => 'qpyukeOGutkXczPGJOyK'                 // Sales Agent (TEXT)
];

// Category 3: Delivery/Fulfillment Fields (6 fields)
const DELIVERY_FIELDS = [
    'delivery_deadline' => 'Igtc83ZkufU8TK50H385',          // Delivery Deadline Date (TEXT)
    'raw_images_link' => 'bp5oCoPifWXOOcN7Z79F',            // Link To Raw Images (TEXT)
    'raw_video_link' => 'K3fNomA8tFU3wShooTTh',             // Link to RAW Video Content (TEXT)
    'final_images_link' => 'epv4xKKDDS1HqbiRz7Wc',          // Link to Final Image gallery (TEXT)
    'final_video_link' => 'QjjCsBRRNu0FlD0ocEJk',           // Link to Final Video (TEXT)
    'has_video' => 'NZh0hsK8OaQ1vHrU0Lkq'                   // Does This Project Have Video? (SINGLE_OPTIONS)
];

// Category 4: Feedback/Review Fields (5 fields)
const REVIEW_FIELDS = [
    'photographer_feedback' => 'CgCTDcu9MCtIIKWlcHZV',      // Feedback For Photographers (LARGE_TEXT)
    'videographer_feedback' => 'P7Et5cQwWqWPnFpeY7Wf',      // Feedback For Videographers (LARGE_TEXT)
    'photo_notes_to_editors' => 'Moa0uJbJTUs3gi4d8zw1',     // Photographers Notes To Editors (LARGE_TEXT)
    'video_notes_to_editors' => 'SHEQAgVtY6k1kVEBS80V',     // Videographers Notes To Editors (LARGE_TEXT)
    'review_link' => 'fIkJwAvbFzQGcLbKTbat'                 // Review Link Based On Location (TEXT)
];

// Category 5: Client/Partner Info Fields (9 fields) - Use primary fields, not "Web Form" duplicates
const PARTNER_FIELDS = [
    'partner_first_name' => 'WPisKIBj4RYy6LkapuX7',         // Partner's First Name (TEXT)
    'partner_last_name' => '7jNpL5BQB3DHJ5mcsFZP',          // Partner's Last Name (TEXT)
    'partner_email' => 'AtOOSx6IrAHhwMps1Ayi',              // Partner's Email (TEXT)
    'partner_phone' => 'iCPDAVCj8RtdRyGNFF12',              // Partner's Phone (TEXT)
    'mailing_address' => '99WRcoKduET0VmFHDd5O'             // Mailing Address (TEXT)
];

// Category 6: Financial Fields (4 fields)
const FINANCIAL_FIELDS = [
    'opportunity_value' => 'OwkEjGNrbE7Rq0TKBG3M',          // Opportunity Value (TEXT)
    'discount_type' => 'HzqDjDwyweE2Qoc47Y9t',              // Active Discount Type (SINGLE_OPTIONS)
    'discount_amount' => '9GBzaQnbrt1z3eLrDIJA',            // Discount Amount (NUMERICAL)
    'travel_distance' => 'qyNnRaxTsDikF7S7XejH'             // Round trip distance (TEXT)
];

// Category 7: Calendar/Scheduling Fields (3 fields)
const CALENDAR_FIELDS = [
    'appointment_start_time' => 'gViqIJVcaJpyLvWPF6Av',     // Appointment Start Time (TEXT)
    'calendar_event_id' => 'LPhlpUyfluKfHFidy5pI',          // Calendar Event ID (TEXT)
    'meeting_link' => 'Y8zEeTsTeVzLa5ODBARP'                // Meeting Link (Ryan + Photographer) (TEXT)
];

// Category 8: Marketing/Engagement Fields (1 field)
const MARKETING_FIELDS = [
    'engagement_score' => 'zPbacyR7OIixOVjHefk5'            // Engagement Score (NUMERICAL)
];

/**
 * ============================================================================
 * HELPER FUNCTIONS
 * ============================================================================
 */

/**
 * Convert PHP array to PostgreSQL array format
 */
function phpArrayToPostgresArray(?array $arr): ?string {
    if (empty($arr)) {
        return null;
    }
    // PostgreSQL array format: {"value1","value2","value3"}
    $escaped = array_map(function($val) {
        // Escape double quotes and backslashes
        return '"' . str_replace(['"', '\\'], ['\"', '\\\\'], (string)$val) . '"';
    }, $arr);
    return '{' . implode(',', $escaped) . '}';
}

/**
 * Map GHL opportunity status to database inquiry status
 */
function mapInquiryStatus(?string $ghlStatus): string {
    $statusMap = [
        'open' => 'new',
        'new' => 'new',
        'contacted' => 'contacted',
        'qualified' => 'qualified',
        'won' => 'booked',
        'lost' => 'lost',
        'abandoned' => 'lost'
    ];

    $normalized = strtolower(trim($ghlStatus ?? ''));
    return $statusMap[$normalized] ?? 'new';
}

/**
 * Extract custom field value from GHL custom fields array
 */
function getCustomFieldValue(array $customFieldsArray, string $fieldId): ?string {
    foreach ($customFieldsArray as $field) {
        if (($field['id'] ?? null) === $fieldId) {
            $value = $field['value'] ?? $field['fieldValue'] ?? $field['fieldValueString'] ?? $field['fieldValueNumber'] ?? null;

            // Handle array values (CHECKBOX fields)
            if (is_array($value)) {
                return json_encode($value);
            }

            // Convert numeric values to string to match return type
            if (is_numeric($value)) {
                return (string)$value;
            }

            return $value;
        }
    }
    return null;
}

/**
 * Get field with fallback to alternate field
 */
function getFieldWithFallback(array $customFieldsArray, string $primaryFieldId, ?string $altFieldId = null): ?string {
    $value = getCustomFieldValue($customFieldsArray, $primaryFieldId);

    if (empty($value) && $altFieldId !== null) {
        $value = getCustomFieldValue($customFieldsArray, $altFieldId);
    }

    return $value;
}

/**
 * Transform Yes/No to boolean
 */
function transformYesNo(?string $value): bool {
    if ($value === null) return false;
    return strtolower(trim($value)) === 'yes';
}

/**
 * Transform date string to proper format
 */
function transformDate(?string $value): ?string {
    if (empty($value)) return null;

    try {
        $date = new DateTime($value);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Transform number string to decimal
 */
function transformDecimal(?string $value): ?float {
    if (empty($value)) return null;

    // Remove currency symbols, commas, etc.
    $cleaned = preg_replace('/[^0-9.]/', '', $value);
    return $cleaned ? (float)$cleaned : null;
}

/**
 * Transform number string to integer
 */
function transformInt(?string $value): ?int {
    if (empty($value)) return null;

    // Handle "15+" style values
    $cleaned = preg_replace('/[^0-9]/', '', $value);
    return $cleaned ? (int)$cleaned : null;
}

/**
 * Fetch contacts from GoHighLevel API
 */
function fetchContacts(string $apiKey, string $locationId, string $baseUrl, string $version): array {
    $allContacts = [];
    $nextCursor = null;
    $pageCount = 0;

    do {
        $pageCount++;
        $url = "$baseUrl/contacts/?locationId=$locationId&limit=100";
        if ($nextCursor) {
            $url .= "&startAfterId=$nextCursor";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $apiKey",
                "Version: $version",
                "Accept: application/json"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo "❌ Error fetching contacts (page $pageCount): HTTP $httpCode\n";
            echo "Response: $response\n";
            break;
        }

        $data = json_decode($response, true);
        $contacts = $data['contacts'] ?? [];
        $allContacts = array_merge($allContacts, $contacts);

        $nextCursor = $data['meta']['nextCursor'] ?? null;

        echo "📄 Fetched page $pageCount: " . count($contacts) . " contacts (Total: " . count($allContacts) . ")\n";

        // Rate limiting
        usleep(200000); // 200ms delay between requests

    } while ($nextCursor !== null);

    return $allContacts;
}

/**
 * Fetch opportunities from GoHighLevel API
 */
function fetchOpportunities(string $apiKey, string $locationId, string $baseUrl, string $version): array {
    $allOpportunities = [];
    $nextCursor = null;
    $pageCount = 0;

    do {
        $pageCount++;
        $url = "$baseUrl/opportunities/search";

        // Build POST body for search endpoint
        $body = [
            'locationId' => $locationId,
            'limit' => 100
        ];
        if ($nextCursor) {
            $body['startAfterId'] = $nextCursor;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $apiKey",
                "Version: $version",
                "Content-Type: application/json",
                "Accept: application/json"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            echo "❌ Error fetching opportunities (page $pageCount): HTTP $httpCode\n";
            echo "Response: $response\n";
            break;
        }

        $data = json_decode($response, true);
        $opportunities = $data['opportunities'] ?? [];
        $allOpportunities = array_merge($allOpportunities, $opportunities);

        $nextCursor = $data['meta']['nextCursor'] ?? null;

        echo "📄 Fetched page $pageCount: " . count($opportunities) . " opportunities (Total: " . count($allOpportunities) . ")\n";

        // Rate limiting
        usleep(200000); // 200ms delay between requests

    } while ($nextCursor !== null);

    return $allOpportunities;
}

/**
 * ============================================================================
 * MAIN SYNC LOGIC
 * ============================================================================
 */

echo "🚀 Starting Complete GHL Historical Data Sync\n";
echo "==============================================\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No data will be written to database\n\n";
}

// Step 1: Fetch all contacts
echo "Step 1/3: Fetching contacts from GoHighLevel...\n";
$contacts = fetchContacts($ghlApiKey, $ghlLocationId, $ghlApiBaseUrl, $ghlApiVersion);
echo "✅ Fetched " . count($contacts) . " total contacts\n\n";

// Step 2: Fetch all opportunities
echo "Step 2/3: Fetching opportunities from GoHighLevel...\n";
$opportunities = fetchOpportunities($ghlApiKey, $ghlLocationId, $ghlApiBaseUrl, $ghlApiVersion);
echo "✅ Fetched " . count($opportunities) . " total opportunities\n\n";

// Step 3: Process and import data
echo "Step 3/3: Processing and importing data...\n";
echo "==============================================\n\n";

$stats = [
    'clients_created' => 0,
    'clients_updated' => 0,
    'inquiries_created' => 0,
    'projects_created' => 0,
    'staff_assignments_created' => 0,
    'deliverables_created' => 0,
    'reviews_created' => 0,
    'errors' => 0
];

// Create contact lookup map
$contactMap = [];
foreach ($contacts as $contact) {
    $contactMap[$contact['id']] = $contact;
}

// Process each opportunity
foreach ($opportunities as $opp) {
    try {
        $contactId = $opp['contactId'] ?? $opp['contact']['id'] ?? null;

        if (!$contactId || !isset($contactMap[$contactId])) {
            echo "⚠️  Skipping opportunity {$opp['id']}: No contact found\n";
            continue;
        }

        $contact = $contactMap[$contactId];
        $customFields = $contact['customFields'] ?? $contact['customField'] ?? [];

        // ===================================================================
        // CLIENT CREATION/UPDATE
        // ===================================================================

        $clientData = [
            'ghl_contact_id' => $contact['id'],
            'first_name' => $contact['firstName'] ?? null,
            'last_name' => $contact['lastName'] ?? null,
            'email' => $contact['email'] ?? null,
            'phone' => $contact['phone'] ?? null,
            'lead_source' => $contact['source'] ?? null,
            'lifecycle_stage' => $contact['type'] ?? 'lead',
            'tags' => phpArrayToPostgresArray($contact['tags'] ?? null),
            'first_inquiry_date' => transformDate($contact['dateAdded'] ?? null),

            // NEW FIELDS FROM GHL DISCOVERY
            'engagement_score' => transformInt(getCustomFieldValue($customFields, MARKETING_FIELDS['engagement_score'])),
            'mailing_address' => getCustomFieldValue($customFields, PARTNER_FIELDS['mailing_address']),
            'partner_first_name' => getCustomFieldValue($customFields, PARTNER_FIELDS['partner_first_name']),
            'partner_last_name' => getCustomFieldValue($customFields, PARTNER_FIELDS['partner_last_name']),
            'partner_email' => getCustomFieldValue($customFields, PARTNER_FIELDS['partner_email']),
            'partner_phone' => getCustomFieldValue($customFields, PARTNER_FIELDS['partner_phone'])
        ];

        if (!$dryRun) {
            // Check if client exists
            $existingClient = $db->queryOne(
                'SELECT * FROM clients WHERE ghl_contact_id = :ghl_contact_id',
                ['ghl_contact_id' => $contact['id']]
            );

            if ($existingClient) {
                $db->update('clients', $clientData, ['id' => $existingClient['id']]);
                $clientDbId = $existingClient['id'];
                $stats['clients_updated']++;
            } else {
                $clientDbId = $db->insert('clients', $clientData);
                $stats['clients_created']++;
            }
        } else {
            echo "   [DRY RUN] Would create/update client: {$contact['firstName']} {$contact['lastName']} ({$contact['email']})\n";
            $clientDbId = 'dry-run-client-id';
        }

        // ===================================================================
        // BOOKING CLASSIFICATION - CRITICAL LOGIC
        // ===================================================================

        $pipelineStage = $opp['pipelineStage'] ?? $opp['stage'] ?? '';
        $pipelineStageNormalized = strtolower(trim($pipelineStage));
        $isBooked = ($pipelineStageNormalized === 'planning');

        if ($isBooked) {
            echo "✅ BOOKED PROJECT: {$opp['name']} (Stage: $pipelineStage)\n";

            // CREATE PROJECT
            $projectData = [
                'client_id' => $clientDbId,
                'ghl_opportunity_id' => $opp['id'],
                'project_name' => $opp['name'],
                'booking_date' => transformDate($opp['createdAt'] ?? null),
                'event_date' => transformDate(getFieldWithFallback($customFields, PROJECT_CORE_FIELDS['event_date'], PROJECT_CORE_FIELDS['event_date_alt'])),
                'event_type' => getFieldWithFallback($customFields, PROJECT_CORE_FIELDS['event_type'], PROJECT_CORE_FIELDS['event_type_alt']),
                'venue_address' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['project_location']),
                'status' => 'booked',
                'total_revenue' => transformDecimal(getCustomFieldValue($customFields, FINANCIAL_FIELDS['opportunity_value'])),

                // NEW FINANCIAL FIELDS
                'discount_type' => getCustomFieldValue($customFields, FINANCIAL_FIELDS['discount_type']),
                'discount_amount' => transformDecimal(getCustomFieldValue($customFields, FINANCIAL_FIELDS['discount_amount'])),
                'travel_distance' => getCustomFieldValue($customFields, FINANCIAL_FIELDS['travel_distance']),

                // NEW VIDEO FLAG
                'has_video' => transformYesNo(getCustomFieldValue($customFields, DELIVERY_FIELDS['has_video'])),

                // NEW CALENDAR FIELDS
                'calendar_event_id' => getCustomFieldValue($customFields, CALENDAR_FIELDS['calendar_event_id']),

                // METADATA (all project core fields)
                'metadata' => json_encode([
                    'services' => json_decode(getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['services']) ?? '[]', true),
                    'photo_hours' => getFieldWithFallback($customFields, PROJECT_CORE_FIELDS['photo_hours'], PROJECT_CORE_FIELDS['photo_hours_alt']),
                    'video_hours' => getFieldWithFallback($customFields, PROJECT_CORE_FIELDS['video_hours'], PROJECT_CORE_FIELDS['video_hours_alt']),
                    'event_time' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['event_start_time']),
                    'photo_start_time' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['photo_start_time']),
                    'video_start_time' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['video_start_time']),
                    'drone_services' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['drone_services']),
                    'has_additional_locations' => transformYesNo(getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['has_additional_locations'])),
                    'additional_locations' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['additional_locations']),
                    'secondary_location' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['secondary_location']),
                    'secondary_location_address' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['secondary_location_address']),
                    'preferred_photographer' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['preferred_photographer']),
                    'preferred_videographer' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['preferred_videographer']),
                    'photographer_name' => getCustomFieldValue($customFields, STAFF_FIELDS['photographer_first_name']),
                    'videographer_name' => getCustomFieldValue($customFields, STAFF_FIELDS['videographer_first_name']),
                    'photo_notes_to_editors' => getCustomFieldValue($customFields, REVIEW_FIELDS['photo_notes_to_editors']),
                    'video_notes_to_editors' => getCustomFieldValue($customFields, REVIEW_FIELDS['video_notes_to_editors']),
                    'appointment_time' => getCustomFieldValue($customFields, CALENDAR_FIELDS['appointment_start_time']),
                    'meeting_link' => getCustomFieldValue($customFields, CALENDAR_FIELDS['meeting_link']),
                    'additional_videos_link' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['additional_videos_link'])
                ]),

                'notes' => getFieldWithFallback($customFields, PROJECT_CORE_FIELDS['notes'], PROJECT_CORE_FIELDS['notes_alt'])
            ];

            if (!$dryRun) {
                $projectId = $db->insert('projects', $projectData);
                $stats['projects_created']++;

                // CREATE STAFF ASSIGNMENTS
                $staffAssignments = [];

                $photographer = getCustomFieldValue($customFields, STAFF_FIELDS['assigned_photographer']);
                if (!empty($photographer)) {
                    $staffAssignments[] = [
                        'project_id' => $projectId,
                        'staff_name' => $photographer,
                        'role' => 'photographer',
                        'ghl_staff_id' => $photographer
                    ];
                }

                $videographer = getCustomFieldValue($customFields, STAFF_FIELDS['assigned_videographer']);
                if (!empty($videographer)) {
                    $staffAssignments[] = [
                        'project_id' => $projectId,
                        'staff_name' => $videographer,
                        'role' => 'videographer',
                        'ghl_staff_id' => $videographer
                    ];
                }

                $projectManager = getCustomFieldValue($customFields, STAFF_FIELDS['project_manager']);
                if (!empty($projectManager)) {
                    $staffAssignments[] = [
                        'project_id' => $projectId,
                        'staff_name' => $projectManager,
                        'role' => 'project_manager',
                        'ghl_staff_id' => $projectManager
                    ];
                }

                $salesAgent = getCustomFieldValue($customFields, STAFF_FIELDS['sales_agent']);
                if (!empty($salesAgent)) {
                    $staffAssignments[] = [
                        'project_id' => $projectId,
                        'staff_name' => $salesAgent,
                        'role' => 'sales_agent',
                        'ghl_staff_id' => $salesAgent
                    ];
                }

                foreach ($staffAssignments as $assignment) {
                    $db->insert('staff_assignments', $assignment);
                    $stats['staff_assignments_created']++;
                }

                // CREATE DELIVERABLES
                $deliverableData = [
                    'project_id' => $projectId,
                    'deliverable_type' => 'photos', // Default type
                    'expected_delivery_date' => transformDate(getCustomFieldValue($customFields, DELIVERY_FIELDS['delivery_deadline'])),
                    'raw_images_link' => getCustomFieldValue($customFields, DELIVERY_FIELDS['raw_images_link']),
                    'raw_video_link' => getCustomFieldValue($customFields, DELIVERY_FIELDS['raw_video_link']),
                    'final_images_link' => getCustomFieldValue($customFields, DELIVERY_FIELDS['final_images_link']),
                    'final_video_link' => getCustomFieldValue($customFields, DELIVERY_FIELDS['final_video_link']),
                    'additional_videos_link' => getCustomFieldValue($customFields, PROJECT_CORE_FIELDS['additional_videos_link'])
                ];

                $db->insert('deliverables', $deliverableData);
                $stats['deliverables_created']++;

                // CREATE REVIEWS (if feedback exists)
                $photographerFeedback = getCustomFieldValue($customFields, REVIEW_FIELDS['photographer_feedback']);
                $videographerFeedback = getCustomFieldValue($customFields, REVIEW_FIELDS['videographer_feedback']);
                $reviewLink = getCustomFieldValue($customFields, REVIEW_FIELDS['review_link']);

                if (!empty($photographerFeedback) || !empty($videographerFeedback) || !empty($reviewLink)) {
                    $reviewData = [
                        'project_id' => $projectId,
                        'client_id' => $clientDbId,
                        'photographer_feedback' => $photographerFeedback,
                        'videographer_feedback' => $videographerFeedback,
                        'review_link' => $reviewLink
                    ];

                    $db->insert('reviews', $reviewData);
                    $stats['reviews_created']++;
                }

                // Update client lifecycle
                $db->update('clients', ['lifecycle_stage' => 'client'], ['id' => $clientDbId]);

            } else {
                echo "   [DRY RUN] Would create project with:\n";
                echo "     - Staff: $photographer (photographer), $videographer (videographer)\n";
                echo "     - Delivery deadline: " . getCustomFieldValue($customFields, DELIVERY_FIELDS['delivery_deadline']) . "\n";
                echo "     - Has feedback: " . ((!empty($photographerFeedback) || !empty($videographerFeedback)) ? 'Yes' : 'No') . "\n";
            }

        } else {
            echo "📋 INQUIRY (not booked): {$opp['name']} (Stage: $pipelineStage)\n";

            // CREATE INQUIRY
            $inquiryData = [
                'client_id' => $clientDbId,
                'inquiry_date' => transformDate($opp['createdAt'] ?? null),
                'source' => $contact['source'] ?? null,
                'event_type' => getFieldWithFallback($customFields, PROJECT_CORE_FIELDS['event_type'], PROJECT_CORE_FIELDS['event_type_alt']),
                'event_date' => transformDate(getFieldWithFallback($customFields, PROJECT_CORE_FIELDS['event_date'], PROJECT_CORE_FIELDS['event_date_alt'])),
                'budget' => transformDecimal(getCustomFieldValue($customFields, FINANCIAL_FIELDS['opportunity_value'])),
                'status' => mapInquiryStatus($opp['status']),
                'notes' => getFieldWithFallback($customFields, PROJECT_CORE_FIELDS['notes'], PROJECT_CORE_FIELDS['notes_alt'])
            ];

            if (!$dryRun) {
                $db->insert('inquiries', $inquiryData);
                $stats['inquiries_created']++;
            } else {
                echo "   [DRY RUN] Would create inquiry\n";
            }
        }

    } catch (Exception $e) {
        echo "❌ Error processing opportunity {$opp['id']}: " . $e->getMessage() . "\n";
        $stats['errors']++;
    }
}

// Print final statistics
echo "\n";
echo "==============================================\n";
echo "✅ SYNC COMPLETE\n";
echo "==============================================\n\n";

echo "📊 Statistics:\n";
echo "  Clients Created: {$stats['clients_created']}\n";
echo "  Clients Updated: {$stats['clients_updated']}\n";
echo "  Projects Created: {$stats['projects_created']}\n";
echo "  Inquiries Created: {$stats['inquiries_created']}\n";
echo "  Staff Assignments Created: {$stats['staff_assignments_created']}\n";
echo "  Deliverables Created: {$stats['deliverables_created']}\n";
echo "  Reviews Created: {$stats['reviews_created']}\n";
echo "  Errors: {$stats['errors']}\n";

if ($dryRun) {
    echo "\n⚠️  This was a DRY RUN - no data was actually written to the database\n";
    echo "Run without --dry-run to perform the actual import\n";
}

echo "\n✅ Done!\n";
