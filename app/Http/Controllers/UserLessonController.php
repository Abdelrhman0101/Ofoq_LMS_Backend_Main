<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\UserCourse;
use App\Models\UserLessonProgress;
use App\Http\Resources\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserCategoryEnrollment;
use App\Models\Course;

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
        
        // تحديث التحقق: السماح إذا كان مسجلاً مباشرة أو مسجلاً بشكل نشط في الدبلومة الأم
        $hasCourseEnrollment = UserCourse::where('user_id', $user->id)
                                        ->where('course_id', $course->id)
                                        ->exists();

        $hasActiveDiplomaEnrollment = false;
        if (!empty($course->category_id)) {
            $hasActiveDiplomaEnrollment = UserCategoryEnrollment::where('user_id', $user->id)
                ->where('category_id', $course->category_id)
                ->where('status', 'active')
                ->exists();
        }
        
        if (!($hasCourseEnrollment || $hasActiveDiplomaEnrollment)) {
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
            'message' => 'Lesson fetched successfully',
            'lesson' => new LessonResource($lesson),
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
        
        // تحقق الوصول: تسجيل مباشر أو تسجيل دبلومة نشط
        $hasCourseEnrollment = UserCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        $hasActiveDiplomaEnrollment = false;
        if (!empty($course->category_id)) {
            $hasActiveDiplomaEnrollment = UserCategoryEnrollment::where('user_id', $user->id)
                ->where('category_id', $course->category_id)
                ->where('status', 'active')
                ->exists();
        }

        if (!($hasCourseEnrollment || $hasActiveDiplomaEnrollment)) {
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

        // إذا لا يوجد سجل للمقرر لكن الوصول عبر الدبلومة نشط، أنشئ سجلًا لبدء التقدم
        if (!$userCourse) {
            $course = Course::find($courseId);
            if ($course && !empty($course->category_id)) {
                $hasActiveDiplomaEnrollment = UserCategoryEnrollment::where('user_id', $userId)
                    ->where('category_id', $course->category_id)
                    ->where('status', 'active')
                    ->exists();
                if ($hasActiveDiplomaEnrollment) {
                    $userCourse = UserCourse::create([
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'status' => 'in_progress',
                        'progress_percentage' => 0,
                    ]);
                }
            }
        }

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
        $course = Course::find($courseId);
        
        // تحقق الوصول: تسجيل مباشر أو تسجيل دبلومة نشط
        $hasCourseEnrollment = UserCourse::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        $hasActiveDiplomaEnrollment = false;
        if ($course && !empty($course->category_id)) {
            $hasActiveDiplomaEnrollment = UserCategoryEnrollment::where('user_id', $user->id)
                ->where('category_id', $course->category_id)
                ->where('status', 'active')
                ->exists();
        }

        if (!($hasCourseEnrollment || $hasActiveDiplomaEnrollment)) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // تأكد من وجود سجل المقرر وتحديث التقدم
        $this->updateCourseProgress($user->id, $courseId);
        $enrollment = UserCourse::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

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
                        'quiz_passed' => (bool) ($progress->quiz_passed ?? false),
                        'started_at' => $progress->started_at,
                        'completed_at' => $progress->completed_at
                    ];
                })
            ]
        ]);
    }

    /**
     * Get navigation info for a lesson: previous/next and whether next is the last lesson
     */
    public function navigation($id)
    {
        $lesson = Lesson::with(['chapter.course'])->find($id);

        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found'
            ], 404);
        }

        $user = Auth::user();
        $course = $lesson->chapter->course;

        // تحقق الوصول: تسجيل مباشر أو تسجيل دبلومة نشط
        $hasCourseEnrollment = UserCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        $hasActiveDiplomaEnrollment = false;
        if (!empty($course->category_id)) {
            $hasActiveDiplomaEnrollment = UserCategoryEnrollment::where('user_id', $user->id)
                ->where('category_id', $course->category_id)
                ->where('status', 'active')
                ->exists();
        }

        if (!($hasCourseEnrollment || $hasActiveDiplomaEnrollment)) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // بناء قائمة الدروس مرتبة حسب ترتيب الفصول ثم الدروس للمسجلين في المقرر
        $orderedLessons = Lesson::query()
            ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
            ->where('chapters.course_id', $course->id)
            ->orderByRaw('COALESCE(chapters.`order`, chapters.id)')
            ->orderByRaw('COALESCE(lessons.`order`, lessons.id)')
            ->select(['lessons.id'])
            ->get();

        $ids = $orderedLessons->pluck('id');
        $count = $ids->count();
        $currentIndex = $ids->search($lesson->id);

        if ($currentIndex === false) {
            // إذا كان الدرس غير مرئي حاليًا أو غير موجود ضمن القائمة المرئية
            return response()->json([
                'message' => 'Lesson is not available for navigation',
            ], 400);
        }

        $prevLessonId = $currentIndex > 0 ? $ids[$currentIndex - 1] : null;
        $nextLessonId = $currentIndex < ($count - 1) ? $ids[$currentIndex + 1] : null;
        $lastLessonId = $count > 0 ? $ids[$count - 1] : null;

        $isLastLesson = $lesson->id === $lastLessonId;
        $isNextLast = $nextLessonId !== null && $nextLessonId === $lastLessonId;

        return response()->json([
            'message' => 'Navigation fetched successfully',
            'data' => [
                'current_lesson_id' => $lesson->id,
                'prev_lesson_id' => $prevLessonId,
                'next_lesson_id' => $nextLessonId,
                'last_lesson_id' => $lastLessonId,
                'is_last_lesson' => $isLastLesson,
                'is_next_last' => $isNextLast,
                'finish_course_available' => $isNextLast,
            ]
        ]);
    }
}