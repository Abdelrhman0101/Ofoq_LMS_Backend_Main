<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\FeaturedCourseController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\LessonController; // use base LessonController for lesson endpoints
use App\Http\Controllers\Admin\CategoryFinalExamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CertificateController;

// This file is already loaded with prefix `api/admin` and middleware `api`, `auth:sanctum`, `role:admin` via RouteServiceProvider.
// Define routes relative to that prefix without re-wrapping groups.

// Students management
Route::get('students', [UserController::class, 'getStudentsWithDiplomas']);

// Chapters
Route::get('courses/{course}/chapters', [ChapterController::class, 'index']);
Route::post('courses/{course}/chapters', [ChapterController::class, 'store']);
Route::get('courses/{course}/chapters/{chapter}', [ChapterController::class, 'show']);
Route::put('courses/{course}/chapters/{chapter}', [ChapterController::class, 'update']);
Route::delete('courses/{course}/chapters/{chapter}', [ChapterController::class, 'destroy']);

// Lessons for a chapter (GET index)
Route::get('chapters/{chapter}/lessons', [LessonController::class, 'index']);

// Lessons CRUD
Route::post('chapters/{chapter}/lessons', [LessonController::class, 'store']);
Route::put('lessons/{lesson}', [LessonController::class, 'update']);
Route::delete('lessons/{lesson}', [LessonController::class, 'destroy']);

// Quiz management for lessons
Route::post('lessons/{lesson}/quiz', [LessonController::class, 'addQuizToLesson']);

// Diploma (Category) Final Exam
Route::get('categories/{category}/final-exam', [CategoryFinalExamController::class, 'show']);
Route::post('categories/{category}/final-exam', [CategoryFinalExamController::class, 'store']);
Route::delete('users/{user}', [UserController::class, 'destroy']);
Route::get('users/{user}/certificates', [CertificateController::class, 'userCertificatesAdmin']);