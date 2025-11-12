<?php

require_once 'vendor/autoload.php';

// Load Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

// Get the completed certificate
$certificate = DB::table('course_certificates')
    ->where('status', 'completed')
    ->first();

if (!$certificate) {
    echo "No completed certificate found.\n";
    exit(1);
}

echo "Found certificate ID: {$certificate->id}\n";
echo "Current verification_token: " . ($certificate->verification_token ?? 'NULL') . "\n";

// Generate UUID for verification token
$verificationToken = Str::uuid();

// Update with verification token
DB::table('course_certificates')
    ->where('id', $certificate->id)
    ->update([
        'verification_token' => $verificationToken,
    ]);

echo "Updated verification_token: {$verificationToken}\n";
echo "Certificate updated successfully!\n";