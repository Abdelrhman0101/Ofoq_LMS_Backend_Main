<?php

namespace App\Http\Controllers;

use App\Models\CategoryOfCourse;
use App\Models\UserCategoryEnrollment;
use App\Models\UserCourse;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserCategoryEnrollmentController extends Controller
{
    /**
     * Enroll the authenticated user in a diploma (category) and attach its courses.
     */
    public function enroll(Request $request, $category)
    {
        $user = Auth::user();

        $diploma = CategoryOfCourse::query()
            ->where('is_published', true)
            ->where(function ($q) use ($category) {
                $q->where('slug', $category);
                if (is_numeric($category)) {
                    $q->orWhere('id', (int) $category);
                }
            })
            ->first();

        if (!$diploma) {
            return response()->json([
                'success' => false,
                'message' => 'الدبلوم غير موجود أو غير منشور.'
            ], 404);
        }

        $existing = UserCategoryEnrollment::where('user_id', $user->id)
            ->where('category_id', $diploma->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'أنت مسجل مسبقًا في هذا الدبلوم.'
            ], 400);
        }

        $isPaid = (!$diploma->is_free) && ((float) $diploma->price > 0);
        $status = $isPaid ? 'pending_payment' : 'active';

        $enrollment = UserCategoryEnrollment::create([
            'user_id' => $user->id,
            'category_id' => $diploma->id,
            'status' => $status,
        ]);

        $attachedCount = 0;
        if (!$isPaid) {
            // Attach all published courses under this diploma to the user
            $courses = $diploma->courses()->get();
            foreach ($courses as $course) {
                $created = UserCourse::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'status' => 'in_progress',
                        'progress_percentage' => 0,
                    ]
                );

                if ($created->wasRecentlyCreated) {
                    $attachedCount++;
                    $course->students_count = ($course->students_count ?? 0) + 1;
                    $course->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $isPaid
                ? 'تم إنشاء تسجيل للدبلوم بحالة انتظار الدفع. سيتم تفعيل الوصول بعد الدفع.'
                : 'تم التسجيل في الدبلوم وربط الدورات بنجاح.',
            'enrollment' => [
                'id' => $enrollment->id,
                'category_id' => $enrollment->category_id,
                'status' => $enrollment->status,
            ],
            'enrolled_courses_count' => $attachedCount,
        ], 201);
    }

    /**
     * List the authenticated user's diploma enrollments (with status and category details).
     */
    public function myDiplomas(Request $request)
    {
        $user = Auth::user();

        $enrollments = UserCategoryEnrollment::query()
            ->where('user_id', $user->id)
            ->with(['category' => function ($q) {
                $q->withCount('courses');
            }])
            ->get();

        $data = $enrollments->map(function ($enrollment) {
            return [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->created_at,
                'category' => new CategoryResource($enrollment->category),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب دبلوماتي بنجاح.',
            'data' => $data,
        ]);
    }

    /**
     * Activate a pending paid diploma enrollment and attach its courses.
     */
    public function activate(Request $request, $category)
    {
        $user = Auth::user();

        $diploma = CategoryOfCourse::query()
            ->where('is_published', true)
            ->where(function ($q) use ($category) {
                $q->where('slug', $category);
                if (is_numeric($category)) {
                    $q->orWhere('id', (int) $category);
                }
            })
            ->first();

        if (! $diploma) {
            return response()->json([
                'success' => false,
                'message' => 'الدبلوم غير موجود أو غير منشور.'
            ], 404);
        }

        $enrollment = UserCategoryEnrollment::query()
            ->where('user_id', $user->id)
            ->where('category_id', $diploma->id)
            ->first();

        if (! $enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد تسجيل لهذا الدبلوم.'
            ], 404);
        }

        if ($enrollment->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'التسجيل مفعل بالفعل.'
            ], 400);
        }

        if ($enrollment->status !== 'pending_payment') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التفعيل لهذه الحالة.'
            ], 400);
        }

        $enrollment->status = 'active';
        $enrollment->save();

        $attachedCount = 0;
        $courses = $diploma->courses()->get();
        foreach ($courses as $course) {
            $created = UserCourse::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ],
                [
                    'status' => 'in_progress',
                    'progress_percentage' => 0,
                ]
            );

            if ($created->wasRecentlyCreated) {
                $attachedCount++;
                $course->students_count = ($course->students_count ?? 0) + 1;
                $course->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل الدبلوم وربط الكورسات بنجاح.',
            'enrollment' => [
                'id' => $enrollment->id,
                'category_id' => $enrollment->category_id,
                'status' => $enrollment->status,
            ],
            'enrolled_courses_count' => $attachedCount,
        ]);
    }
}