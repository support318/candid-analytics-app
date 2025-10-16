#!/usr/bin/env php
<?php
/**
 * GoHighLevel Historical Data Import Script
 *
 * This script pulls historical contacts, opportunities, and payments from GHL
 * and populates the analytics database with real data.
 *
 * Usage:
 *   php scripts/import-ghl-data.php --api-key=YOUR_GHL_API_KEY --location-id=YOUR_LOCATION_ID
 *
 * Options:
 *   --api-key         Your GHL API Key (required)
 *   --location-id     Your GHL Location ID (required)
 *   --start-date      Start date for data import (format: YYYY-MM-DD, default: 1 year ago)
 *   --end-date        End date for data import (format: YYYY-MM-DD, default: today)
 *   --dry-run         Preview what would be imported without actually importing
 */

require_once __DIR__ . '/../api/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../api');
$dotenv->load();

class GHLDataImporter
{
    private $apiKey;
    private $locationId;
    private $baseUrl = 'https://services.leadconnectorhq.com';
    private $db;
    private $dryRun = false;

    // Custom field IDs from GHL_FIELD_MAPPING.md
    private $fieldMapping = [
        '00cH1d6lq8m0U8tf3FHg' => 'services',
        'AFX1YsPB7QHBP50Ajs1Q' => 'event_type',
        'Bz6tmEcB0S0pXupkha84' => 'event_start_time',
        'T5nq3eiHUuXM0wFYNNg4' => 'photo_hours',
        'nHiHJxfNxRhvUfIu6oD6' => 'video_hours',
        'iQOUEUruaZfPKln4sdKP' => 'drone_services',
        'kvDBYw8fixMftjWdF51g' => 'event_date',
        'nstR5hDlCQJ6jpsFzxi7' => 'event_location',
        'qpyukeOGutkXczPGJOyK' => 'contact_name',
        'OwkEjGNrbE7Rq0TKBG3M' => 'estimated_value',
        'xV2dxG35gDY1Vqb00Ql1' => 'notes'
    ];

    public function __construct($apiKey, $locationId, $dryRun = false)
    {
        $this->apiKey = $apiKey;
        $this->locationId = $locationId;
        $this->dryRun = $dryRun;

        // Connect to database
        $this->connectDatabase();
    }

    private function connectDatabase()
    {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            );

            $this->db = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            echo "✅ Connected to database\n";
        } catch (PDOException $e) {
            die("❌ Database connection failed: " . $e->getMessage() . "\n");
        }
    }

    private function makeGHLRequest($endpoint, $params = [])
    {
        $url = $this->baseUrl . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Version: 2021-07-28',
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo "⚠️  GHL API request failed with status $httpCode\n";
            echo "Response: $response\n";
            return null;
        }

        return json_decode($response, true);
    }

    public function importContacts($startDate, $endDate)
    {
        echo "\n📋 Importing Contacts from GHL...\n";
        echo "Date range: $startDate to $endDate\n\n";

        $params = [
            'locationId' => $this->locationId,
            'startAfter' => $startDate,
            'endBefore' => $endDate,
            'limit' => 100
        ];

        $contacts = $this->makeGHLRequest('/contacts/', $params);

        if (!$contacts || !isset($contacts['contacts'])) {
            echo "❌ Failed to fetch contacts from GHL\n";
            return 0;
        }

        $imported = 0;
        $skipped = 0;

        foreach ($contacts['contacts'] as $contact) {
            try {
                if ($this->importContact($contact)) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (Exception $e) {
                echo "❌ Error importing contact {$contact['id']}: " . $e->getMessage() . "\n";
                $skipped++;
            }
        }

        echo "\n✅ Imported $imported contacts\n";
        echo "⏭️  Skipped $skipped contacts (already exist)\n";

        return $imported;
    }

    private function importContact($contact)
    {
        $ghlContactId = $contact['id'] ?? null;

        if (!$ghlContactId) {
            return false;
        }

        // Check if contact already exists
        $stmt = $this->db->prepare("SELECT client_id FROM clients WHERE ghl_contact_id = ?");
        $stmt->execute([$ghlContactId]);

        if ($stmt->fetch()) {
            echo "⏭️  Contact {$ghlContactId} already exists\n";
            return false;
        }

        if ($this->dryRun) {
            echo "[DRY RUN] Would import: {$contact['firstName']} {$contact['lastName']} ({$contact['email']})\n";
            return true;
        }

        // Insert client
        $stmt = $this->db->prepare("
            INSERT INTO clients (
                ghl_contact_id, first_name, last_name, email, phone,
                lead_source, lifecycle_stage, tags, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING client_id
        ");

        $lifecycleStage = $this->determineLifecycleStage($contact);
        $tags = isset($contact['tags']) ? json_encode($contact['tags']) : null;

        $stmt->execute([
            $ghlContactId,
            $contact['firstName'] ?? null,
            $contact['lastName'] ?? null,
            $contact['email'] ?? null,
            $contact['phone'] ?? null,
            $contact['source'] ?? 'ghl_import',
            $lifecycleStage,
            $tags,
            $contact['dateAdded'] ?? date('Y-m-d H:i:s')
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $clientId = $result['client_id'];

        echo "✅ Imported contact: {$contact['firstName']} {$contact['lastName']} (ID: $clientId)\n";

        // Import associated inquiry/project data
        $this->importContactOpportunities($contact, $clientId);

        return true;
    }

    private function determineLifecycleStage($contact)
    {
        $tags = $contact['tags'] ?? [];

        if (is_array($tags)) {
            if (in_array('booked', $tags) || in_array('client', $tags)) {
                return 'client';
            }
            if (in_array('qualified', $tags) || in_array('proposal sent', $tags)) {
                return 'qualified';
            }
        }

        return 'lead';
    }

    private function importContactOpportunities($contact, $clientId)
    {
        // Get opportunities for this contact
        $opportunities = $this->makeGHLRequest("/opportunities/search", [
            'location_id' => $this->locationId,
            'contact_id' => $contact['id']
        ]);

        if (!$opportunities || !isset($opportunities['opportunities'])) {
            return;
        }

        foreach ($opportunities['opportunities'] as $opp) {
            $this->importOpportunity($opp, $clientId);
        }
    }

    private function importOpportunity($opp, $clientId)
    {
        if ($this->dryRun) {
            echo "[DRY RUN] Would import opportunity: {$opp['name']} (Value: {$opp['monetaryValue']})\n";
            return;
        }

        // Determine if this is an inquiry or a project
        $status = strtolower($opp['status'] ?? 'open');
        $isBooked = in_array($status, ['won', 'booked', 'closed won']);

        if ($isBooked) {
            // Create project
            $stmt = $this->db->prepare("
                INSERT INTO projects (
                    client_id, ghl_opportunity_id, project_name, booking_date,
                    event_date, event_type, status, total_revenue, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $clientId,
                $opp['id'],
                $opp['name'] ?? 'Imported Project',
                date('Y-m-d'),
                date('Y-m-d'), // Would need event date from custom fields
                'other',
                'booked',
                ($opp['monetaryValue'] ?? 0) / 100, // GHL stores in cents
                $opp['notes'] ?? null
            ]);

            echo "  📦 Imported project: {$opp['name']}\n";
        } else {
            // Create inquiry
            $stmt = $this->db->prepare("
                INSERT INTO inquiries (
                    client_id, ghl_opportunity_id, inquiry_date, source,
                    event_type, budget, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $clientId,
                $opp['id'],
                date('Y-m-d'),
                'ghl_import',
                'other',
                ($opp['monetaryValue'] ?? 0) / 100,
                'new',
                $opp['notes'] ?? null
            ]);

            echo "  📝 Imported inquiry: {$opp['name']}\n";
        }
    }

    public function refreshMaterializedViews()
    {
        if ($this->dryRun) {
            echo "\n[DRY RUN] Would refresh materialized views\n";
            return;
        }

        echo "\n🔄 Refreshing materialized views...\n";

        try {
            $this->db->exec("REFRESH MATERIALIZED VIEW priority_kpis");
            echo "✅ Refreshed priority_kpis view\n";
        } catch (PDOException $e) {
            echo "⚠️  Could not refresh materialized views: " . $e->getMessage() . "\n";
        }
    }
}

// Parse command line arguments
$options = getopt('', [
    'api-key:',
    'location-id:',
    'start-date::',
    'end-date::',
    'dry-run'
]);

if (!isset($options['api-key']) || !isset($options['location-id'])) {
    echo "Usage: php import-ghl-data.php --api-key=YOUR_KEY --location-id=YOUR_ID [--start-date=YYYY-MM-DD] [--end-date=YYYY-MM-DD] [--dry-run]\n\n";
    echo "Required:\n";
    echo "  --api-key       Your GHL API Key\n";
    echo "  --location-id   Your GHL Location ID\n\n";
    echo "Optional:\n";
    echo "  --start-date    Start date (default: 1 year ago)\n";
    echo "  --end-date      End date (default: today)\n";
    echo "  --dry-run       Preview without importing\n\n";
    exit(1);
}

$apiKey = $options['api-key'];
$locationId = $options['location-id'];
$startDate = $options['start-date'] ?? date('Y-m-d', strtotime('-1 year'));
$endDate = $options['end-date'] ?? date('Y-m-d');
$dryRun = isset($options['dry-run']);

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  GoHighLevel Data Import Script\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No data will be modified\n\n";
}

$importer = new GHLDataImporter($apiKey, $locationId, $dryRun);

// Import contacts
$imported = $importer->importContacts($startDate, $endDate);

// Refresh views
if ($imported > 0) {
    $importer->refreshMaterializedViews();
}

echo "\n✨ Import complete!\n\n";
