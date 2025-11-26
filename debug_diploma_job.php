<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DiplomaCertificate;
use App\Jobs\GenerateDiplomaCertificateJob;
use Illuminate\Support\Facades\Log;

echo "--------------------------------------------------\n";
echo "Diploma Certificate Debugger\n";
echo "--------------------------------------------------\n";

// Get the latest diploma certificate or create a test one
$certificate = DiplomaCertificate::latest()->first();

if (!$certificate) {
    echo "[INFO] No existing DiplomaCertificate found. Attempting to create a TEST record...\n";
    
    $user = \App\Models\User::first();
    $category = \App\Models\CategoryOfCourse::first();
    
    if (!$user || !$category) {
        echo "[ERROR] Cannot create test certificate: No Users or Categories found in DB.\n";
        exit(1);
    }
    
    echo "[INFO] Creating test certificate for User: {$user->name} (ID: {$user->id}) and Diploma: {$category->name} (ID: {$category->id})\n";
    
    $certificate = DiplomaCertificate::create([
        'user_id' => $user->id,
        'diploma_id' => $category->id,
        'serial_number' => 'TEST-' . time(),
        'verification_token' => \Illuminate\Support\Str::uuid(),
        'status' => 'pending',
        'student_name' => $user->name,
        'certificate_data' => json_encode([
            'student_name' => $user->name,
            'diploma_name' => $category->name,
            'completion_date' => now()->format('Y-m-d'),
        ]),
    ]);
    
    echo "[INFO] Test certificate created with ID: {$certificate->id}\n";
}

echo "Certificate ID: " . $certificate->id . PHP_EOL;
echo "Status: " . $certificate->status . PHP_EOL;
echo "User ID: " . $certificate->user_id . PHP_EOL;
echo "Category ID: " . $certificate->category_id . PHP_EOL;

// Check if user and diploma exist
$user = $certificate->user;
$diploma = $certificate->diploma ?? $certificate->category; // Handle relation name variance

echo "User: " . ($user ? $user->name : 'NULL') . PHP_EOL;
echo "Diploma: " . ($diploma ? $diploma->name : 'NULL') . PHP_EOL;

// Try to run the job directly
try {
    echo "\nRunning GenerateDiplomaCertificateJob directly...\n";
    $job = new GenerateDiplomaCertificateJob($certificate);
    $job->handle();
    
    $certificate->refresh();
    echo "Job completed!\n";
    echo "New Status: " . $certificate->status . PHP_EOL;
    echo "File Path: " . $certificate->file_path . PHP_EOL;
    echo "Serial Number: " . $certificate->serial_number . PHP_EOL;
    
    if ($certificate->status === 'failed') {
        echo "[FAIL] Status is 'failed'. Check laravel.log for details.\n";
    } elseif (!empty($certificate->file_path) && file_exists(storage_path('app/public/' . $certificate->file_path))) {
        echo "[PASS] File generated successfully at: " . storage_path('app/public/' . $certificate->file_path) . "\n";
    } else {
        echo "[WARNING] Status is completed but file not found at expected path.\n";
    }
    
} catch (\Exception $e) {
    echo "[ERROR] Exception: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . PHP_EOL;
    echo "Line: " . $e->getLine() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
