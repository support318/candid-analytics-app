#!/usr/bin/env php
<?php

/**
 * Debug GHL Opportunity Structure
 * Shows what fields are available in GHL opportunities
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

echo "🔍 Fetching first opportunity from GHL...\n\n";

$url = 'https://services.leadconnectorhq.com/opportunities/search?location_id=' . $ghlLocationId . '&limit=1';

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

echo "📊 Full Opportunity Response:\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n\n";

if (!empty($data['opportunities'][0])) {
    $opp = $data['opportunities'][0];
    echo "📋 First Opportunity Fields:\n";
    echo "Keys: " . implode(", ", array_keys($opp)) . "\n\n";

    echo "Name: " . ($opp['name'] ?? 'N/A') . "\n";
    echo "Status: " . ($opp['status'] ?? 'N/A') . "\n";
    echo "Monetary Value: " . ($opp['monetaryValue'] ?? 'N/A') . "\n";
    echo "Date Added: " . ($opp['dateAdded'] ?? 'N/A') . "\n";
    echo "Pipeline ID: " . ($opp['pipelineId'] ?? 'N/A') . "\n";
    echo "Stage ID: " . ($opp['pipelineStageId'] ?? 'N/A') . "\n";

    if (isset($opp['customFields'])) {
        echo "\nCustom Fields:\n";
        echo json_encode($opp['customFields'], JSON_PRETTY_PRINT);
    } else {
        echo "\nNo custom fields found\n";
    }
}
