<?php

// Simple test to verify certificate using Laravel's HTTP client
$baseUrl = 'http://localhost:8000';
$endpoint = '/api/public/verify-certificate';
$serialNumber = '4W5QFLR';

// Create a simple POST request
$ch = curl_init($baseUrl . $endpoint);

// Set cURL options
$payload = json_encode(['serial_number' => $serialNumber]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Close cURL
curl_close($ch);

// Output results
echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";
if ($error) {
    echo "cURL Error: " . $error . "\n";
}