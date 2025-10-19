#!/usr/bin/env php
<?php

/**
 * Debug GHL API Response Structure
 * Shows what data GHL actually returns from the contacts endpoint
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$ghlApiKey = $_ENV['GHL_API_KEY'] ?? '';
$ghlLocationId = $_ENV['GHL_LOCATION_ID'] ?? '';

if (empty($ghlApiKey) || empty($ghlLocationId)) {
    echo "❌ Error: GHL_API_KEY and GHL_LOCATION_ID required\n";
    exit(1);
}

echo "🔍 Fetching contact data from GHL API...\n\n";

// Fetch first contact
$url = 'https://services.leadconnectorhq.com/contacts/?locationId=' . $ghlLocationId . '&limit=1';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
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

$data = json_decode($response, true);

echo "📊 Full API Response:\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// Show what fields are present in first contact
if (!empty($data['contacts'][0])) {
    $contact = $data['contacts'][0];
    echo "📋 First Contact Keys:\n";
    echo implode(", ", array_keys($contact)) . "\n\n";

    echo "First Name: " . ($contact['firstName'] ?? $contact['first_name'] ?? 'MISSING') . "\n";
    echo "Last Name: " . ($contact['lastName'] ?? $contact['last_name'] ?? 'MISSING') . "\n";
    echo "Email: " . ($contact['email'] ?? 'MISSING') . "\n";
    echo "Phone: " . ($contact['phone'] ?? 'MISSING') . "\n";
}
