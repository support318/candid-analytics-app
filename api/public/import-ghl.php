<?php
/**
 * Direct GHL data import - runs inside API container
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// GHL credentials
$apiKey = 'pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3';
$locationId = 'GHJ0X5n0UomysnUPNfao';
$baseUrl = 'https://services.leadconnectorhq.com';

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  GoHighLevel Data Import\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Connect to database
try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME']
    );

    $db = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✅ Connected to database\n\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Function to make GHL API request
function makeGHLRequest($endpoint, $apiKey, $params = []) {
    global $baseUrl;

    $url = $baseUrl . $endpoint;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Version: 2021-07-28',
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: $error");
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("GHL API returned status $httpCode: $response");
    }

    return json_decode($response, true);
}

// Fetch contacts
echo "📋 Fetching contacts from GoHighLevel...\n";

try {
    $response = makeGHLRequest('/contacts/', $apiKey, [
        'locationId' => $locationId,
        'limit' => 100
    ]);

    if (!isset($response['contacts'])) {
        die("❌ No contacts found in response\n");
    }

    $contacts = $response['contacts'];
    echo "✅ Found " . count($contacts) . " contacts\n\n";

    $imported = 0;
    $skipped = 0;

    foreach ($contacts as $contact) {
        $ghlId = $contact['id'] ?? null;

        if (!$ghlId) {
            $skipped++;
            continue;
        }

        // Check if exists
        $stmt = $db->prepare("SELECT id FROM clients WHERE ghl_contact_id = ?");
        $stmt->execute([$ghlId]);

        if ($stmt->fetch()) {
            echo "⏭️  Skipping {$contact['firstName']} {$contact['lastName']} (already exists)\n";
            $skipped++;
            continue;
        }

        // Determine lifecycle stage from tags
        $tags = $contact['tags'] ?? [];
        $lifecycleStage = 'lead';

        if (is_array($tags)) {
            $tagsLower = array_map('strtolower', $tags);
            if (in_array('booked', $tagsLower) || in_array('client', $tagsLower)) {
                $lifecycleStage = 'client';
            } elseif (in_array('qualified', $tagsLower) || in_array('proposal sent', $tagsLower)) {
                $lifecycleStage = 'qualified';
            }
        }

        // Convert tags array to PostgreSQL array format
        $tagsArray = null;
        if (!empty($tags) && is_array($tags)) {
            // Filter empty tags and escape for PostgreSQL
            $filteredTags = array_filter($tags, function($tag) {
                return !empty($tag) && trim($tag) !== '';
            });
            if (!empty($filteredTags)) {
                $tagsArray = '{' . implode(',', array_map(function($tag) {
                    return '"' . str_replace('"', '""', $tag) . '"';
                }, $filteredTags)) . '}';
            }
        }

        // Insert client
        $stmt = $db->prepare("
            INSERT INTO clients (
                ghl_contact_id, first_name, last_name, email, phone,
                lead_source, lifecycle_stage, tags, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?::text[], ?)
            RETURNING id
        ");

        $stmt->execute([
            $ghlId,
            $contact['firstName'] ?? null,
            $contact['lastName'] ?? null,
            $contact['email'] ?? null,
            $contact['phone'] ?? null,
            $contact['source'] ?? 'ghl_import',
            $lifecycleStage,
            $tagsArray,
            $contact['dateAdded'] ?? date('Y-m-d H:i:s')
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $clientId = $result['id'];

        echo "✅ Imported: {$contact['firstName']} {$contact['lastName']} (Stage: $lifecycleStage)\n";

        // If client lifecycle stage, try to create inquiry/project
        $customFields = $contact['customFields'] ?? [];

        if (!empty($customFields)) {
            // Create inquiry record
            $eventType = $customFields['AFX1YsPB7QHBP50Ajs1Q'] ?? 'other';
            $eventDate = $customFields['kvDBYw8fixMftjWdF51g'] ?? null;
            $budget = $customFields['OwkEjGNrbE7Rq0TKBG3M'] ?? 0;
            $notes = $customFields['xV2dxG35gDY1Vqb00Ql1'] ?? null;

            $stmt = $db->prepare("
                INSERT INTO inquiries (
                    client_id, inquiry_date, source, event_type,
                    event_date, budget, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $clientId,
                date('Y-m-d'),
                'ghl_import',
                $eventType,
                $eventDate,
                $budget,
                $lifecycleStage === 'client' ? 'converted' : 'new',
                $notes
            ]);

            echo "  📝 Created inquiry record\n";

            // If client, also create project
            if ($lifecycleStage === 'client') {
                $stmt = $db->prepare("
                    INSERT INTO projects (
                        client_id, project_name, booking_date, event_date,
                        event_type, total_revenue, status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $projectName = ($contact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? '') . ' - ' . $eventType;

                $stmt->execute([
                    $clientId,
                    $projectName,
                    date('Y-m-d'),
                    $eventDate ?? date('Y-m-d'),
                    $eventType,
                    $budget,
                    'booked',
                    $notes
                ]);

                echo "  📦 Created project record\n";
            }
        }

        $imported++;
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Import complete!\n";
    echo "   Imported: $imported contacts\n";
    echo "   Skipped: $skipped contacts (already existed)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Refresh materialized views
    echo "🔄 Refreshing materialized views...\n";
    try {
        $db->exec("REFRESH MATERIALIZED VIEW priority_kpis");
        echo "✅ Refreshed priority_kpis view\n";
    } catch (PDOException $e) {
        echo "⚠️  Could not refresh view: " . $e->getMessage() . "\n";
    }

    echo "\n✨ All done! Check your dashboard at https://analytics.candidstudios.net\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
