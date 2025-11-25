<?php

namespace App\Http\Controllers;

use App\Models\CategoryOfCourse;
use App\Models\UserCategoryEnrollment;
use App\Models\UserCourse;
use App\Http\Resources\CategoryResource;
use App\Models\DiplomaCertificate;
use App\Services\DiplomaEligibilityService;
use Illuminate\Support\Facades\Storage;
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

        $userId = $user->id;
        $eligibilityService = new DiplomaEligibilityService();
        $data = $enrollments->map(function ($enrollment) use ($userId, $eligibilityService, $user) {
            $certificate = DiplomaCertificate::where('user_id', $userId)
                ->where('diploma_id', $enrollment->category_id)
                ->orderByDesc('created_at')
                ->first();

            // Normalize file URL to a FULL absolute URL when possible
            $fileUrl = null;
            if ($certificate && !empty($certificate->file_path)) {
                if (Storage::disk('public')->exists($certificate->file_path)) {
                    // Storage::url returns a relative path like /storage/... -> wrap with url() to make it absolute
                    $fileUrl = url(Storage::url($certificate->file_path));
                } elseif (preg_match('/^https?:\/\//i', $certificate->file_path)) {
                    $fileUrl = $certificate->file_path;
                }
            }

            // Compute progress (0-100) using eligibility service
            $progress = 0.0;
            if ($enrollment->category) {
                $progress = $eligibilityService->calculateCompletionPercentage($user, $enrollment->category);
            }

            return [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->created_at,
                'category' => new CategoryResource($enrollment->category),
                // Always include certificate object if exists, with full file_url when resolvable
                'certificate' => $certificate ? [
                    'id' => $certificate->id,
                    'status' => $certificate->status,
                    'serial_number' => $certificate->serial_number,
                    'file_url' => $fileUrl,
                ] : null,
                // Frontend flags based on agreed conditions
                'progress' => $progress,
                'can_download_certificate' => ($progress == 100.0) && ($certificate !== null) && ($certificate->status === 'completed'),
                'is_eligible_for_certificate' => ($progress == 100.0) && ($certificate === null),
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