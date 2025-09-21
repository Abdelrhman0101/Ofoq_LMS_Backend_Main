<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\UserCourseController;
use App\Http\Controllers\UserLessonController;
use App\Http\Controllers\UserQuizController;
use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
// Route::prefix('auth')->group(function () {
// });

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes done
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Admin routes
    Route::middleware('role:admin')->group(function () {
        // Courses done
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
        Route::post('/lessons/{lesson_id}/quiz', [LessonController::class, 'addQuizToLesson']);

        // Quizzes
        Route::post('/chapters/{chapter_id}/quiz', [QuizController::class, 'store']);
        Route::put('/quiz/{id}', [QuizController::class, 'update']);
        Route::delete('/quiz/{id}', [QuizController::class, 'destroy']);

        // Questions
        Route::post('/quiz/{quiz_id}/questions', [QuestionController::class, 'store']);
        Route::put('/questions/{id}', [QuestionController::class, 'update']);
        Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);
    });

    // User/Student routes (accessible to authenticated users)
    // Courses - Public browsing and enrollment
    Route::get('/courses', [UserCourseController::class, 'index']);
    Route::get('/courses/{id}', [UserCourseController::class, 'show']);
    Route::post('/courses/{id}/enroll', [UserCourseController::class, 'enroll']);
    Route::get('/my-enrollments', [UserCourseController::class, 'myEnrollments']);
    
    // Lessons - View and track progress
    Route::get('/lessons/{id}', [UserLessonController::class, 'show']);
    Route::post('/lessons/{id}/complete', [UserLessonController::class, 'complete']);
    Route::get('/courses/{courseId}/progress', [UserLessonController::class, 'getCourseProgress']);
    
    // Quizzes - Take quizzes and submit answers
    Route::get('/chapters/{chapterId}/quiz', [UserQuizController::class, 'getQuiz']);
    Route::post('/quiz/{quizId}/submit', [UserQuizController::class, 'submitQuiz']);
    Route::get('/quiz/{quizId}/attempts', [UserQuizController::class, 'getAttempts']);
    
    // Certificates - Generate and verify
    Route::get('/courses/{courseId}/certificate', [CertificateController::class, 'generateCertificate']);
    Route::get('/my-certificates', [CertificateController::class, 'myCertificates']);
    Route::post('/certificates/{certificateId}/regenerate', [CertificateController::class, 'regenerateCertificate']);
    
    // Admin-only certificate routes
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/certificates/bulk-generate', [CertificateController::class, 'bulkGenerate']);
    });
});

// Public certificate verification (no authentication required)
Route::get('/certificate/verify/{token}', [CertificateController::class, 'verifyCertificate']);