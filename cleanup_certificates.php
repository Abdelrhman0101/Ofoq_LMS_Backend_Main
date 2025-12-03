<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Certificate;
use App\Models\CourseCertificate;
use App\Models\DiplomaCertificate;

// 1. Delete files from storage
echo "Deleting certificate files...\n";
Storage::disk('public')->deleteDirectory('certificates');
Storage::disk('public')->makeDirectory('certificates/courses');
Storage::disk('public')->makeDirectory('certificates/diplomas');
echo "Files deleted.\n";

// 2. Truncate tables
echo "Truncating database tables...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
Certificate::truncate();
CourseCertificate::truncate();
DiplomaCertificate::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo "Tables truncated.\n";

echo "All certificates have been cleared successfully.\n";
