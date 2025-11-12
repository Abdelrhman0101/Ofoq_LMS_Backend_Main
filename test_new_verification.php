<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\CourseCertificate;

$serial = '4W5QFLR';
echo "Testing serial number: $serial\n";

// Test case-insensitive search
$cert = CourseCertificate::whereRaw('LOWER(serial_number) = LOWER(?)', [$serial])
    ->whereIn('status', ['generated', 'completed'])
    ->first();

if ($cert) {
    echo "Certificate found!\n";
    echo "Serial: " . $cert->serial_number . "\n";
    echo "Status: " . $cert->status . "\n";
    echo "Student ID: " . $cert->user_id . "\n";
    echo "Course ID: " . $cert->course_id . "\n";
    
    // Test with user and course
    $student = $cert->user;
    $course = $cert->course;
    
    if ($student && $course) {
        echo "Student: " . $student->name . "\n";
        echo "Course: " . $course->title . "\n";
    } else {
        echo "Missing student or course data\n";
    }
} else {
    echo "Certificate not found!\n";
    
    // Let's see what we have
    echo "\nAll certificates:\n";
    $all = CourseCertificate::whereIn('status', ['generated', 'completed'])->get();
    foreach ($all as $c) {
        echo "ID: {$c->id}, Serial: {$c->serial_number}, Status: {$c->status}\n";
    }
}