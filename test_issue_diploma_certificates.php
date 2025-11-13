<?php

// Test script to directly call the issue certificates API
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\CategoryOfCourse;
use App\Models\User;
use App\Models\DiplomaCertificate;

echo "🎯 Testing Diploma Certificate Issuance\n";
echo "=====================================\n\n";

try {
    // Check if diploma certificates table exists
    if (!Schema::hasTable('diploma_certificates')) {
        throw new Exception("❌ Diploma certificates table not found");
    }
    echo "✅ Diploma certificates table exists\n";

    // Get a test diploma
    $diploma = CategoryOfCourse::first();
    if (!$diploma) {
        throw new Exception("❌ No diplomas found in database");
    }
    
    echo "📋 Using diploma: {$diploma->name} (ID: {$diploma->id})\n";
    
    // Check current certificate count
    $initialCount = DiplomaCertificate::count();
    echo "📊 Initial certificate count: {$initialCount}\n\n";
    
    // Create a mock request for the controller
    $request = new \Illuminate\Http\Request();
    
    // Create controller instance
    $eligibilityService = new \App\Services\DiplomaEligibilityService();
    $controller = new \App\Http\Controllers\Admin\DiplomaCertificateController($eligibilityService);
    
    echo "🔧 Calling issueCertificates method...\n";
    
    // Call the issue certificates method
    $response = $controller->issueCertificates($request, $diploma);
    
    echo "✅ Method executed successfully\n";
    echo "📊 Response status: {$response->getStatusCode()}\n";
    
    // Parse response data
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success']) {
        echo "✅ Certificates issued successfully\n";
        echo "📈 Certificates issued: {$responseData['data']['total_issued']}\n";
        echo "⚠️  Errors: {$responseData['data']['total_errors']}\n";
        
        if (!empty($responseData['data']['errors'])) {
            echo "\n❌ Error details:\n";
            foreach ($responseData['data']['errors'] as $error) {
                echo "   - {$error}\n";
            }
        }
        
        if (!empty($responseData['data']['issued_certificates'])) {
            echo "\n✅ Issued certificates:\n";
            foreach ($responseData['data']['issued_certificates'] as $cert) {
                echo "   - {$cert['student_name']} ({$cert['serial_number']})\n";
            }
        }
        
        // Check final count
        $finalCount = DiplomaCertificate::count();
        echo "\n📊 Final certificate count: {$finalCount}\n";
        echo "📈 New certificates created: " . ($finalCount - $initialCount) . "\n";
        
        // Check if any certificates were created and their status
        $newCertificates = DiplomaCertificate::where('status', 'pending')->get();
        if ($newCertificates->isNotEmpty()) {
            echo "\n📋 New pending certificates:\n";
            foreach ($newCertificates as $cert) {
                echo "   - {$cert->serial_number} for {$cert->student_name}\n";
                echo "     Status: {$cert->status}\n";
                echo "     File path: {$cert->file_path}\n";
            }
        }
        
    } else {
        echo "❌ Failed to issue certificates\n";
        echo "Error: {$responseData['message']}\n";
        if (isset($responseData['error'])) {
            echo "Details: {$responseData['error']}\n";
        }
    }
    
    echo "\n✅ Diploma certificate issuance test completed!\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}