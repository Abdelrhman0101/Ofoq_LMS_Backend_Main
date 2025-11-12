<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Quiz;

echo "Checking quiz structure...\n";

try {
    $quiz = Quiz::first();
    if ($quiz) {
        echo "Found quiz!\n";
        echo "Quizzable type: " . $quiz->quizzable_type . "\n";
        echo "Quizzable ID: " . $quiz->quizzable_id . "\n";
        
        // Try to get the quizzable model
        $quizzable = $quiz->quizzable;
        if ($quizzable) {
            echo "Quizzable class: " . get_class($quizzable) . "\n";
            echo "Quizzable data: " . json_encode($quizzable->toArray()) . "\n";
        } else {
            echo "No quizzable model found\n";
        }
    } else {
        echo "No quizzes found\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}