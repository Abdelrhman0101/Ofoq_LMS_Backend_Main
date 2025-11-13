<?php

// Setup test scenario for diploma certificates
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CategoryOfCourse;
use App\Models\Course;
use App\Models\UserCategoryEnrollment;
use App\Models\UserCourse;

echo "🎯 Setting up Test Scenario for Diploma Certificates\n";
echo "=================================================\n\n";

try {
    // Check basic data
    $users = User::limit(3)->get();
    $diplomas = CategoryOfCourse::limit(3)->get();
    $courses = Course::limit(3)->get();
    
    echo "📊 Database Status:\n";
    echo "   Users: {$users->count()}\n";
    echo "   Diplomas: {$diplomas->count()}\n";
    echo "   Courses: {$courses->count()}\n\n";
    
    if ($users->isEmpty() || $diplomas->isEmpty() || $courses->isEmpty()) {
        throw new Exception("❌ Insufficient test data. Need at least one user, diploma, and course.");
    }
    
    // Show available data
    echo "📝 Available Data:\n";
    foreach ($users as $user) {
        echo "   User: {$user->name} (ID: {$user->id})\n";
    }
    foreach ($diplomas as $diploma) {
        echo "   Diploma: {$diploma->name} (ID: {$diploma->id})\n";
    }
    foreach ($courses as $course) {
        echo "   Course: {$course->title} (ID: {$course->id})\n";
    }
    echo "\n";
    
    // Use first available data
    $user = $users->first();
    $diploma = $diplomas->first();
    $course = $courses->first();
    
    echo "🎯 Selected for Test:\n";
    echo "   User: {$user->name}\n";
    echo "   Diploma: {$diploma->name}\n";
    echo "   Course: {$course->title}\n\n";
    
    // Link course to diploma if not already linked
    if (!$course->category_id) {
        $course->category_id = $diploma->id;
        $course->save();
        echo "✅ Linked course to diploma\n";
    }
    
    // Enroll user in diploma
    $enrollment = UserCategoryEnrollment::firstOrCreate([
        'user_id' => $user->id,
        'category_id' => $diploma->id,
    ], [
        'status' => 'active',
        'completed_at' => null,
    ]);
    
    if ($enrollment->wasRecentlyCreated) {
        echo "✅ Enrolled user in diploma\n";
    } else {
        echo "ℹ️  User already enrolled in diploma\n";
    }
    
    // Enroll user in course and mark as completed
    $userCourse = UserCourse::firstOrCreate([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ], [
        'status' => 'completed',
        'progress_percentage' => 100,
        'final_exam_score' => 85, // Passing score
        'completed_at' => now(),
    ]);
    
    if ($userCourse->wasRecentlyCreated) {
        echo "✅ Enrolled user in course and marked as completed\n";
    } else {
        // Update existing enrollment
        $userCourse->status = 'completed';
        $userCourse->progress_percentage = 100;
        $userCourse->final_exam_score = 85;
        $userCourse->completed_at = now();
        $userCourse->save();
        echo "✅ Updated course enrollment to completed\n";
    }
    
    echo "\n🎉 Test scenario setup completed!\n";
    echo "\n📋 Summary:\n";
    echo "   User enrolled in diploma: ✅\n";
    echo "   User completed course: ✅\n";
    echo "   Course linked to diploma: ✅\n";
    echo "   Ready for eligibility check: ✅\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}