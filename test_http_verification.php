<?php

// Test the certificate verification endpoint via HTTP
$serial_number = '4W5QFLR';

echo "Testing certificate verification for serial: $serial_number\n";

$data = json_encode(['serial_number' => $serial_number]);

$ch = curl_init('http://localhost:8000/api/public/verify-certificate');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $result\n";

// Parse the JSON response
$response_data = json_decode($result, true);
if ($response_data) {
    echo "Parsed Response:\n";
    print_r($response_data);
}