<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Monitoring course creation requests...\n";
echo "Current courses count: " . \App\Models\Course::count() . "\n";
echo "Latest course ID: " . (\App\Models\Course::latest()->first()->id ?? 'None') . "\n";
echo "\nWaiting for new course creation...\n";

// Monitor for new courses every 2 seconds
$lastCount = \App\Models\Course::count();
$lastId = \App\Models\Course::latest()->first()->id ?? 0;

while (true) {
    sleep(2);
    
    $currentCount = \App\Models\Course::count();
    $currentLatest = \App\Models\Course::latest()->first();
    $currentId = $currentLatest->id ?? 0;
    
    if ($currentCount > $lastCount || $currentId > $lastId) {
        echo "\n🎉 NEW COURSE DETECTED!\n";
        echo "Course ID: " . $currentLatest->id . "\n";
        echo "Title: " . $currentLatest->title . "\n";
        echo "Created at: " . $currentLatest->created_at . "\n";
        echo "Instructor ID: " . $currentLatest->instructor_id . "\n";
        echo "Category ID: " . $currentLatest->category_id . "\n";
        echo "Price: " . $currentLatest->price . "\n";
        echo "Is Free: " . ($currentLatest->is_free ? 'Yes' : 'No') . "\n";
        echo "Duration: " . $currentLatest->duration . "\n";
        echo "---\n";
        
        $lastCount = $currentCount;
        $lastId = $currentId;
    }
    
    echo ".";
}