<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\UserCourse;
use App\Http\Resources\CourseResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\BlockedUser;
use Illuminate\Support\Facades\Schema;
use App\Models\UserQuizAttempt;

class UserCourseController extends Controller
{
    /**
     * Display all courses for students with filtering
     */
    public function myEnrollments(Request $request)
    {
        $user = Auth::user();
        // Allow client to specify per_page up to a safe maximum (100)
        $perPage = (int) $request->query('per_page', 12);
        if ($perPage < 1) { $perPage = 12; }
        if ($perPage > 100) { $perPage = 100; }

        $enrollments = UserCourse::query()
            ->where('user_id', $user->id)
            ->with(['course' => function ($query) use ($request) {
                $query->search($request->input('search'))
                    ->field($request->input('field'))
                    ->sort($request->input('sort'))
                    ->withCount(['chapters', 'lessons', 'reviews'])
                    ->withAvg('reviews', 'rating');
            }])
            ->orderBy('created_at', 'desc') // Ensure consistent ordering
            ->paginate($perPage);

        // Extract courses from enrollments
        $courses = $enrollments->pluck('course');

        return response()->json([
            'data' => CourseResource::collection($courses),
            'pagination' => [
                'total' => $enrollments->total(),
                'per_page' => $enrollments->perPage(),
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'from' => $enrollments->firstItem(),
                'to' => $enrollments->lastItem(),
            ],
            'message' => 'Courses fetched successfully.'
        ]);
    }


    /**
     * Display course details with chapters and lessons
     */
    // public function show($id)
    // {
    //     $course = Course::with([
    //         'chapters.lessons',
    //         'chapters.quiz.questions'
    //     ])->find($id);

    //     if (!$course) {
    //         return response()->json([
    //             'message' => 'Course not found'
    //         ], 404);
    //     }

    //     // Check if user is enrolled
    //     $isEnrolled = false;
    //     $progress = null;

    //     if (Auth::check()) {
    //         $userCourse = UserCourse::where('user_id', Auth::id())
    //             ->where('course_id', $id)
    //             ->first();

    //         if ($userCourse) {
    //             $isEnrolled = true;
    //             $progress = [
    //                 'status' => $userCourse->status,
    //                 'progress_percentage' => $userCourse->progress_percentage,
    //                 'completed_at' => $userCourse->completed_at,
    //                 'enrolled_at' => $userCourse->created_at
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'course' => new CourseResource($course),
    //         'is_enrolled' => $isEnrolled,
    //         'progress' => $progress
    //     ]);
    // }

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
        $course->students_count = $course->students_count + 1;
        $course->save();
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
    // public function myEnrollments()
    // {
    //     $user = Auth::user();

    //     $enrollments = UserCourse::with('course.chapters.lessons')
    //         ->where('user_id', $user->id)
    //         ->get();

    //     return response()->json([
    //         'enrollments' => $enrollments->map(function ($enrollment) {
    //             return [
    //                 'id' => $enrollment->id,
    //                 'course' => new CourseResource($enrollment->course),
    //                 'status' => $enrollment->status,
    //                 'progress_percentage' => $enrollment->progress_percentage,
    //                 'completed_at' => $enrollment->completed_at,
    //                 'enrolled_at' => $enrollment->created_at
    //             ];
    //         })
    //     ]);
    // }


    public function studentCoursesStatus(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            
            $students = User::with([
                'enrollments' => function ($query) {
                    $query->with([
                        'course' => function ($courseQuery) {
                            $courseQuery->with(['instructor', 'category', 'finalExam']);
                        },
                        'certificate'
                    ]);
                }
            ])
            ->whereHas('enrollments')
            ->where('is_blocked', false)
            ->paginate($perPage);

            $studentsData = $students->getCollection()->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'courses' => $student->enrollments->map(function ($enrollment) {
                        $course = $enrollment->course;
                        $finalExamScore = null;
                        
                        if ($course->finalExam) {
                            $quizAttempt = UserQuizAttempt::where('user_id', $enrollment->user_id)
                                ->where('quiz_id', $course->finalExam->id)
                                ->orderBy('created_at', 'desc')
                                ->first();
                            
                            if ($quizAttempt) {
                                $finalExamScore = $quizAttempt->score;
                            }
                        }
                        
                        return [
                            'id' => $course->id,
                            'title' => $course->title,
                            'instructor' => $course->instructor?->name,
                            'category' => $course->category?->name,
                            'cover_image_url' => $course->cover_image_url,
                            'status' => $enrollment->status,
                            'progress_percentage' => $enrollment->progress_percentage,
                            'completed_at' => $enrollment->completed_at,
                            'final_exam_score' => $finalExamScore,
                            'certificate_id' => $enrollment->certificate?->id,
                        ];
                    })
                ];
            });

            return response()->json([
                'data' => $studentsData,
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in studentCoursesStatus: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
