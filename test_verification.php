<?php
// Test the verification endpoint
$baseUrl = 'http://localhost:8000';

// Test with the verification token we just created
$verificationToken = '1651e0df-292a-cbcf-b0a2-e6e9af4ff595'; // This should be the token from our test certificate with non-existent file

// Test JSON response
$jsonUrl = $baseUrl . '/api/certificate/verify/' . $verificationToken;
echo "Testing JSON response: $jsonUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $jsonUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        if (isset($data['certificate']['file_path'])) {
            echo "✓ file_path found in JSON response: " . $data['certificate']['file_path'] . "\n";
        } else {
            echo "✗ file_path NOT found in JSON response\n";
            echo "Available keys in certificate: " . implode(', ', array_keys($data['certificate'] ?? [])) . "\n";
        }
    } else {
        echo "Failed to parse JSON response. Raw response: " . substr($response, 0, 200) . "...\n";
    }
} else {
    echo "Request failed with HTTP code: $httpCode\n";
    echo "Response: " . substr($response, 0, 500) . "...\n";
}

// Test PDF response
echo "\nTesting PDF response: $jsonUrl\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $jsonUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$pdfResponse = curl_exec($ch);
$pdfHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $pdfHttpCode\n";
if ($pdfHttpCode == 200) {
    // Check if it's a PDF
    if (substr($pdfResponse, 0, 4) === '%PDF') {
        echo "✓ PDF file returned successfully\n";
    } else {
        echo "Response is not a PDF file\n";
    }
} else {
    echo "PDF request failed with HTTP code: $pdfHttpCode\n";
}