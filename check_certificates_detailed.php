<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CourseCertificate;

// Get all certificates
$certificates = CourseCertificate::limit(5)->get();

echo "Certificates in database:\n";
echo "Total certificates: " . CourseCertificate::count() . "\n\n";

foreach ($certificates as $certificate) {
    echo "ID: {$certificate->id}\n";
    echo "User ID: {$certificate->user_id}\n";
    echo "Course ID: {$certificate->course_id}\n";
    echo "Status: {$certificate->status}\n";
    echo "Verification Token: " . ($certificate->verification_token ?? 'NULL') . "\n";
    echo "File Path: " . ($certificate->file_path ?? 'NULL') . "\n";
    echo "Serial Number: " . ($certificate->serial_number ?? 'NULL') . "\n";
    echo "Created: {$certificate->created_at}\n";
    echo "---\n";
}