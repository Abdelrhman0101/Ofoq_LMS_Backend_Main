<?php

// Test script for diploma certificate PDF generation
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\DiplomaCertificate;
use App\Jobs\GenerateDiplomaCertificateJob;

echo "🧪 Testing Diploma Certificate PDF Generation\n";
echo "===========================================\n\n";

try {
    // Check if diploma certificates table exists
    if (!Schema::hasTable('diploma_certificates')) {
        throw new Exception("❌ Diploma certificates table not found");
    }
    echo "✅ Diploma certificates table exists\n";

    // Check if any certificates exist
    $certificateCount = DiplomaCertificate::count();
    echo "📊 Found {$certificateCount} diploma certificates in database\n";

    if ($certificateCount === 0) {
        echo "⚠️  No certificates found. Please run the eligibility test first to create certificates.\n";
        echo "   Run: php test_diploma_eligibility.php\n";
        exit(1);
    }

    // Get a pending certificate to test PDF generation
    $certificate = DiplomaCertificate::where('status', 'pending')
        ->with(['user', 'diploma'])
        ->first();

    if (!$certificate) {
        echo "⚠️  No pending certificates found. Creating a test certificate...\n";
        
        // Create a test certificate if none exists
        $user = \App\Models\User::first();
        $diploma = \App\Models\CategoryOfCourse::first();
        
        if (!$user || !$diploma) {
            throw new Exception("❌ No users or diplomas found in database");
        }

        $certificate = DiplomaCertificate::create([
            'user_id' => $user->id,
            'diploma_id' => $diploma->id,
            'serial_number' => 'TEST-' . date('Y') . '-' . strtoupper(uniqid()),
            'file_path' => 'certificates/diplomas/test_diploma.pdf',
            'status' => 'pending',
            'verification_token' => \Illuminate\Support\Str::uuid()->toString(),
            'student_name' => $user->name,
            'issued_at' => now(),
            'certificate_data' => [
                'diploma_name' => $diploma->name,
                'student_name' => $user->name,
                'issued_date' => now()->toDateString(),
                'total_courses_completed' => 5,
            ]
        ]);
        
        echo "✅ Created test certificate with ID: {$certificate->id}\n";
    }

    echo "📋 Testing certificate: {$certificate->serial_number}\n";
    echo "   Student: {$certificate->student_name}\n";
    echo "   Diploma: {$certificate->diploma->name}\n";
    echo "   Status: {$certificate->status}\n\n";

    // Test the job directly
    echo "🔧 Testing GenerateDiplomaCertificateJob...\n";
    
    try {
        $job = new GenerateDiplomaCertificateJob($certificate);
        $job->handle();
        
        echo "✅ Job executed successfully\n";
        
        // Refresh certificate data
        $certificate->refresh();
        echo "   New status: {$certificate->status}\n";
        echo "   File path: {$certificate->file_path}\n";
        
        // Check if file was created
        $filePath = storage_path('app/public/' . $certificate->file_path);
        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
            echo "✅ PDF file created successfully\n";
            echo "   File size: " . number_format($fileSize / 1024, 2) . " KB\n";
            echo "   File location: {$filePath}\n";
        } else {
            echo "⚠️  PDF file not found at expected location: {$filePath}\n";
        }
        
    } catch (Exception $jobException) {
        echo "❌ Job failed: " . $jobException->getMessage() . "\n";
        echo "   File: " . $jobException->getFile() . "\n";
        echo "   Line: " . $jobException->getLine() . "\n";
        echo "   Trace: " . $jobException->getTraceAsString() . "\n";
    }

    echo "\n🎯 Testing Job Dispatch from Controller...\n";
    
    // Test the job dispatch (simulating the controller)
    try {
        \App\Jobs\GenerateDiplomaCertificateJob::dispatch($certificate);
        echo "✅ Job dispatched successfully\n";
    } catch (Exception $dispatchException) {
        echo "❌ Job dispatch failed: " . $dispatchException->getMessage() . "\n";
    }

    echo "\n📊 Final Certificate Status:\n";
    echo "============================\n";
    $finalCertificate = DiplomaCertificate::find($certificate->id);
    echo "Certificate ID: {$finalCertificate->id}\n";
    echo "Serial Number: {$finalCertificate->serial_number}\n";
    echo "Status: {$finalCertificate->status}\n";
    echo "File Path: {$finalCertificate->file_path}\n";
    echo "Verification Token: {$finalCertificate->verification_token}\n";
    echo "Issued At: {$finalCertificate->issued_at}\n";

    echo "\n✅ Diploma PDF generation test completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}