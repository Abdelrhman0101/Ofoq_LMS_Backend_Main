<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\UserQuiz;

// Test the query
$userId = 21;
$courseId = 4;

echo "Testing UserQuiz query for user_id: $userId, course_id: $courseId\n";

try {
    $userQuiz = UserQuiz::join('quizzes', 'user_quizzes.quiz_id', '=', 'quizzes.id')
        ->where('user_quizzes.user_id', $userId)
        ->where('quizzes.course_id', $courseId)
        ->where('user_quizzes.score', '>=', 60)
        ->orderBy('user_quizzes.updated_at', 'desc')
        ->select('user_quizzes.score', 'user_quizzes.updated_at')
        ->first();

    if ($userQuiz) {
        echo "Found quiz! Score: {$userQuiz->score}, Updated: {$userQuiz->updated_at}\n";
    } else {
        echo "No quiz found for this user and course\n";
        
        // Let's see what we have
        echo "\nAll user quizzes for this user:\n";
        $allQuizzes = UserQuiz::where('user_id', $userId)->get();
        foreach ($allQuizzes as $quiz) {
            echo "Quiz ID: {$quiz->quiz_id}, Score: {$quiz->score}\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}