<?php

namespace App\Http\Controllers;

use App\Models\Reviews;
use App\Models\UserCourse;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    // List reviews for a course (optional, keep for future)
    public function index(Request $request, $courseId)
    {
        $perPage = (int)($request->get('per_page', 15));
        $reviews = Reviews::query()
            ->where('course_id', $courseId)
            ->with('user')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    // Create review (only if user enrolled in course)
    public function store(Request $request, $courseId)
    {
        $user = Auth::user();

        // Ensure course exists
        $course = Course::findOrFail($courseId);

        // Check enrollment
        $enrolled = UserCourse::query()
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$enrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to submit a review.',
            ], 403);
        }

        // Validate input
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string'],
            // Unique review per user per course
            // enforce unique (course_id, user_id) combo
        ]);

        // enforce uniqueness
        $alreadyReviewed = Reviews::where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a review for this course.',
            ], 409);
        }

        $review = Reviews::create([
            'course_id' => $courseId,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'data' => $review,
        ], 201);
    }


    public function update(Request $request, $courseId, $reviewId)
    {
        $user = Auth::user();

        $course = Course::findOrFail($courseId);

        $review = Reviews::where('id', $reviewId)
            ->where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or you do not have permission to edit it.',
            ], 404);
        }

        $validated = $request->validate([
            'comment' => ['sometimes', 'string'],
        ]);

        $review->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => $review,
        ]);
    }



    public function destroy($courseId, $reviewId)
    {
        $user = Auth::user();

        $course = Course::findOrFail($courseId);

        // جيب الريفيو بتاع اليوزر
        $review = Reviews::where('id', $reviewId)
            ->where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or you do not have permission to delete it.',
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }
}
