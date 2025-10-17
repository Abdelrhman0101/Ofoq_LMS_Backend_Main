<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use Illuminate\Support\Facades\Route;

Route::apiResource('categories', CategoryController::class);

Route::get('courses/details', [CourseController::class, 'details']);
Route::apiResource('courses', CourseController::class);

Route::post('courses/{course}/chapters', [ChapterController::class, 'store']);
Route::put('chapters/{chapter}', [ChapterController::class, 'update']);
Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy']);

Route::post('chapters/{chapter}/lessons', [LessonController::class, 'store']);
Route::put('lessons/{lesson}', [LessonController::class, 'update']);
Route::delete('lessons/{lesson}', [LessonController::class, 'destroy']);

Route::post('chapters/{chapter}/quiz', [QuizController::class, 'store']);

Route::post('quiz/{quiz}/questions', [QuestionController::class, 'store']);
Route::put('questions/{question}', [QuestionController::class, 'update']);
Route::delete('questions/{question}', [QuestionController::class, 'destroy']);