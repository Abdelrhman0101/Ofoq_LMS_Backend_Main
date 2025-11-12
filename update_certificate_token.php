<?php

require_once 'vendor/autoload.php';

use App\Models\CourseCertificate;
use Illuminate\Support\Str;

// Get the completed certificate
$certificate = CourseCertificate::where('status', 'completed')->first();

if (!$certificate) {
    echo "No completed certificate found.\n";
    exit(1);
}

echo "Found certificate ID: {$certificate->id}\n";
echo "Current verification_token: " . ($certificate->verification_token ?? 'NULL') . "\n";

// Update with verification token
$certificate->update([
    'verification_token' => Str::uuid(),
]);

echo "Updated verification_token: {$certificate->verification_token}\n";
echo "Certificate updated successfully!\n";