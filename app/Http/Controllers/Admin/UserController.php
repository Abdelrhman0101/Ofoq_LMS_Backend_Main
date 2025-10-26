<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCategoryEnrollment;
use Illuminate\Http\Request;
use App\Models\UserQuizAttempt;

class UserController extends Controller
{
    /**
     * Get complete student data with diploma enrollments for admin panel
     */
    public function getStudentsWithDiplomas(Request $request)
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
                },
                'categoryEnrollments' => function ($query) {
                    $query->with(['category' => function ($categoryQuery) {
                        $categoryQuery->withCount('courses');
                    }]);
                }
            ])
            ->where('role', '!=', 'admin')
            ->paginate($perPage);

            $studentsData = $students->getCollection()->map(function ($student) {
                // Get course enrollments
                $courses = $student->enrollments->map(function ($enrollment) {
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
                });

                // Get diploma enrollments
                $diplomas = $student->categoryEnrollments->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'category_id' => $enrollment->category_id,
                        'category_name' => $enrollment->category?->name,
                        'category_slug' => $enrollment->category?->slug,
                        'status' => $enrollment->status,
                        'enrolled_at' => $enrollment->created_at,
                        'courses_count' => $enrollment->category?->courses_count ?? 0,
                        'is_free' => $enrollment->category?->is_free ?? true,
                        'price' => $enrollment->category?->price ?? 0,
                    ];
                });

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'nationality' => $student->nationality,
                    'qualification' => $student->qualification,
                    'media_work_sector' => $student->media_work_sector,
                    'date_of_birth' => $student->date_of_birth,
                    'previous_field' => $student->previous_field,
                    'created_at' => $student->created_at,
                    'email_verified_at' => $student->email_verified_at,
                    'is_blocked' => \App\Models\BlockedUser::where('user_id', $student->id)->where('is_blocked', true)->exists(),
                    'courses' => $courses,
                    'diplomas' => $diplomas,
                    'total_courses' => $courses->count(),
                    'total_diplomas' => $diplomas->count(),
                    'completed_courses' => $courses->where('status', 'completed')->count(),
                    'active_diplomas' => $diplomas->where('status', 'active')->count(),
                ];
            });

            // Global stats across all non-admin users
            $totalStudents = User::where('role', '!=', 'admin')->count();
            // Blocked users are tracked in blocked_users table
            $blockedStudents = \App\Models\BlockedUser::where('is_blocked', true)
                ->whereHas('user', function ($q) {
                    $q->where('role', '!=', 'admin');
                })
                ->distinct('user_id')
                ->count('user_id');
            $activeStudents = $totalStudents - $blockedStudents;

            return response()->json([
                'success' => true,
                'data' => $studentsData,
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                ],
                'stats' => [
                    'total_students' => $totalStudents,
                    'active_students' => $activeStudents,
                    'blocked_students' => $blockedStudents,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getStudentsWithDiplomas: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Delete a user (admin only). Cascades will remove related data.
     */
    public function destroy(User $user)
    {
        // Optional: prevent deleting admins
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Deleting admin users is not allowed.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}