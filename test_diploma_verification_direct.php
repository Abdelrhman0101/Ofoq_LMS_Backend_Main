<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

echo "🔍 Testing Diploma Certificate Verification Directly\n";
echo "==================================================\n\n";

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
    
    // Test the verification route directly
    echo "Testing verification route directly...\n";
    
    // Create a request to the verification route
    $request = Request::create("/api/diploma-certificate/verify/{$certificate->verification_token}", 'GET');
    
    // Get the router and dispatch the request
    $router = app('router');
    $response = $router->dispatch($request);
    
    echo "Response Status: {$response->getStatusCode()}\n";
    echo "Response Headers: " . json_encode($response->headers->all()) . "\n";
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Verification route is working!\n";
        echo "Response Content-Type: " . $response->headers->get('Content-Type') . "\n";
        
        // Check if it's a file response or JSON
        if ($response->headers->get('Content-Type') === 'application/pdf') {
            echo "✅ PDF file is being served correctly!\n";
        } else {
            $content = $response->getContent();
            echo "Response body preview:\n" . substr($content, 0, 500) . "...\n";
        }
    } else {
        echo "❌ Verification route returned HTTP {$response->getStatusCode()}\n";
        $content = $response->getContent();
        echo "Response: " . substr($content, 0, 500) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}