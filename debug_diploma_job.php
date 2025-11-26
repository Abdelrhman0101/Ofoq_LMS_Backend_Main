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

// Get the latest diploma certificate or a specific one
// You can change the ID here to test a specific certificate
$certificate = DiplomaCertificate::latest()->first();

if (!$certificate) {
    echo "[ERROR] No DiplomaCertificate found in database.\n";
    exit(1);
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
