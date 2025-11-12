<?php

// Test the verification endpoint with GET request
$ch = curl_init();

// Set the URL with query parameters
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/public/verify-certificate?serial_number=TEST123');

// Set the request method to GET (this is the default)
curl_setopt($ch, CURLOPT_HTTPGET, true);

// Set the headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

// Return the response as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Close cURL
curl_close($ch);

// Output the results
echo "=== Certificate Verification Test (GET) ===\n";
echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";
if ($error) {
    echo "cURL Error: " . $error . "\n";
}

// Parse and display the response
$data = json_decode($response, true);
if ($data) {
    echo "\n=== Parsed Response ===\n";
    echo "Valid: " . ($data['valid'] ? 'true' : 'false') . "\n";
    if (isset($data['message'])) {
        echo "Message: " . $data['message'] . "\n";
    }
    if (isset($data['errors'])) {
        echo "Errors: " . json_encode($data['errors']) . "\n";
    }
    if (isset($data['data'])) {
        echo "Data: " . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n";
    }
}