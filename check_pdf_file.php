<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$filePath = 'certificates/diploma_media-basics-diploma_user_1.pdf';
$fullPath = storage_path('app/public/' . $filePath);

echo "🔍 Checking PDF File\n";
echo "====================\n\n";

echo "File path: {$fullPath}\n";

if (file_exists($fullPath)) {
    echo "✅ File exists!\n";
    echo "File size: " . number_format(filesize($fullPath) / 1024, 2) . " KB\n";
    echo "File modified: " . date('Y-m-d H:i:s', filemtime($fullPath)) . "\n";
} else {
    echo "❌ File not found\n";
    
    // Check if certificates directory exists
    $dir = dirname($fullPath);
    if (file_exists($dir)) {
        echo "✅ Directory exists: {$dir}\n";
        $files = scandir($dir);
        echo "Files in directory:\n";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "   - {$file}\n";
            }
        }
    } else {
        echo "❌ Directory not found: {$dir}\n";
    }
}