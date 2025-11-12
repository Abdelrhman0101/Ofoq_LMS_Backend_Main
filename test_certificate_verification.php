<?php

// Test certificate verification endpoint (public - no auth required)
echo "Testing certificate verification endpoint...\n";

// Use the verification token from our successfully generated certificate
$verificationToken = 'af5e8c3e-1234-5678-9abc-def012345678'; // This would be the actual token from certificate ID 6

// Make request to public verification endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/certificate/verify/{$verificationToken}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response: {$response}\n\n";

// Test with a different approach - check if file_path is returned in verification
if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['certificate']['file_path'])) {
        echo "✅ SUCCESS: file_path is returned in verification response\n";
        echo "File path: " . $data['certificate']['file_path'] . "\n";
    } else {
        echo "❌ file_path not found in verification response\n";
    }
} else {
    echo "❌ Verification failed with HTTP {$httpCode}\n";
}

// Test storage link
echo "\nTesting storage link...\n";
$publicStoragePath = 'public/storage';
if (is_link($publicStoragePath) || is_dir($publicStoragePath)) {
    echo "✅ Storage link exists\n";
} else {
    echo "❌ Storage link does not exist\n";
}

// Check if certificates directory is accessible via public storage
echo "\nChecking certificates directory accessibility...\n";
$certificatesPath = 'public/storage/certificates';
if (is_dir($certificatesPath)) {
    echo "✅ Certificates directory is accessible via public storage\n";
    $files = scandir($certificatesPath);
    $pdfFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'pdf';
    });
    if (!empty($pdfFiles)) {
        echo "Found PDF files: " . implode(', ', $pdfFiles) . "\n";
    } else {
        echo "No PDF files found in public certificates directory\n";
    }
} else {
    echo "❌ Certificates directory not found in public storage\n";
}

echo "\nTest completed.\n";