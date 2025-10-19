#!/usr/bin/env php
<?php

/**
 * Import Historical Revenue from GHL Invoices
 *
 * This script imports historical invoice/payment data into the system.
 *
 * Usage:
 *   1. Export invoices from GHL as CSV
 *   2. Run: php import-historical-revenue.php <csv-file>
 *
 * Or import individual invoices:
 *   php import-historical-revenue.php --manual
 *
 * CSV Format Expected:
 *   contact_id,invoice_id,customer_name,email,amount,payment_date,status
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use CandidAnalytics\Services\Database;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Initialize database
$db = new Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);

echo "📊 Historical Revenue Import Tool\n";
echo str_repeat("=", 60) . "\n\n";

// Check arguments
if ($argc < 2) {
    showUsage();
    exit(1);
}

$mode = $argv[1];

if ($mode === '--manual') {
    manualImport($db);
} elseif ($mode === '--webhook') {
    webhookImport($db);
} elseif (file_exists($mode)) {
    csvImport($db, $mode);
} else {
    echo "❌ File not found: {$mode}\n\n";
    showUsage();
    exit(1);
}

/**
 * Show usage instructions
 */
function showUsage(): void
{
    echo "Usage:\n";
    echo "  CSV Import:     php import-historical-revenue.php invoices.csv\n";
    echo "  Manual Entry:   php import-historical-revenue.php --manual\n";
    echo "  Webhook Method: php import-historical-revenue.php --webhook\n\n";
    echo "CSV Format:\n";
    echo "  contact_id,invoice_id,customer_name,email,amount,payment_date,status\n\n";
}

/**
 * Import from CSV file
 */
function csvImport(Database $db, string $filename): void
{
    echo "📁 Importing from CSV: {$filename}\n\n";

    $handle = fopen($filename, 'r');
    if (!$handle) {
        echo "❌ Could not open file\n";
        exit(1);
    }

    // Read header
    $header = fgetcsv($handle);
    if (!$header) {
        echo "❌ Invalid CSV file\n";
        exit(1);
    }

    echo "CSV Columns: " . implode(', ', $header) . "\n\n";

    $imported = 0;
    $skipped = 0;
    $errors = 0;
    $lineNum = 1;

    while (($row = fgetcsv($handle)) !== false) {
        $lineNum++;

        if (count($row) < 6) {
            echo "⚠️  Line {$lineNum}: Skipping incomplete row\n";
            $skipped++;
            continue;
        }

        $invoiceData = [
            'contactId' => $row[0],
            'altId' => $row[1],
            'name' => $row[2],
            'email' => $row[3],
            'amountPaid' => floatval($row[4]),
            'amount' => floatval($row[4]),
            'updatedAt' => $row[5],
            'status' => $row[6] ?? 'paid',
            'currency' => $row[7] ?? 'USD',
        ];

        try {
            $result = importInvoice($db, $invoiceData);
            if ($result['success']) {
                echo "✅ Line {$lineNum}: Imported \${$invoiceData['amountPaid']} for {$invoiceData['name']}\n";
                $imported++;
            } else {
                echo "⚠️  Line {$lineNum}: {$result['message']}\n";
                $skipped++;
            }
        } catch (\Exception $e) {
            echo "❌ Line {$lineNum}: Error - {$e->getMessage()}\n";
            $errors++;
        }
    }

    fclose($handle);

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Import Complete!\n";
    echo "  Imported: {$imported}\n";
    echo "  Skipped:  {$skipped}\n";
    echo "  Errors:   {$errors}\n";
    echo str_repeat("=", 60) . "\n";
}

/**
 * Manual import - interactive
 */
function manualImport(Database $db): void
{
    echo "📝 Manual Invoice Entry\n\n";

    echo "Enter invoice details (or 'quit' to exit):\n\n";

    while (true) {
        echo "Contact ID (GHL): ";
        $contactId = trim(fgets(STDIN));
        if ($contactId === 'quit') break;

        echo "Invoice ID: ";
        $invoiceId = trim(fgets(STDIN));

        echo "Customer Name: ";
        $name = trim(fgets(STDIN));

        echo "Email: ";
        $email = trim(fgets(STDIN));

        echo "Amount Paid: $";
        $amount = floatval(trim(fgets(STDIN)));

        echo "Payment Date (YYYY-MM-DD): ";
        $date = trim(fgets(STDIN));

        $invoiceData = [
            'contactId' => $contactId,
            'altId' => $invoiceId,
            'name' => $name,
            'email' => $email,
            'amountPaid' => $amount,
            'amount' => $amount,
            'updatedAt' => $date,
            'status' => 'paid',
            'currency' => 'USD',
        ];

        try {
            $result = importInvoice($db, $invoiceData);
            if ($result['success']) {
                echo "✅ Imported successfully! Revenue ID: {$result['revenue_id']}\n\n";
            } else {
                echo "⚠️  {$result['message']}\n\n";
            }
        } catch (\Exception $e) {
            echo "❌ Error: {$e->getMessage()}\n\n";
        }

        echo "Add another? (y/n): ";
        $continue = trim(fgets(STDIN));
        if (strtolower($continue) !== 'y') break;
        echo "\n";
    }

    echo "\n✅ Manual import complete!\n";
}

/**
 * Webhook method - uses the API endpoint
 */
function webhookImport(Database $db): void
{
    $webhookUrl = $_ENV['API_BASE_URL'] ?? 'https://api.candidstudios.net';
    $webhookUrl .= '/api/webhooks/revenue';

    echo "🌐 Webhook Import Method\n";
    echo "Webhook URL: {$webhookUrl}\n\n";

    echo "This method will call the webhook endpoint for each invoice.\n";
    echo "Enter CSV filename: ";
    $filename = trim(fgets(STDIN));

    if (!file_exists($filename)) {
        echo "❌ File not found\n";
        exit(1);
    }

    $handle = fopen($filename, 'r');
    $header = fgetcsv($handle);

    $imported = 0;
    $errors = 0;

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 6) continue;

        $payload = [
            'contactId' => $row[0],
            'altId' => $row[1],
            'name' => $row[2],
            'email' => $row[3],
            'amountPaid' => floatval($row[4]),
            'amount' => floatval($row[4]),
            'updatedAt' => $row[5],
            'status' => 'paid',
            'currency' => 'USD',
        ];

        // Call webhook
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            echo "✅ Imported \${$payload['amountPaid']} for {$payload['name']}\n";
            $imported++;
        } else {
            echo "❌ Error importing {$payload['name']}: {$response}\n";
            $errors++;
        }
    }

    fclose($handle);

    echo "\n✅ Webhook import complete! Imported: {$imported}, Errors: {$errors}\n";
}

/**
 * Import a single invoice
 */
function importInvoice(Database $db, array $data): array
{
    // Check for duplicate
    if (!empty($data['altId'])) {
        $existing = $db->queryOne(
            "SELECT id FROM revenue WHERE metadata->>'ghl_invoice_id' = ?",
            [$data['altId']]
        );

        if ($existing) {
            return [
                'success' => false,
                'message' => "Invoice {$data['altId']} already imported (Revenue ID: {$existing['id']})"
            ];
        }
    }

    // Find or create client
    $client = $db->queryOne(
        "SELECT id FROM clients WHERE ghl_contact_id = ?",
        [$data['contactId']]
    );

    if (!$client) {
        // Create client
        $nameParts = explode(' ', $data['name'], 2);
        $clientData = [
            'ghl_contact_id' => $data['contactId'],
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'email' => $data['email'] ?? null,
            'status' => 'active',
            'lifecycle_stage' => 'customer',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $clientId = $db->insert('clients', $clientData);
    } else {
        $clientId = $client['id'];
        // Update to customer
        $db->update('clients', [
            'lifecycle_stage' => 'customer',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $clientId]);
    }

    // Find or create project
    $project = $db->queryOne(
        "SELECT id, total_revenue FROM projects WHERE client_id = ? ORDER BY created_at DESC LIMIT 1",
        [$clientId]
    );

    $paymentDate = date('Y-m-d', strtotime($data['updatedAt']));

    if (!$project) {
        // Create project
        $projectData = [
            'client_id' => $clientId,
            'project_name' => $data['name'] ?? 'Imported Project',
            'booking_date' => $paymentDate,
            'event_date' => $paymentDate,
            'event_type' => 'other',
            'status' => 'booked',
            'total_revenue' => $data['amountPaid'],
            'metadata' => json_encode(['imported' => true, 'ghl_invoice_id' => $data['altId']]),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $projectId = $db->insert('projects', $projectData);
    } else {
        $projectId = $project['id'];
        // Add to existing revenue
        $newTotalRevenue = floatval($project['total_revenue']) + floatval($data['amountPaid']);
        $db->update('projects', [
            'total_revenue' => $newTotalRevenue,
            'status' => 'booked',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $projectId]);
    }

    // Insert revenue record
    $revenueData = [
        'project_id' => $projectId,
        'client_id' => $clientId,
        'payment_date' => $paymentDate,
        'amount' => $data['amountPaid'],
        'payment_method' => 'online',
        'payment_type' => floatval($data['amountPaid']) >= floatval($data['amount']) ? 'full' : 'deposit',
        'status' => 'completed',
        'metadata' => json_encode([
            'ghl_invoice_id' => $data['altId'],
            'currency' => $data['currency'] ?? 'USD',
            'total_invoice_amount' => $data['amount'],
            'amount_paid' => $data['amountPaid'],
            'imported' => true
        ]),
        'created_at' => date('Y-m-d H:i:s')
    ];

    $revenueId = $db->insert('revenue', $revenueData);

    // Refresh materialized views
    $db->getConnection()->exec('REFRESH MATERIALIZED VIEW CONCURRENTLY mv_priority_kpis');

    return [
        'success' => true,
        'revenue_id' => $revenueId,
        'project_id' => $projectId,
        'client_id' => $clientId
    ];
}
