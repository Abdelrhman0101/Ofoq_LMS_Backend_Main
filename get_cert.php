<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\CourseCertificate;

$cert = CourseCertificate::find(6);
if($cert) {
    echo 'Verification token: ' . $cert->verification_token . PHP_EOL;
    echo 'File path: ' . $cert->file_path . PHP_EOL;
    echo 'Status: ' . $cert->status . PHP_EOL;
} else {
    echo 'Certificate not found';
}