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

echo "Found certificate:\n";
echo "ID: {$certificate->id}\n";
echo "Token: {$certificate->verification_token}\n";
echo "Status: {$certificate->status}\n";
echo "File Path: {$certificate->file_path}\n\n";

// Test certificate verification endpoint (public - no auth required)
echo "Testing certificate verification endpoint...\n";

// Make request to public verification endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/course-certificate/verify/{$certificate->verification_token}");
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
        
        // Test if the file is accessible
        $fileUrl = "http://localhost:8000/storage/{$data['certificate']['file_path']}";
        echo "File URL: {$fileUrl}\n";
        
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $fileUrl);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_NOBODY, true); // HEAD request
        curl_exec($ch2);
        $fileHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        if ($fileHttpCode === 200) {
            echo "✅ Certificate file is accessible at URL\n";
        } else {
            echo "❌ Certificate file is not accessible (HTTP {$fileHttpCode})\n";
        }
    } else {
        echo "❌ file_path not found in verification response\n";
    }
} else {
    echo "❌ Verification failed with HTTP {$httpCode}\n";
}

echo "\nTest completed.\n";