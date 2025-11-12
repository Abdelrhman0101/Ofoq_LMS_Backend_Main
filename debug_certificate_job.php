<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CourseCertificate;
use App\Jobs\GenerateCertificateJob;
use Illuminate\Support\Facades\Log;

// Get the latest certificate
$certificate = CourseCertificate::find(3);

echo "Certificate ID: " . $certificate->id . PHP_EOL;
echo "Status: " . $certificate->status . PHP_EOL;
echo "User ID: " . $certificate->user_id . PHP_EOL;
echo "Course ID: " . $certificate->course_id . PHP_EOL;

// Check if user and course exist
$user = $certificate->user;
$course = $certificate->course;

echo "User: " . ($user ? $user->name : 'NULL') . PHP_EOL;
echo "Course: " . ($course ? $course->title : 'NULL') . PHP_EOL;

// Try to run the job directly
try {
    echo "\nRunning job directly...\n";
    $job = new GenerateCertificateJob($certificate);
    $job->handle();
    
    echo "Job completed!\n";
    echo "New Status: " . $certificate->fresh()->status . PHP_EOL;
    echo "File Path: " . $certificate->fresh()->file_path . PHP_EOL;
    echo "Serial Number: " . $certificate->fresh()->serial_number . PHP_EOL;
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . PHP_EOL;
    echo "Line: " . $e->getLine() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}