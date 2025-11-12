<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING COURSE_CERTIFICATES TABLE ===" . PHP_EOL;

try {
    $certificates = \DB::table('course_certificates')->limit(5)->get();
    echo "Found " . count($certificates) . " certificates" . PHP_EOL;
    
    foreach ($certificates as $cert) {
        echo "ID: {$cert->id} | User: {$cert->user_id} | Course: {$cert->course_id}";
        if (property_exists($cert, 'status')) {
            echo " | Status: {$cert->status}";
        } else {
            echo " | Status: NO STATUS FIELD";
        }
        echo PHP_EOL;
    }
    
    // Check table structure
    echo "\n=== TABLE STRUCTURE ===" . PHP_EOL;
    $columns = \DB::select("SHOW COLUMNS FROM course_certificates");
    foreach ($columns as $column) {
        echo "Field: {$column->Field} | Type: {$column->Type} | Null: {$column->Null} | Key: {$column->Key} | Default: " . ($column->Default ?? 'NULL') . PHP_EOL;
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}