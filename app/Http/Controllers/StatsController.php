<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\UserCourse;
use App\Models\Reviews;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    /**
     * Get general website statistics for homepage
     */
    public function getGeneralStats(): JsonResponse
    {
        try {
            // عدد الطلاب المسجلين (غير المحظورين)
            $totalStudents = User::where('role', '!=', 'admin')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('blocked_users')
                        ->whereRaw('blocked_users.user_id = users.id')
                        ->where('blocked_users.is_blocked', true);
                })
                ->count();

            // الطلاب السعداء (أكملوا كورس واحد على الأقل)
            $happyStudents = User::where('role', '!=', 'admin')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('blocked_users')
                        ->whereRaw('blocked_users.user_id = users.id')
                        ->where('blocked_users.is_blocked', true);
                })
                ->whereHas('enrollments', function ($query) {
                    $query->where('status', 'completed');
                })
                ->count();

            // عدد الكورسات المنشورة
            $totalCourses = Course::where('is_published', true)->count();

            // عدد المراجعات الإيجابية (4 نجوم فأكثر)
            $positiveReviews = Reviews::where('rating', '>=', 4)->count();

            // متوسط التقييمات
            $averageRating = Reviews::avg('rating') ?? 0;

            // عدد الطلاب النشطين (لديهم تسجيلات نشطة)
            $activeStudents = User::where('role', '!=', 'admin')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('blocked_users')
                        ->whereRaw('blocked_users.user_id = users.id')
                        ->where('blocked_users.is_blocked', true);
                })
                ->whereHas('enrollments', function ($query) {
                    $query->where('status', 'active');
                })
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_students'   => $totalStudents,
                    'happy_students'   => $happyStudents,
                    'total_courses'    => $totalCourses,
                    'positive_reviews' => $positiveReviews,
                    'average_rating'   => round($averageRating, 1),
                    'active_students'  => $activeStudents,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in getGeneralStats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الإحصائيات',
            ], 500);
        }
    }

    /**
     * Get detailed student statistics (admin only)
     */
    public function getStudentStats(): JsonResponse
    {
        try {
            $totalStudents = User::where('role', '!=', 'admin')->count();

            $activeStudents = User::where('role', '!=', 'admin')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('blocked_users')
                        ->whereRaw('blocked_users.user_id = users.id')
                        ->where('blocked_users.is_blocked', true);
                })
                ->count();

            $blockedStudents = User::where('role', '!=', 'admin')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('blocked_users')
                        ->whereRaw('blocked_users.user_id = users.id')
                        ->where('blocked_users.is_blocked', true);
                })
                ->count();

            // الطلاب الذين أكملوا كورس واحد على الأقل
            $studentsWithCompletedCourses = User::where('role', '!=', 'admin')
                ->whereHas('enrollments', function ($query) {
                    $query->where('status', 'completed');
                })
                ->count();

            // الطلاب الذين لديهم تسجيلات نشطة
            $studentsWithActiveEnrollments = User::where('role', '!=', 'admin')
                ->whereHas('enrollments', function ($query) {
                    $query->where('status', 'active');
                })
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_students'                    => $totalStudents,
                    'active_students'                   => $activeStudents,
                    'blocked_students'                  => $blockedStudents,
                    'students_with_completed_courses'   => $studentsWithCompletedCourses,
                    'students_with_active_enrollments'  => $studentsWithActiveEnrollments,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in getStudentStats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب إحصائيات الطلاب',
            ], 500);
        }
    }
}
