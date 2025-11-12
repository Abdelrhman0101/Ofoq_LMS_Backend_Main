<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CourseCertificate;
$cert = CourseCertificate::find(6);
if ($cert) {
    echo "Certificate ID: " . $cert->id . "\n";
    echo "File path: " . ($cert->file_path ?? 'NULL') . "\n";
    echo "Status: " . $cert->status . "\n";
    echo "Token: " . $cert->verification_token . "\n";
} else {
    echo "Certificate not found\n";
}