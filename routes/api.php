<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Courses
        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{id}', [CourseController::class, 'show']);
        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{id}', [CourseController::class, 'update']);
        Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

        // Chapters
        Route::post('/courses/{course_id}/chapters', [ChapterController::class, 'store']);
        Route::put('/chapters/{id}', [ChapterController::class, 'update']);
        Route::delete('/chapters/{id}', [ChapterController::class, 'destroy']);

        // Lessons
        Route::post('/chapters/{chapter_id}/lessons', [LessonController::class, 'store']);
        Route::put('/lessons/{id}', [LessonController::class, 'update']);
        Route::delete('/lessons/{id}', [LessonController::class, 'destroy']);

        // Quizzes
        Route::post('/chapters/{chapter_id}/quiz', [QuizController::class, 'store']);
        Route::put('/quiz/{id}', [QuizController::class, 'update']);
        Route::delete('/quiz/{id}', [QuizController::class, 'destroy']);

        // Questions
        Route::post('/quiz/{quiz_id}/questions', [QuestionController::class, 'store']);
        Route::put('/questions/{id}', [QuestionController::class, 'update']);
        Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);
    });

    // Student routes
    Route::middleware('role:student')->prefix('student')->group(function () {
        // Student-specific endpoints can be added here
        // For example: course enrollment, quiz attempts, etc.
    });
});