<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\CourseCertificate;
use App\Models\User;
use App\Models\Course;
use Carbon\Carbon;

try {
    // Create test user if doesn't exist
    $user = User::firstOrCreate([
        'email' => 'test@example.com'
    ], [
        'name' => 'Test User',
        'password' => bcrypt('password'),
        'email_verified_at' => Carbon::now()
    ]);

    // Create test course if doesn't exist
    $course = Course::firstOrCreate([
        'title' => 'Test Course'
    ], [
        'description' => 'Test course for certificate verification',
        'is_published' => true,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ]);

    // Create test certificate
    $certificate = CourseCertificate::firstOrCreate([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'serial_number' => 'TEST123456'
    ], [
        'status' => 'generated',
        'file_path' => '/test/path/certificate.pdf',
        'token' => 'test-token-123',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ]);

    echo "=== Test Certificate Created ===\n";
    echo "Certificate ID: " . $certificate->id . "\n";
    echo "Serial Number: " . $certificate->serial_number . "\n";
    echo "User: " . $user->name . "\n";
    echo "Course: " . $course->title . "\n";
    echo "Status: " . $certificate->status . "\n";

    // Now test the verification endpoint
    echo "\n=== Testing Certificate Verification ===\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/public/verify-certificate?serial_number=TEST123456');
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Status Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";

    // Parse response
    $data = json_decode($response, true);
    if ($data) {
        echo "\n=== Verification Result ===\n";
        echo "Valid: " . ($data['valid'] ? 'true' : 'false') . "\n";
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
        if (isset($data['data'])) {
            echo "Certificate Data:\n";
            echo "- Student: " . $data['data']['student_name'] . "\n";
            echo "- Course: " . $data['data']['course_title'] . "\n";
            echo "- Exam Grade: " . $data['data']['exam_grade'] . "\n";
            echo "- Exam Date: " . $data['data']['exam_date'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}