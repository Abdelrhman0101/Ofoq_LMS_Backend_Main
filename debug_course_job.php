<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CourseCertificate;
use App\Jobs\GenerateCertificateJob;
use Illuminate\Support\Facades\Log;

echo "--------------------------------------------------\n";
echo "Course Certificate Debugger\n";
echo "--------------------------------------------------\n";

// Get the latest course certificate or create a test one
$certificate = CourseCertificate::latest()->first();

if (!$certificate) {
    echo "[INFO] No existing CourseCertificate found. Attempting to create a TEST record...\n";
    
    $user = \App\Models\User::first();
    $course = \App\Models\Course::first();
    
    if (!$user || !$course) {
        echo "[ERROR] Cannot create test certificate: No Users or Courses found in DB.\n";
        exit(1);
    }

    // Find or create user course enrollment
    $userCourse = \App\Models\UserCourse::firstOrCreate(
        ['user_id' => $user->id, 'course_id' => $course->id],
        [
            'status' => 'completed', 
            'completed_at' => now(),
            'progress_percentage' => 100,
            'final_exam_score' => 100
        ]
    );
    
    echo "[INFO] Creating test certificate for User: {$user->name} (ID: {$user->id}) and Course: {$course->title} (ID: {$course->id})\n";
    
    $certificate = CourseCertificate::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'user_course_id' => $userCourse->id,
        'serial_number' => 'TEST-COURSE-' . time(),
        'verification_token' => \Illuminate\Support\Str::uuid(),
        'status' => 'pending',
        'student_name' => $user->name,
        'certificate_data' => json_encode([
            'student_name' => $user->name,
            'course_title' => $course->title,
            'completion_date' => now()->format('Y-m-d'),
        ]),
    ]);
    
    echo "[INFO] Test certificate created with ID: {$certificate->id}\n";
}

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
    echo "\nRunning GenerateCertificateJob directly...\n";
    $job = new GenerateCertificateJob($certificate);
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
