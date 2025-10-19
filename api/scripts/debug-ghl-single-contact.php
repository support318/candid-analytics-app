#!/usr/bin/env php
<?php

/**
 * Debug GHL Single Contact API
 * Fetch a single contact by ID to see if we get more complete data
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$ghlApiKey = $_ENV['GHL_API_KEY'] ?? '';

if (empty($ghlApiKey)) {
    echo "❌ Error: GHL_API_KEY required\n";
    exit(1);
}

// First, get a contact ID from the list
echo "🔍 Getting contact ID from list...\n";
$listUrl = 'https://services.leadconnectorhq.com/contacts/?locationId=' . ($_ENV['GHL_LOCATION_ID'] ?? '') . '&limit=1';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $listUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $ghlApiKey,
        'Version: 2021-07-28',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$contactId = $data['contacts'][0]['id'] ?? null;

if (!$contactId) {
    echo "❌ No contact found\n";
    exit(1);
}

echo "✅ Found contact ID: $contactId\n\n";

// Now fetch that specific contact
echo "🔍 Fetching single contact details...\n";
$singleUrl = "https://services.leadconnectorhq.com/contacts/$contactId";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $singleUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $ghlApiKey,
        'Version: 2021-07-28',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status Code: $statusCode\n\n";

if ($statusCode !== 200) {
    echo "❌ API request failed\n";
    echo "Response: $response\n";
    exit(1);
}

$contactData = json_decode($response, true);

echo "📊 Single Contact Response:\n";
echo json_encode($contactData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n\n";

echo "First Name: " . ($contactData['contact']['firstName'] ?? 'MISSING') . "\n";
echo "Last Name: " . ($contactData['contact']['lastName'] ?? 'MISSING') . "\n";
echo "Email: " . ($contactData['contact']['email'] ?? 'MISSING') . "\n";
echo "Phone: " . ($contactData['contact']['phone'] ?? 'MISSING') . "\n";
