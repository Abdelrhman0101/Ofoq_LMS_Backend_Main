<?php
// Simple script to check if certificates exist and test verification endpoint

echo "Testing certificate verification endpoint...\n";

// Test with a dummy token first to see the response format
echo "\n1. Testing with dummy token:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/certificate/verify/dummy-token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $body . "\n";

curl_close($ch);

echo "\n2. Checking if storage link exists:\n";
if (file_exists('public/storage')) {
    echo "Storage link exists\n";
} else {
    echo "Storage link does not exist\n";
}

echo "\n3. Checking certificates directory:\n";
if (file_exists('storage/app/public/certificates')) {
    echo "Certificates directory exists\n";
    $files = scandir('storage/app/public/certificates');
    echo "Files in certificates directory: " . count($files) . "\n";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "  - " . $file . "\n";
        }
    }
} else {
    echo "Certificates directory does not exist\n";
}

echo "\nTest completed.\n";