<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "🔍 Checking Laravel Logs\n";
echo "========================\n\n";

$logFile = storage_path('logs/laravel.log');

if (file_exists($logFile)) {
    echo "✅ Log file exists: {$logFile}\n";
    echo "File size: " . number_format(filesize($logFile) / 1024, 2) . " KB\n\n";
    
    echo "Latest 50 lines:\n";
    echo "=================\n";
    
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    
    foreach ($lastLines as $line) {
        echo $line;
    }
} else {
    echo "❌ Log file not found: {$logFile}\n";
    
    // Check if logs directory exists
    $logsDir = dirname($logFile);
    if (file_exists($logsDir)) {
        echo "✅ Logs directory exists: {$logsDir}\n";
        $files = scandir($logsDir);
        echo "Files in logs directory:\n";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "   - {$file}\n";
            }
        }
    } else {
        echo "❌ Logs directory not found: {$logsDir}\n";
    }
}