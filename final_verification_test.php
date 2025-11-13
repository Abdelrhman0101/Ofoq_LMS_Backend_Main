<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\DiplomaCertificate;
use Illuminate\Http\Request;

echo "🎓 Final Diploma Certificate Verification Test\n";
echo "==============================================\n\n";

try {
    // Get a diploma certificate
    $certificate = DiplomaCertificate::first();
    
    if (!$certificate) {
        throw new Exception("No diploma certificates found");
    }
    
    echo "📋 Certificate Details:\n";
    echo "- ID: {$certificate->id}\n";
    echo "- User ID: {$certificate->user_id}\n";
    echo "- Diploma ID: {$certificate->diploma_id}\n";
    echo "- Serial Number: {$certificate->serial_number}\n";
    echo "- Verification Token: {$certificate->verification_token}\n";
    echo "- File Path: {$certificate->file_path}\n";
    echo "- Status: {$certificate->status}\n\n";
    
    // Test the verification URL
    $verificationUrl = "http://localhost:8000/api/diploma-certificate/verify/{$certificate->verification_token}";
    echo "🔗 Testing Verification URL: {$verificationUrl}\n\n";
    
    // Test using Laravel's internal router
    $request = Request::create("/api/diploma-certificate/verify/{$certificate->verification_token}", 'GET');
    $response = app()->handle($request);
    
    echo "📊 Response Status: {$response->getStatusCode()}\n";
    echo "📄 Response Headers: " . json_encode($response->headers->all()) . "\n\n";
    
    if ($response->getStatusCode() === 200) {
        $content = $response->getContent();
        
        // Check if it's a PDF file or JSON response
        $contentType = $response->headers->get('Content-Type');
        echo "🎯 Content-Type: {$contentType}\n";
        
        if (strpos($contentType, 'application/pdf') !== false) {
            echo "✅ SUCCESS: PDF file returned!\n";
            echo "📊 File size: " . strlen($content) . " bytes\n";
        } elseif (strpos($contentType, 'application/json') !== false) {
            echo "✅ SUCCESS: JSON response returned!\n";
            $data = json_decode($content, true);
            echo "📊 Response data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "⚠️  Response content: " . substr($content, 0, 500) . "...\n";
        }
        
        echo "\n🎉 DIPLOMA CERTIFICATE VERIFICATION IS WORKING!\n";
        
    } else {
        echo "❌ FAILED: HTTP {$response->getStatusCode()}\n";
        echo "Response: " . $response->getContent() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}