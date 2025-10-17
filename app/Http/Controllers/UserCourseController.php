<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\UserCourse;
use App\Http\Resources\CourseResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserCourseController extends Controller
{
    /**
     * Display all courses for students with filtering
     */
    public function myEnrollments(Request $request)
    {
        $user = Auth::user();

        $courses = UserCourse::query()
            ->where('user_id', $user->id)
            ->with(['course' => function ($query) use ($request) {
                $query->search($request->input('search'))
                    ->field($request->input('field'))
                    ->sort($request->input('sort'))
                    ->withCount(['chapters', 'lessons', 'reviews'])
                    ->withAvg('reviews', 'rating');
            }])
            ->paginate(12);

        return CourseResource::collection($courses->pluck('course'))
            ->additional([
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


    public function studentCoursesStatus()
    {
        $students = User::with([
            'enrollments' => function ($query) {
                $query->with([
                    'course',
                    'certificate',
                    'course.finalExam.userAttempts' => function ($attemptQuery) {
                        $attemptQuery->select('id', 'quiz_id', 'user_id', 'score', 'passed');
                    },
                ]);
            }
        ])->whereHas('enrollments')->get();

        $data = $students->map(function ($student) {
            $courses = $student->enrollments
                ->filter(fn($enrollment) => $enrollment->course !== null)
                ->values()
                ->map(function ($enrollment) use ($student) {
                    $course = $enrollment->course;
                    $finalExam = $course->finalExam;

                    $attempts = $finalExam
                        ? $finalExam->userAttempts->where('user_id', $student->id)
                        : collect();

                    // ✅ التعديل هنا: أخذ أعلى درجة بدلاً من آخر محاولة
                    $bestAttempt = $attempts->sortByDesc('score')->first();
                    $finalExamScore = $bestAttempt?->score;
                    $attemptCount = $attempts->count();

                    return [
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'status' => $enrollment->status,
                        'certificate_issued' => (bool) $enrollment->certificate,
                        'issued_at' => $enrollment->certificate?->issued_at,
                        'final_exam_score' => $finalExamScore,
                        'attempts_count' => $attemptCount,
                    ];
                });

            return [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'total_courses' => $student->enrollments->count(),
                'courses' => $courses,
            ];
        });

        return response()->json([
            'message' => 'Students with course statuses fetched successfully.',
            'data' => $data,
        ]);
    }
}
