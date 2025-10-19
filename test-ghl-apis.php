#!/usr/bin/env php
<?php

/**
 * Test GHL APIs locally to find revenue data
 */

require __DIR__ . '/api/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/api');
$dotenv->safeLoad();

$ghlApiKey = $_ENV['GHL_API_KEY'] ?? '';
$ghlLocationId = $_ENV['GHL_LOCATION_ID'] ?? '';

if (empty($ghlApiKey) || empty($ghlLocationId)) {
    die("❌ GHL credentials not configured\n");
}

echo "🧪 Testing GHL APIs for Revenue Data\n";
echo str_repeat("=", 60) . "\n\n";

// Test 1: Contacts API (known to work)
echo "1️⃣  Testing Contacts API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://services.leadconnectorhq.com/contacts/?locationId={$ghlLocationId}&limit=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$ghlApiKey}",
    "Version: 2021-07-28",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   Status: {$httpCode}\n";
echo "   Works: " . ($httpCode === 200 ? "✅ YES" : "❌ NO") . "\n\n";

// Test 2: Invoices API
echo "2️⃣  Testing Invoices API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://services.leadconnectorhq.com/invoices/?locationId={$ghlLocationId}&limit=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$ghlApiKey}",
    "Version: 2021-07-28",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);
curl_close($ch);
echo "   Status: {$httpCode}\n";
echo "   Works: " . ($httpCode === 200 ? "✅ YES" : "❌ NO") . "\n";
if ($httpCode !== 200) {
    echo "   Error: " . json_encode($data) . "\n";
}
echo "\n";

// Test 3: Transactions API
echo "3️⃣  Testing Transactions API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://services.leadconnectorhq.com/payments/transactions?locationId={$ghlLocationId}&limit=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$ghlApiKey}",
    "Version: 2021-07-28",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);
curl_close($ch);
echo "   Status: {$httpCode}\n";
echo "   Works: " . ($httpCode === 200 ? "✅ YES" : "❌ NO") . "\n";
if ($httpCode === 200) {
    echo "   🎉 SUCCESS! Found revenue data source!\n";
    echo "   Sample: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   Error: " . json_encode($data) . "\n";
}
echo "\n";

// Test 4: Orders API
echo "4️⃣  Testing Orders API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://services.leadconnectorhq.com/orders/?locationId={$ghlLocationId}&limit=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$ghlApiKey}",
    "Version: 2021-07-28",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);
curl_close($ch);
echo "   Status: {$httpCode}\n";
echo "   Works: " . ($httpCode === 200 ? "✅ YES" : "❌ NO") . "\n";
if ($httpCode === 200) {
    echo "   🎉 SUCCESS! Found revenue data source!\n";
    echo "   Sample: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   Error: " . json_encode($data) . "\n";
}
echo "\n";

// Test 5: Payments API (different endpoint)
echo "5️⃣  Testing Payments API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://services.leadconnectorhq.com/payments/?locationId={$ghlLocationId}&limit=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$ghlApiKey}",
    "Version: 2021-07-28",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);
curl_close($ch);
echo "   Status: {$httpCode}\n";
echo "   Works: " . ($httpCode === 200 ? "✅ YES" : "❌ NO") . "\n";
if ($httpCode === 200) {
    echo "   🎉 SUCCESS! Found revenue data source!\n";
    echo "   Sample: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   Error: " . json_encode($data) . "\n";
}
echo "\n";

echo str_repeat("=", 60) . "\n";
echo "✅ Testing complete!\n";
