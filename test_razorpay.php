<?php
// Simple test script to verify Razorpay configuration
require_once 'vendor/autoload.php';

echo "=== Razorpay Configuration Test ===\n\n";

// Check if config file exists
$configFile = __DIR__ . '/config/payment.php';
if (!file_exists($configFile)) {
    echo "❌ Payment config file not found: $configFile\n";
    exit(1);
}

// Load config
$config = require $configFile;
echo "✅ Payment config file loaded\n";

// Check credentials
if (empty($config['key_id'])) {
    echo "❌ RAZORPAY_KEY_ID is not set\n";
} else {
    echo "✅ RAZORPAY_KEY_ID: " . substr($config['key_id'], 0, 10) . "...\n";
}

if (empty($config['key_secret'])) {
    echo "❌ RAZORPAY_KEY_SECRET is not set\n";
} else {
    echo "✅ RAZORPAY_KEY_SECRET: " . substr($config['key_secret'], 0, 10) . "...\n";
}

if (empty($config['key_id']) || empty($config['key_secret'])) {
    echo "\n❌ Razorpay credentials are not properly configured!\n";
    echo "Please set the following environment variables:\n";
    echo "- RAZORPAY_KEY_ID\n";
    echo "- RAZORPAY_KEY_SECRET\n";
    exit(1);
}

// Test API connectivity
echo "\n=== Testing Razorpay API Connectivity ===\n";

$url = 'https://api.razorpay.com/v1/orders';
$data = [
    'amount' => 100, // 1 INR in paise
    'currency' => 'INR',
    'receipt' => 'test_' . time(),
    'notes' => [
        'test' => 'true'
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($config['key_id'] . ':' . $config['key_secret'])
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($curlError) {
    echo "Curl Error: $curlError\n";
}

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if (isset($result['id'])) {
        echo "✅ Razorpay API test successful! Order ID: " . $result['id'] . "\n";
    } else {
        echo "❌ Unexpected response format\n";
    }
} else {
    echo "❌ Razorpay API test failed\n";
    $errorResponse = json_decode($response, true);
    if (isset($errorResponse['error']['description'])) {
        echo "Error: " . $errorResponse['error']['description'] . "\n";
    }
}

echo "\n=== Test Complete ===\n"; 