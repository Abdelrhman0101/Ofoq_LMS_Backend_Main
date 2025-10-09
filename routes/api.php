<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FinalExamController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\UserCourseController;
use App\Http\Controllers\UserLessonController;
use App\Http\Controllers\UserQuizController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserFavoriteCourseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InstructorController as AdminInstructorController;
use App\Http\Controllers\Admin\FeaturedCourseController as AdminFeaturedCourseController;

// Authentication routes (public)
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Public course routes
Route::get('/allCourses', [CourseController::class, 'search']);
Route::get('/course/{course}', [CourseController::class, 'show']);
Route::get('/courses/featured', [CourseController::class, 'featured']);

// Public reviews listing for a course
Route::get('/courses/{courseId}/reviews', [ReviewController::class, 'index']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Favorite Courses
    Route::get('/user/favorite-courses', [UserFavoriteCourseController::class, 'index']);
    Route::post('/user/favorite-courses/{course}', [UserFavoriteCourseController::class, 'store']);
    Route::delete('/user/favorite-courses/{course}', [UserFavoriteCourseController::class, 'destroy']);

    // Admin routes
    Route::middleware('role:admin')->group(function () {
        // Courses
        Route::post('/courses', [AdminCourseController::class, 'store']);
        Route::put('/courses/{course}', [AdminCourseController::class, 'update']);
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy']);
        Route::get('/admin/courses', [AdminCourseController::class, 'index']);
        Route::get('/admin/course/{id}', [AdminCourseController::class, 'show']);
        Route::get('/admin/courses-details', [AdminCourseController::class, 'details']);
        Route::get('/admin/courses-not-published', [AdminCourseController::class, 'getOnlyCoursesNotPublished']);

        // Instructors (Admin)
        Route::get('/admin/instructors', [AdminInstructorController::class, 'index']);
        Route::get('/admin/instructors/{instructor}', [AdminInstructorController::class, 'show']);
        Route::post('/admin/instructors', [AdminInstructorController::class, 'store']);
        Route::put('/admin/instructors/{instructor}', [AdminInstructorController::class, 'update']);
        Route::delete('/admin/instructors/{instructor}', [AdminInstructorController::class, 'destroy']);

        // Featured Courses (Admin only)
        // Route::get('/admin/featured-courses', [AdminFeaturedCourseController::class, 'index']);
        // Route::get('/admin/featured-courses/{featuredCourse}', [AdminFeaturedCourseController::class, 'show']);
        Route::post('/admin/featured-courses', [AdminFeaturedCourseController::class, 'store']);
        // Route::put('/admin/featured-courses/{featuredCourse}', [AdminFeaturedCourseController::class, 'update']);
        Route::delete('/admin/featured-courses/{featuredCourse}', [AdminFeaturedCourseController::class, 'destroy']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
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

        Route::get ('/admin/student-courses-status', [UserCourseController::class, 'studentCoursesStatus']);
    });

    // User/Student routes (accessible to authenticated users)
    Route::get('/courseEnroll/{course}', [CourseController::class, 'show']);
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

    // Reviews - Create review for a course
    Route::post('/courses/{courseId}/reviews', [ReviewController::class, 'store']);
    Route::put('/courses/{courseId}/reviews/{reviewId}', [ReviewController::class, 'update']);
    Route::delete('/courses/{courseId}/reviews/{reviewId}', [ReviewController::class, 'destroy']);

    // Certificates - Generate and verify
    Route::get('/courses/{courseId}/certificate', [CertificateController::class, 'generateCertificate']);
    Route::get('/my-certificates', [CertificateController::class, 'myCertificates']);
    Route::post('/certificates/{certificateId}/regenerate', [CertificateController::class, 'regenerateCertificate']);

    // Admin-only certificate routes
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/certificates/bulk-generate', [CertificateController::class, 'bulkGenerate']);
    });

    // Final Exam
    Route::post('/courses/{course}/final-exam/start', [FinalExamController::class, 'start']);
    Route::post('/courses/{course}/final-exam/submit/{attempt}', [FinalExamController::class, 'submit']);
});

// Public certificate verification (no authentication required)
Route::get('/certificate/verify/{token}', [CertificateController::class, 'verifyCertificate']);
