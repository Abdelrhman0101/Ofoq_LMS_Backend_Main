<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Course;

echo "Checking courses in database...\n";

// Get all courses
$courses = Course::orderBy('id', 'desc')->take(15)->get();

echo "Found " . $courses->count() . " courses:\n";
foreach ($courses as $course) {
    echo $course->id . " - " . $course->title . " - " . $course->created_at . "\n";
}

// Check for specific course IDs
$courseIds = [16, 17];
foreach ($courseIds as $courseId) {
    $course = Course::find($courseId);
    if ($course) {
        echo "\nCourse $courseId EXISTS: " . $course->title . "\n";
    } else {
        echo "\nCourse $courseId does NOT exist in database.\n";
    }
}