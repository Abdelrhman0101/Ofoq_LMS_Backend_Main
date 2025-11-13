<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\Lesson;
use App\Models\UserLessonProgress;
use App\Models\UserCategoryEnrollment;

class LessonProgressController extends Controller
{
    /**
     * Check a user's lesson status using token and lesson id.
     * Accepts token via query param `token` or Authorization Bearer.
     * Returns: completed | in_progress | not_enrolled
     */
    public function status(Request $request, Lesson $lesson)
    {
        // Get token from query or Authorization header
        $tokenString = $request->input('token') ?: $request->bearerToken();
        if (!$tokenString) {
            return response()->json([
                'message' => 'Missing token',
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($tokenString);
        if (!$accessToken) {
            return response()->json([
                'message' => 'Invalid token',
            ], 401);
        }

        $user = $accessToken->tokenable;

        // Ensure relationships are loaded to traverse diploma via course
        $lesson->load(['chapter.course.category']);
        $course = optional($lesson->chapter)->course;
        $diploma = optional($course)->category; // CategoryOfCourse acts as diploma

        // If lesson is not attached properly to a course/diploma, treat as not enrolled
        if (!$course || !$diploma) {
            return response()->json([
                'status' => 'not_enrolled',
                'lesson_id' => $lesson->id,
                'course_id' => optional($course)->id,
                'diploma_id' => optional($diploma)->id,
                'user_id' => $user->id,
            ], 200);
        }

        // Check diploma enrollment
        $isEnrolledInDiploma = UserCategoryEnrollment::where('user_id', $user->id)
            ->where('category_id', $diploma->id)
            ->exists();

        if (!$isEnrolledInDiploma) {
            return response()->json([
                'status' => 'not_enrolled',
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
                'diploma_id' => $diploma->id,
                'user_id' => $user->id,
            ], 200);
        }

        // Fetch lesson progress
        $progress = UserLessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $status = 'in_progress';
        if ($progress && $progress->status === 'completed') {
            $status = 'completed';
        }

        return response()->json([
            'status' => $status,
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
            'diploma_id' => $diploma->id,
            'user_id' => $user->id,
        ], 200);
    }
}