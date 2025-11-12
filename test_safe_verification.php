<?php

// Test the safe verification controller
echo "Testing Safe Certificate Verification Controller\n";
echo "==============================================\n\n";

// Test 1: Invalid serial number (should return 404)
echo "Test 1: Invalid serial number\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/public/verify-certificate?serial_number=INVALID123');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

// Test 2: Missing serial number (should return 422)
echo "Test 2: Missing serial number\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/public/verify-certificate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

echo "Tests completed!\n";
echo "The controller should now:\n";
echo "- Return 404 for invalid certificates\n";
echo "- Return 422 for missing parameters\n";
echo "- Never crash with 500 errors\n";
echo "- Handle missing data gracefully\n";