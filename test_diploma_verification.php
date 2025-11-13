<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "🔍 Testing Diploma Certificate Verification\n";
echo "==========================================\n\n";

try {
    // Get a diploma certificate
    $certificate = DB::table('diploma_certificates')->first();
    
    if (!$certificate) {
        throw new Exception("No diploma certificates found");
    }
    
    echo "Found certificate:\n";
    echo "ID: {$certificate->id}\n";
    echo "User ID: {$certificate->user_id}\n";
    echo "Diploma ID: {$certificate->diploma_id}\n";
    echo "Serial Number: {$certificate->serial_number}\n";
    echo "Verification Token: {$certificate->verification_token}\n";
    echo "File Path: {$certificate->file_path}\n\n";
    
    // Test verification URL
    $baseUrl = env('APP_URL', 'http://localhost');
    $verificationUrl = "{$baseUrl}/api/diploma-certificate/verify/{$certificate->verification_token}";
    echo "Verification URL: {$verificationUrl}\n\n";
    
    // Test the verification endpoint
    echo "Testing verification endpoint...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $verificationUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    echo "HTTP Response Code: {$httpCode}\n";
    
    if ($httpCode === 200) {
        echo "✅ Verification endpoint is working!\n";
        
        // Extract headers and body
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        echo "Response headers:\n{$headers}\n";
        echo "Response body preview:\n" . substr($body, 0, 500) . "...\n";
        
    } else {
        echo "❌ Verification endpoint returned HTTP {$httpCode}\n";
        echo "Response: " . substr($response, 0, 500) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}