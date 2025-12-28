<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$backupHistory = App\Models\BackupHistory::latest()->first();

if (!$backupHistory) {
    echo "No backup history found.\n";
    exit(1);
}

$path = storage_path('app/private/' . $backupHistory->filename);
echo "Checking backup file: $path\n";

if (!file_exists($path)) {
    echo "File not found!\n";
    exit(1);
}

$zip = new ZipArchive;
if ($zip->open($path) === TRUE) {
    echo "Zip opened successfully. Num files: " . $zip->numFiles . "\n";
    $found = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (str_contains($name, 'test_image.txt')) {
            echo "FOUND IMAGE: " . $name . "\n";
            $found = true;
        }
        if (str_contains($name, 'ofuqCopy.sql')) {
             echo "FOUND DB: " . $name . "\n";
        }
    }
    if (!$found) {
        echo "Image file NOT found in zip.\n";
    }
    $zip->close();
} else {
    echo "Failed to open zip.\n";
}
