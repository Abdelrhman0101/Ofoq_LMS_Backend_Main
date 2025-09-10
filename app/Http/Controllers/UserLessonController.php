<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\UserCourse;
use App\Models\UserLessonProgress;
use App\Http\Resources\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserLessonController extends Controller
{
    /**
     * Display a lesson and update user progress
     */
    public function show($id)
    {
        $lesson = Lesson::with(['chapter.course'])->find($id);
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found'
            ], 404);
        }

        $user = Auth::user();
        $course = $lesson->chapter->course;
        
        // Check if user is enrolled in the course
        $enrollment = UserCourse::where('user_id', $user->id)
                               ->where('course_id', $course->id)
                               ->first();
        
        if (!$enrollment) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Track lesson progress
        $lessonProgress = UserLessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $id
            ],
            [
                'status' => 'in_progress',
                'started_at' => now()
            ]
        );

        // If lesson wasn't completed before, mark as in progress
        if ($lessonProgress->status === 'not_started') {
            $lessonProgress->update([
                'status' => 'in_progress',
                'started_at' => now()
            ]);
        }

        // Update course progress
        $this->updateCourseProgress($user->id, $course->id);

        return response()->json([
            'lesson' => new LessonResource($lesson),
            'progress' => [
                'status' => $lessonProgress->status,
                'started_at' => $lessonProgress->started_at,
                'completed_at' => $lessonProgress->completed_at
            ]
        ]);
    }

    /**
     * Mark lesson as completed
     */
    public function complete($id)
    {
        $lesson = Lesson::with(['chapter.course'])->find($id);
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found'
            ], 404);
        }

        $user = Auth::user();
        $course = $lesson->chapter->course;
        
        // Check if user is enrolled
        $enrollment = UserCourse::where('user_id', $user->id)
                               ->where('course_id', $course->id)
                               ->first();
        
        if (!$enrollment) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Mark lesson as completed
        $lessonProgress = UserLessonProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $id
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
                'started_at' => now() // In case it wasn't started before
            ]
        );

        // Update course progress
        $this->updateCourseProgress($user->id, $course->id);

        return response()->json([
            'message' => 'Lesson marked as completed',
            'progress' => [
                'status' => $lessonProgress->status,
                'started_at' => $lessonProgress->started_at,
                'completed_at' => $lessonProgress->completed_at
            ]
        ]);
    }

    /**
     * Update course progress based on completed lessons
     */
    private function updateCourseProgress($userId, $courseId)
    {
        // Get total lessons in course
        $totalLessons = Lesson::whereHas('chapter', function($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })->count();

        // Get completed lessons by user
        $completedLessons = UserLessonProgress::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereHas('lesson.chapter', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->count();

        // Calculate progress percentage
        $progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;

        // Update user course progress
        $userCourse = UserCourse::where('user_id', $userId)
                               ->where('course_id', $courseId)
                               ->first();

        if ($userCourse) {
            $status = 'in_progress';
            $completedAt = null;

            // If all lessons are completed, mark course as completed
            if ($progressPercentage >= 100) {
                $status = 'completed';
                $completedAt = now();
            }

            $userCourse->update([
                'progress_percentage' => $progressPercentage,
                'status' => $status,
                'completed_at' => $completedAt
            ]);
        }
    }

    /**
     * Get user's lesson progress for a course
     */
    public function getCourseProgress($courseId)
    {
        $user = Auth::user();
        
        // Check if user is enrolled
        $enrollment = UserCourse::where('user_id', $user->id)
                               ->where('course_id', $courseId)
                               ->first();
        
        if (!$enrollment) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Get lesson progress
        $lessonProgress = UserLessonProgress::where('user_id', $user->id)
            ->whereHas('lesson.chapter', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->with('lesson')
            ->get();

        return response()->json([
            'course_progress' => [
                'overall_progress' => $enrollment->progress_percentage,
                'status' => $enrollment->status,
                'completed_at' => $enrollment->completed_at,
                'lessons' => $lessonProgress->map(function($progress) {
                    return [
                        'lesson_id' => $progress->lesson_id,
                        'lesson_title' => $progress->lesson->title,
                        'status' => $progress->status,
                        'started_at' => $progress->started_at,
                        'completed_at' => $progress->completed_at
                    ];
                })
            ]
        ]);
    }
}