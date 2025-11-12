<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CourseCertificate;

// Get a real certificate with a token
$certificate = CourseCertificate::whereNotNull('verification_token')->first();

if (!$certificate) {
    echo "No certificates with tokens found in database.\n";
    exit;
}

echo "Testing certificate verification endpoint...\n";
echo "Certificate ID: {$certificate->id}\n";
echo "File path: {$certificate->file_path}\n";
echo "Token: {$certificate->verification_token}\n\n";

// Make request to public verification endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/course-certificate/verify/{$certificate->verification_token}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Content-Type: {$contentType}\n";
echo "Header size: {$headerSize}\n";

if (strpos($contentType, 'application/json') !== false) {
    $body = substr($response, $headerSize);
    echo "JSON Response: {$body}\n\n";
    
    $data = json_decode($body, true);
    echo "Response structure:\n";
    echo "Valid: " . ($data['valid'] ?? 'not set') . "\n";
    echo "Has certificate key: " . (isset($data['certificate']) ? 'yes' : 'no') . "\n";
    if (isset($data['certificate'])) {
        echo "Certificate keys: " . implode(', ', array_keys($data['certificate'])) . "\n";
        echo "File path: " . ($data['certificate']['file_path'] ?? 'not set') . "\n";
    }
} else if (strpos($contentType, 'application/pdf') !== false) {
    echo "✅ SUCCESS: Certificate PDF is being served directly!\n";
    echo "The certificate file exists and is accessible.\n";
} else {
    echo "Response (first 500 chars): " . substr($response, $headerSize, 500) . "\n";
}