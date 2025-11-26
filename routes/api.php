<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\ChapterController as AdminChapterController;
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
use App\Http\Controllers\DiplomaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\Admin\InstructorController as AdminInstructorController;
use App\Http\Controllers\Admin\FeaturedCourseController as AdminFeaturedCourseController;
use App\Http\Controllers\Admin\DiplomaCertificateController as AdminDiplomaCertificateController;
use App\Http\Controllers\LessonNoteController;
use App\Http\Controllers\Admin\BlockedUserController;
use App\Http\Controllers\UserCategoryEnrollmentController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\LessonProgressController;

// Public stats routes
Route::get('/stats/general', [StatsController::class, 'getGeneralStats']);
Route::get('/stats/students-by-country', [StatsController::class, 'getStudentsByCountry']);

// Public course routes
Route::get('/allCourses', [CourseController::class, 'search']);
Route::get('/course/{course}', [CourseController::class, 'show']);
Route::get('/courses/featured', [CourseController::class, 'featured']);
// دبلومات/فئات عامة (تسمية الاستراتيجية: categories)
Route::get('/diplomas', [DiplomaController::class, 'index']);
Route::get('/diplomas/{slug}', [DiplomaController::class, 'show']);
// Aliases per strategy naming
Route::get('/categories', [DiplomaController::class, 'index']);
Route::get('/categories/{slug}', [DiplomaController::class, 'show']);

// Public authentication routes
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Social authentication routes
Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// Public certificate verification route
Route::get('/public/verify-certificate', [CertificateVerificationController::class, 'verify']);

// Public reviews listing for a course
Route::get('/courses/{courseId}/reviews', [ReviewController::class, 'index']);

// Public: check lesson status by token and lesson id
Route::get('/lessons/{lesson}/status', [LessonProgressController::class, 'status']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Sanctum protected route to fetch current user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Protected authentication routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/picture', [AuthController::class, 'updateProfilePicture']);
    Route::post('/profile/password', [AuthController::class, 'changePassword']);
    // Aliases for user profile endpoints
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    // Favorite Courses
    Route::get('/user/favorite-courses', [UserFavoriteCourseController::class, 'index']);
    Route::post('/user/favorite-courses/{course}', [UserFavoriteCourseController::class, 'store']);
    Route::delete('/user/favorite-courses/{course}', [UserFavoriteCourseController::class, 'destroy']);

    // Admin routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Courses
        Route::post('/courses', [AdminCourseController::class, 'store']);
        Route::put('/courses/{course}', [AdminCourseController::class, 'update']);
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy']);
        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::get('/courses/{id}', [AdminCourseController::class, 'show']); // added alias for admin course details
        Route::get('/course/{id}', [AdminCourseController::class, 'show']);
        Route::get('/courses-details', [AdminCourseController::class, 'details']);
        Route::get('/courses-not-published', [AdminCourseController::class, 'getOnlyCoursesNotPublished']);

        // Instructors (Admin)
        Route::get('/instructors', [AdminInstructorController::class, 'index']);
        Route::get('/instructors/{instructor}', [AdminInstructorController::class, 'show']);
        Route::post('/instructors', [AdminInstructorController::class, 'store']);
        Route::put('/instructors/{instructor}', [AdminInstructorController::class, 'update']);
        Route::delete('/instructors/{instructor}', [AdminInstructorController::class, 'destroy']);

        // Featured Courses (Admin only)
        // Route::get('/featured-courses', [AdminFeaturedCourseController::class, 'index']);
        // Route::get('/featured-courses/{featuredCourse}', [AdminFeaturedCourseController::class, 'show']);
        Route::post('/featured-courses', [AdminFeaturedCourseController::class, 'store']);
        // Route::put('/featured-courses/{featuredCourse}', [AdminFeaturedCourseController::class, 'update']);
        Route::delete('/featured-courses/{featuredCourse}', [AdminFeaturedCourseController::class, 'destroy']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        // Chapters
        Route::post('/courses/{course}/chapters', [AdminChapterController::class, 'store']);
        Route::put('/chapters/{id}', [ChapterController::class, 'update']);
        Route::delete('/chapters/{id}', [ChapterController::class, 'destroy']);

        // Lessons
        Route::get('/chapters/{chapter}/lessons', [LessonController::class, 'index']);
        Route::post('/chapters/{chapter_id}/lessons', [LessonController::class, 'store']);
        Route::put('/lessons/{id}', [LessonController::class, 'update']);
        Route::delete('/lessons/{id}', [LessonController::class, 'destroy']);
        Route::post('/lessons/{lesson_id}/quiz', [LessonController::class, 'addQuizToLesson']);

        // Quizzes
        Route::post('/chapters/{chapter_id}/quiz', [QuizController::class, 'store']);
        Route::put('/quiz/{id}', [QuizController::class, 'update']);
        Route::delete('/quiz/{id}', [QuizController::class, 'destroy']);

        // Questions (existing singular path)
        Route::post('/quiz/{quiz_id}/questions', [QuestionController::class, 'store']);
        Route::put('/questions/{id}', [QuestionController::class, 'update']);
        Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);

        // Questions (plural alias to match frontend and docs)
        Route::get('/quizzes/{quiz}/questions', [AdminQuestionController::class, 'index']);
        Route::post('/quizzes/{quiz}/questions', [AdminQuestionController::class, 'store']);

        // Blocked Users
        Route::get('/admin/blocked-users', [BlockedUserController::class, 'index']);
        Route::post('/admin/blocked-users', [BlockedUserController::class, 'store']);
        // Route::put('/admin/blocked-users/{blockedUser}', [BlockedUserController::class, 'update']);
        Route::delete('/admin/blocked-users/{blockedUser}', [BlockedUserController::class, 'destroy']);

        // Admin Stats
        Route::get('/stats/students', [StatsController::class, 'getStudentStats']);

        // Database Backups Management
        Route::get('/backups', [BackupController::class, 'index']);
        Route::post('/backups/create', [BackupController::class, 'create']);
        Route::post('/backups/upload', [BackupController::class, 'upload']);
        Route::post('/backups/restore', [BackupController::class, 'restore']);
        Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->where('filename', '.*');
        Route::delete('/backups/{filename}', [BackupController::class, 'delete'])->where('filename', '.*');

        // Diploma Certificates Management (Admin)
        Route::get('/diploma-certificates/student', [AdminDiplomaCertificateController::class, 'searchStudentDiplomas']);
        Route::post('/diplomas/{diploma}/certificates/generate', [AdminDiplomaCertificateController::class, 'generateForStudent']);
    });

    // User/Student routes (accessible to authenticated users)
    Route::get('/courseEnroll/{course}', [CourseController::class, 'show']);
    Route::post('/courses/{id}/enroll', [UserCourseController::class, 'enroll']);
    Route::get('/my-enrollments/filters', [UserCourseController::class, 'myEnrollmentFilters']);
    Route::get('/my-enrollments', [UserCourseController::class, 'myEnrollments']);
    // Student exams page: list courses and final exam status
    Route::get('/my-tests', [FinalExamController::class, 'myTests']);

    // Diplomas enrollment
    Route::post('/categories/{category}/enroll', [UserCategoryEnrollmentController::class, 'enroll']);
    Route::post('/categories/{category}/enroll/activate', [UserCategoryEnrollmentController::class, 'activate']);
    Route::get('/my-diplomas', [UserCategoryEnrollmentController::class, 'myDiplomas']);

    // Lessons - View and track progress
    Route::get('/lessons/{id}', [UserLessonController::class, 'show']);
    // Lessons navigation info (prev/next, is_next_last)
    Route::get('/lessons/{id}/navigation', [UserLessonController::class, 'navigation']);
    Route::post('/lessons/{id}/complete', [UserLessonController::class, 'complete']);
    Route::get('/courses/{courseId}/progress', [UserLessonController::class, 'getCourseProgress']);

    // Lesson Notes - students can write notes per lesson
    Route::get('/lessons/{lessonId}/notes', [LessonNoteController::class, 'index']);
    Route::post('/lessons/{lessonId}/notes', [LessonNoteController::class, 'store']);
    Route::put('/lessons/{lessonId}/notes/{noteId}', [LessonNoteController::class, 'update']);
    Route::delete('/lessons/{lessonId}/notes/{noteId}', [LessonNoteController::class, 'destroy']);

    // Quizzes - Take quizzes and submit answers
    Route::get('/chapters/{chapterId}/quiz', [UserQuizController::class, 'getQuiz']);
    Route::get('/lessons/{lessonId}/quiz', [UserQuizController::class, 'getLessonQuiz']);
    Route::post('/quiz/{quizId}/submit', [UserQuizController::class, 'submitQuiz']);
    Route::get('/quiz/{quizId}/attempts', [UserQuizController::class, 'getAttempts']);

    // Reviews - Create review for a course
    Route::post('/courses/{courseId}/reviews', [ReviewController::class, 'store']);
    Route::put('/courses/{courseId}/reviews/{reviewId}', [ReviewController::class, 'update']);
    Route::delete('/courses/{courseId}/reviews/{reviewId}', [ReviewController::class, 'destroy']);

    // Certificates - Generate and verify
    Route::get('/courses/{courseId}/certificate', [CertificateController::class, 'generateCertificate']);
    // Request certificate (new certificate request system)
    Route::post('/courses/{courseId}/request-certificate', [CertificateController::class, 'requestCertificate']);
    // Get certificate status (for frontend to know initial state)
    Route::get('/courses/{courseId}/certificate-status', [CertificateController::class, 'getCertificateStatus']);
    // Certificate data only (frontend will render PDF)
    Route::get('/courses/{courseId}/certificate/data', [CertificateController::class, 'getCourseCertificateData']);
    Route::get('/my-certificates', [CertificateController::class, 'myCertificates']);
    Route::post('/certificates/{certificateId}/regenerate', [CertificateController::class, 'regenerateCertificate']);
    // Update file path after frontend generates PDF
    Route::post('/certificates/{certificateId}/file', [CertificateController::class, 'updateCertificateFilePath']);
    // Diploma certificate download (student)
    Route::get('/categories/{category}/certificate', [CertificateController::class, 'downloadDiplomaCertificate']);
    // Update diploma certificate file path after frontend generates PDF
    Route::post('/diploma-certificates/{certificateId}/file', [CertificateController::class, 'updateDiplomaCertificateFilePath']);
    // Diploma certificate data only (frontend will render PDF)
    Route::get('/categories/{category}/certificate/data', [CertificateController::class, 'getDiplomaCertificateData']);

    // Admin-only certificate routes
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/certificates/bulk-generate', [CertificateController::class, 'bulkGenerate']);
    });

    // External certificate generation tool routes
    Route::prefix('external')->group(function () {
        // Get pending certificates for PDF generation
        Route::get('/certificates/pending', [CertificateController::class, 'getPendingCertificates']);
        // Update certificate with generated PDF file path
        Route::post('/certificates/{certificateId}/file', [CertificateController::class, 'updateCertificateFilePathExternal']);
    });

    // Final Exam
    Route::get('/courses/{course}/final-exam/meta', [FinalExamController::class, 'meta'])
        ->withoutMiddleware('throttle:api')
        ->middleware('throttle:final_exam_meta');
    Route::get('/courses/{course}/final-exam/attempt/active', [FinalExamController::class, 'activeAttempt']);
    Route::post('/courses/{course}/final-exam/start', [FinalExamController::class, 'start']);
    Route::post('/courses/{course}/final-exam/cancel/{attempt}', [FinalExamController::class, 'cancel']);
    Route::post('/courses/{course}/final-exam/submit/{attempt}', [FinalExamController::class, 'submit']);
    }); // end not_blocked group


// Public certificate verification (no authentication required)
Route::get('/certificate/verify/{token}', [CertificateController::class, 'verifyCertificate']);
Route::get('/course-certificate/verify/{token}', [CertificateController::class, 'verifyCourseCertificate']);
// Public diploma certificate verification (serve PDF or redirect to external URL)
Route::get('/diploma-certificate/verify/{token}', function ($token) {
    $cert = \App\Models\DiplomaCertificate::where('verification_token', $token)
        ->with(['user', 'diploma'])
        ->first();

    if (!$cert) {
        return response()->json(['valid' => false, 'message' => 'Diploma certificate not found or invalid token'], 404);
    }

    // If file_path is external URL, redirect
    if (!empty($cert->file_path) && preg_match('/^https?:\/\//i', $cert->file_path)) {
        return redirect()->away($cert->file_path);
    }

    // If file exists locally, stream it
    if (!empty($cert->file_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($cert->file_path)) {
        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($cert->file_path);
        $name = "Diploma_{$cert->diploma->name}_{$cert->user->name}.pdf";
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $name . '"'
        ]);
    }

    // Fallback: return certificate data JSON
    $data = json_decode($cert->certificate_data, true) ?? [];
    return response()->json([
        'valid' => true,
        'certificate' => [
            'id' => $cert->id,
            'user_name' => $cert->user->name,
            'diploma_name' => $cert->diploma->name,
            'issued_at' => optional($cert->issued_at)->format('F d, Y'),
            'completion_date' => $data['completion_date'] ?? null,
            'verification_token' => $cert->verification_token,
            'serial_number' => $cert->serial_number,
            'file_path' => $cert->file_path,
        ],
    ]);
});
