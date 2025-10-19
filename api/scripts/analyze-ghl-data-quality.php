#!/usr/bin/env php
<?php

/**
 * Analyze GHL Data Quality
 * Shows percentage of contacts with complete vs incomplete data
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

echo "🔍 Analyzing GHL Data Quality (first 100 contacts)...\n\n";

$stats = [
    'total' => 0,
    'has_first_name' => 0,
    'has_last_name' => 0,
    'has_email' => 0,
    'has_phone' => 0,
    'complete' => 0, // Has first name, last name, AND email
    'samples_complete' => [],
    'samples_incomplete' => []
];

$url = 'https://services.leadconnectorhq.com/contacts/?locationId=' . $ghlLocationId . '&limit=100';

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
curl_close($ch);

$data = json_decode($response, true);
$contacts = $data['contacts'] ?? [];

foreach ($contacts as $contact) {
    $stats['total']++;

    $hasFirstName = !empty($contact['firstName']);
    $hasLastName = !empty($contact['lastName']);
    $hasEmail = !empty($contact['email']);
    $hasPhone = !empty($contact['phone']);

    if ($hasFirstName) $stats['has_first_name']++;
    if ($hasLastName) $stats['has_last_name']++;
    if ($hasEmail) $stats['has_email']++;
    if ($hasPhone) $stats['has_phone']++;

    $isComplete = $hasFirstName && $hasLastName && $hasEmail;

    if ($isComplete) {
        $stats['complete']++;
        if (count($stats['samples_complete']) < 3) {
            $stats['samples_complete'][] = [
                'id' => $contact['id'],
                'name' => trim(($contact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? '')),
                'email' => $contact['email'] ?? 'N/A',
                'phone' => $contact['phone'] ?? 'N/A'
            ];
        }
    } else {
        if (count($stats['samples_incomplete']) < 3) {
            $stats['samples_incomplete'][] = [
                'id' => $contact['id'],
                'firstName' => $contact['firstName'] ?? 'NULL',
                'lastName' => $contact['lastName'] ?? 'NULL',
                'email' => $contact['email'] ?? 'NULL',
                'phone' => $contact['phone'] ?? 'N/A',
                'contactName' => $contact['contactName'] ?? 'N/A'
            ];
        }
    }
}

echo "📊 Data Quality Report:\n";
echo str_repeat("=", 60) . "\n";
echo "Total Contacts Analyzed: {$stats['total']}\n\n";

echo "Contacts with First Name: {$stats['has_first_name']} (" . round($stats['has_first_name'] / $stats['total'] * 100, 1) . "%)\n";
echo "Contacts with Last Name:  {$stats['has_last_name']} (" . round($stats['has_last_name'] / $stats['total'] * 100, 1) . "%)\n";
echo "Contacts with Email:      {$stats['has_email']} (" . round($stats['has_email'] / $stats['total'] * 100, 1) . "%)\n";
echo "Contacts with Phone:      {$stats['has_phone']} (" . round($stats['has_phone'] / $stats['total'] * 100, 1) . "%)\n\n";

echo "COMPLETE Contacts (Name + Email): {$stats['complete']} (" . round($stats['complete'] / $stats['total'] * 100, 1) . "%)\n";
echo "INCOMPLETE Contacts: " . ($stats['total'] - $stats['complete']) . " (" . round(($stats['total'] - $stats['complete']) / $stats['total'] * 100, 1) . "%)\n\n";

if (!empty($stats['samples_complete'])) {
    echo "✅ Sample Complete Contacts:\n";
    foreach ($stats['samples_complete'] as $sample) {
        echo "  - {$sample['name']} ({$sample['email']}) | {$sample['phone']}\n";
    }
    echo "\n";
}

if (!empty($stats['samples_incomplete'])) {
    echo "❌ Sample Incomplete Contacts:\n";
    foreach ($stats['samples_incomplete'] as $sample) {
        echo "  - First: {$sample['firstName']} | Last: {$sample['lastName']} | Email: {$sample['email']}\n";
        echo "    Phone: {$sample['phone']} | ContactName: {$sample['contactName']}\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Total contacts in GHL: " . ($data['meta']['total'] ?? 'Unknown') . "\n";
