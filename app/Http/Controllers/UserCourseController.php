<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\UserCourse;
use App\Http\Resources\CourseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserCourseController extends Controller
{
    /**
     * Display all courses for students with filtering
     */
    public function index(Request $request)
    {
        $query = Course::with(['chapters.lessons', 'chapters.quiz.questions']);

        // Filter by course type (free/paid)
        if ($request->has('type')) {
            if ($request->type === 'free') {
                $query->where('price', 0);
            } elseif ($request->type === 'paid') {
                $query->where('price', '>', 0);
            }
        }

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->get();

        return response()->json([
            'courses' => CourseResource::collection($courses)
        ]);
    }

    /**
     * Display course details with chapters and lessons
     */
    public function show($id)
    {
        $course = Course::with([
            'chapters.lessons',
            'chapters.quiz.questions'
        ])->find($id);

        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }

        // Check if user is enrolled
        $isEnrolled = false;
        $progress = null;
        
        if (Auth::check()) {
            $userCourse = UserCourse::where('user_id', Auth::id())
                                  ->where('course_id', $id)
                                  ->first();
            
            if ($userCourse) {
                $isEnrolled = true;
                $progress = [
                    'status' => $userCourse->status,
                    'progress_percentage' => $userCourse->progress_percentage,
                    'completed_at' => $userCourse->completed_at,
                    'enrolled_at' => $userCourse->created_at
                ];
            }
        }

        return response()->json([
            'course' => new CourseResource($course),
            'is_enrolled' => $isEnrolled,
            'progress' => $progress
        ]);
    }

    /**
     * Enroll user in a course
     */
    public function enroll(Request $request, $id)
    {
        $course = Course::find($id);
        
        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }

        $user = Auth::user();
        
        // Check if already enrolled
        $existingEnrollment = UserCourse::where('user_id', $user->id)
                                       ->where('course_id', $id)
                                       ->first();
        
        if ($existingEnrollment) {
            return response()->json([
                'message' => 'You are already enrolled in this course'
            ], 400);
        }

        // For paid courses, you would implement payment logic here
        if ($course->price > 0) {
            // TODO: Implement payment verification
            // For now, we'll allow enrollment for demonstration
        }

        // Create enrollment
        $enrollment = UserCourse::create([
            'user_id' => $user->id,
            'course_id' => $id,
            'status' => 'in_progress',
            'progress_percentage' => 0
        ]);

        return response()->json([
            'message' => 'Successfully enrolled in course',
            'enrollment' => [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'status' => $enrollment->status,
                'progress_percentage' => $enrollment->progress_percentage,
                'enrolled_at' => $enrollment->created_at
            ]
        ], 201);
    }

    /**
     * Get user's enrolled courses
     */
    public function myEnrollments()
    {
        $user = Auth::user();
        
        $enrollments = UserCourse::with('course.chapters.lessons')
                                ->where('user_id', $user->id)
                                ->get();

        return response()->json([
            'enrollments' => $enrollments->map(function($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'course' => new CourseResource($enrollment->course),
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'completed_at' => $enrollment->completed_at,
                    'enrolled_at' => $enrollment->created_at
                ];
            })
        ]);
    }
}