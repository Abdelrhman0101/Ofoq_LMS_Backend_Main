<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryOfCourse;
use App\Models\DiplomaCertificate;
use App\Services\DiplomaEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\UserCategoryEnrollment;
use App\Models\UserCourse;

class DiplomaCertificateController extends Controller
{
    protected DiplomaEligibilityService $eligibilityService;

    public function __construct(DiplomaEligibilityService $eligibilityService)
    {
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Search for a student by id or email and list enrolled diplomas
     * with completion percentage, certificate status, and action logic.
     */
    public function searchStudentDiplomas(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
            'email' => 'sometimes|email'
        ]);

        // Resolve user by id or email
        $user = null;
        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        // Get all diploma enrollments
        $enrollments = UserCategoryEnrollment::where('user_id', $user->id)
            ->with('category')
            ->get();

        $diplomas = $enrollments->map(function ($enrollment) use ($user) {
            /** @var \App\Models\CategoryOfCourse $diploma */
            $diploma = $enrollment->category;
            $totalCourses = $diploma->courses()->count();

            // Compute completion percentage
            $completedCourses = UserCourse::where('user_id', $user->id)
                ->whereHas('course', function ($q) use ($diploma) {
                    $q->where('category_id', $diploma->id);
                })
                ->where('status', 'completed')
                ->where('final_exam_score', '>=', 60)
                ->count();
            $percentage = $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100, 2) : 0.0;

            // Check existing certificate
            $certificate = DiplomaCertificate::where('user_id', $user->id)
                ->where('diploma_id', $diploma->id)
                ->orderByDesc('created_at')
                ->first();

            $fileUrl = null;
            if ($certificate && !empty($certificate->file_path) && Storage::disk('public')->exists($certificate->file_path)) {
                $fileUrl = Storage::url($certificate->file_path);
            } elseif ($certificate && !empty($certificate->file_path) && preg_match('/^https?:\/\//i', $certificate->file_path)) {
                $fileUrl = $certificate->file_path;
            }

            // Determine action
            $action = [
                'type' => 'locked',
                'label' => 'غير متاح',
            ];

            if ($percentage < 100) {
                $action = ['type' => 'locked', 'label' => 'غير متاح'];
            } elseif ($percentage == 100 && !$certificate) {
                $action = ['type' => 'generate', 'label' => 'توليد الشهادة'];
            } elseif ($certificate) {
                if ($certificate->status === 'completed' && $fileUrl) {
                    $action = ['type' => 'download', 'label' => 'تحميل', 'url' => $fileUrl];
                } else {
                    $action = ['type' => 'processing', 'label' => 'قيد المعالجة'];
                }
            }

            return [
                'diploma' => [
                    'id' => $diploma->id,
                    'name' => $diploma->name,
                    'total_courses' => $totalCourses,
                ],
                'completion_percentage' => $percentage,
                'certificate' => $certificate ? [
                    'id' => $certificate->id,
                    'status' => $certificate->status,
                    'serial_number' => $certificate->serial_number,
                    'file_path' => $certificate->file_path,
                    'file_url' => $fileUrl,
                ] : null,
                'action' => $action,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'diplomas' => $diplomas,
            ],
        ]);
    }

    /**
     * Manually generate a diploma certificate for a student (admin trigger)
     * Validates 100% completion, creates record, dispatches job.
     */
    public function generateForStudent(Request $request, CategoryOfCourse $diploma): JsonResponse
    {
        $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
            'email' => 'sometimes|email'
        ]);

        // Resolve user by id or email
        $user = null;
        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        // Verify enrollment exists
        $enrollment = UserCategoryEnrollment::where('user_id', $user->id)
            ->where('category_id', $diploma->id)
            ->first();
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير مسجل في هذه الدبلومة'
            ], 403);
        }

        // Backend validation: 100% completion
        $percentage = $this->eligibilityService->calculateCompletionPercentage($user, $diploma);
        if ($percentage < 100.0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التوليد: نسبة الإنجاز أقل من 100%'
            ], 403);
        }

        // Check existing certificate
        $existing = DiplomaCertificate::where('user_id', $user->id)
            ->where('diploma_id', $diploma->id)
            ->where('status', '!=', 'revoked')
            ->first();
        if ($existing) {
            $fileUrl = null;
            if (!empty($existing->file_path) && Storage::disk('public')->exists($existing->file_path)) {
                $fileUrl = Storage::url($existing->file_path);
            } elseif (!empty($existing->file_path) && preg_match('/^https?:\/\//i', $existing->file_path)) {
                $fileUrl = $existing->file_path;
            }

            return response()->json([
                'success' => true,
                'message' => $existing->status === 'completed' ? 'تم إصدار الشهادة مسبقًا' : 'الشهادة قيد المعالجة',
                'certificate' => [
                    'id' => $existing->id,
                    'status' => $existing->status,
                    'serial_number' => $existing->serial_number,
                    'file_url' => $fileUrl,
                ],
            ]);
        }

        // Generate unique serial number
        $serialNumber = $this->generateSerialNumber($diploma);

        // Create certificate record (pending)
        $certificate = DiplomaCertificate::create([
            'user_id' => $user->id,
            'diploma_id' => $diploma->id,
            'user_category_enrollment_id' => $enrollment->id,
            'serial_number' => $serialNumber,
            'file_path' => "certificates/diplomas/{$serialNumber}.pdf",
            'status' => 'pending',
            'verification_token' => Str::uuid()->toString(),
            'student_name' => $user->name,
            'issued_at' => now(),
            'certificate_data' => [
                'diploma_name' => $diploma->name,
                'student_name' => $user->name,
                'issued_date' => now()->toDateString(),
                'total_courses_completed' => $diploma->courses()->count(),
            ]
        ]);

        // Dispatch job to generate PDF
        \App\Jobs\GenerateDiplomaCertificateJob::dispatch($certificate);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء سجل الشهادة وإرسال مهمة توليد الـ PDF',
            'certificate' => [
                'id' => $certificate->id,
                'serial_number' => $certificate->serial_number,
                'status' => $certificate->status,
                'verification_token' => $certificate->verification_token,
            ],
        ]);
    }

    /**
     * Get eligible students for a specific diploma
     * 
     * @param Request $request
     * @param CategoryOfCourse $diploma
     * @return JsonResponse
     */
    public function getEligibleStudents(Request $request, CategoryOfCourse $diploma): JsonResponse
    {
        try {
            $eligibleStudents = $this->eligibilityService->getEligibleStudents($diploma);

            return response()->json([
                'success' => true,
                'data' => [
                    'diploma' => [
                        'id' => $diploma->id,
                        'name' => $diploma->name,
                        'total_courses' => $diploma->courses()->count(),
                    ],
                    'eligible_students' => $eligibleStudents->map(function ($student) {
                        return [
                            'id' => $student->id,
                            'name' => $student->name,
                            'email' => $student->email,
                            'completed_courses' => $student->completed_diploma_courses,
                        ];
                    }),
                    'total_eligible' => $eligibleStudents->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving eligible students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check eligibility for a specific student and diploma
     * 
     * @param Request $request
     * @param CategoryOfCourse $diploma
     * @return JsonResponse
     */
    public function checkStudentEligibility(Request $request, CategoryOfCourse $diploma): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $eligibilityDetails = $this->eligibilityService->getStudentEligibilityDetails($user, $diploma);

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'diploma' => [
                        'id' => $diploma->id,
                        'name' => $diploma->name,
                    ],
                    'eligibility' => $eligibilityDetails,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking eligibility',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Issue certificates to eligible students
     * 
     * @param Request $request
     * @param CategoryOfCourse $diploma
     * @return JsonResponse
     */
    public function issueCertificates(Request $request, CategoryOfCourse $diploma): JsonResponse
    {
        try {
            $eligibleStudents = $this->eligibilityService->getEligibleStudents($diploma);
            $issuedCertificates = [];
            $errors = [];

            foreach ($eligibleStudents as $student) {
                try {
                    // Check if certificate already exists (double-check)
                    $existingCertificate = DiplomaCertificate::where('user_id', $student->id)
                        ->where('diploma_id', $diploma->id)
                        ->where('status', '!=', 'revoked')
                        ->first();

                    if ($existingCertificate) {
                        $errors[] = "Student {$student->name} already has certificate {$existingCertificate->serial_number}";
                        continue;
                    }

                    // Get enrollment for this student and diploma
                    $enrollment = \App\Models\UserCategoryEnrollment::where('user_id', $student->id)
                        ->where('category_id', $diploma->id)
                        ->first();

                    if (!$enrollment) {
                        $errors[] = "Student {$student->name} is not enrolled in this diploma";
                        continue;
                    }

                    // Generate unique serial number
                    $serialNumber = $this->generateSerialNumber($diploma);

                    // Create certificate
                    $certificate = DiplomaCertificate::create([
                        'user_id' => $student->id,
                        'diploma_id' => $diploma->id,
                        'user_category_enrollment_id' => $enrollment->id,
                        'serial_number' => $serialNumber,
                        'file_path' => "certificates/diplomas/{$serialNumber}.pdf",
                        'status' => 'pending', // Will be updated to 'issued' when PDF is generated
                        'verification_token' => Str::uuid()->toString(),
                        'student_name' => $student->name,
                        'issued_at' => now(),
                        'certificate_data' => [
                            'diploma_name' => $diploma->name,
                            'student_name' => $student->name,
                            'issued_date' => now()->toDateString(),
                            'total_courses_completed' => $student->completed_diploma_courses,
                        ]
                    ]);

                    // Queue certificate PDF generation
                    \App\Jobs\GenerateDiplomaCertificateJob::dispatch($certificate);

                    $issuedCertificates[] = [
                        'certificate_id' => $certificate->id,
                        'serial_number' => $certificate->serial_number,
                        'student_name' => $student->name,
                        'student_email' => $student->email,
                    ];

                } catch (\Exception $e) {
                    $errors[] = "Error issuing certificate for student {$student->name}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'diploma' => [
                        'id' => $diploma->id,
                        'name' => $diploma->name,
                    ],
                    'issued_certificates' => $issuedCertificates,
                    'total_issued' => count($issuedCertificates),
                    'errors' => $errors,
                    'total_errors' => count($errors),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error issuing certificates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all certificates for a diploma
     * 
     * @param Request $request
     * @param CategoryOfCourse $diploma
     * @return JsonResponse
     */
    public function getDiplomaCertificates(Request $request, CategoryOfCourse $diploma): JsonResponse
    {
        try {
            $certificates = DiplomaCertificate::with(['user'])
                ->where('diploma_id', $diploma->id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => [
                    'diploma' => [
                        'id' => $diploma->id,
                        'name' => $diploma->name,
                    ],
                    'certificates' => $certificates->items(),
                    'pagination' => [
                        'total' => $certificates->total(),
                        'per_page' => $certificates->perPage(),
                        'current_page' => $certificates->currentPage(),
                        'last_page' => $certificates->lastPage(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving certificates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List enrolled students in a diploma with progress and certificate status
     *
     * GET /api/admin/diplomas/{diploma}/students
     */
    public function listEnrolledStudents(Request $request, CategoryOfCourse $diploma): JsonResponse
    {
        try {
            $query = UserCategoryEnrollment::where('category_id', $diploma->id)
                ->where('status', 'active');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $enrollments = $query->with('user')->get();

            $service = $this->eligibilityService;

            $students = $enrollments->map(function ($enrollment) use ($diploma, $service) {
                /** @var \App\Models\User $user */
                $user = $enrollment->user;
                $progress = $service->calculateCompletionPercentage($user, $diploma);

                $certificate = DiplomaCertificate::where('user_id', $user->id)
                    ->where('diploma_id', $diploma->id)
                    ->where('status', '!=', 'revoked')
                    ->orderByDesc('created_at')
                    ->first();

                // Map backend certificate status to frontend admin states
                // generated: status == completed
                // processing: status == pending OR processing
                // failed: status == failed
                // not_generated: no certificate OR revoked
                $certificateStatus = 'not_generated';
                if ($certificate) {
                    if ($certificate->status === 'completed') {
                        $certificateStatus = 'generated';
                    } elseif (in_array($certificate->status, ['pending', 'processing'])) {
                        $certificateStatus = 'processing';
                    } elseif ($certificate->status === 'failed') {
                        $certificateStatus = 'failed';
                    } else {
                        $certificateStatus = 'not_generated';
                    }
                }

                $fileUrl = null;
                if ($certificate && !empty($certificate->file_path) && Storage::disk('public')->exists($certificate->file_path)) {
                    $fileUrl = Storage::url($certificate->file_path);
                } elseif ($certificate && !empty($certificate->file_path) && preg_match('/^https?:\/\//i', $certificate->file_path)) {
                    $fileUrl = $certificate->file_path;
                }

                return [
                    'student_id' => $user->id,
                    'student_name' => $user->name,
                    'email' => $user->email,
                    'progress' => $progress,
                    'certificate_status' => $certificateStatus,
                    'is_eligible' => $progress == 100.0,
                    'file_url' => $fileUrl,
                ];
            });

            if ($request->filled('filter')) {
                $filter = $request->filter;
                $students = $students->filter(function ($student) use ($filter) {
                    if ($filter === 'ready') {
                        return $student['is_eligible'] && $student['certificate_status'] === 'not_generated';
                    }
                    if ($filter === 'generated') {
                        return $student['certificate_status'] === 'generated';
                    }
                    if ($filter === 'not_ready') {
                        return !$student['is_eligible'];
                    }
                    if ($filter === 'failed') {
                        return $student['certificate_status'] === 'failed';
                    }
                    return true;
                })->values();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'diploma' => [
                        'id' => $diploma->id,
                        'name' => $diploma->name,
                        'total_courses' => $diploma->courses()->count(),
                    ],
                    'students' => $students,
                    'total' => $students->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving enrolled students',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate certificate for a specific student by path param
     *
     * POST /api/admin/diplomas/{diploma}/students/{student}/generate-certificate
     */
    public function generateForStudentById(Request $request, CategoryOfCourse $diploma, User $student): JsonResponse
    {
        try {
            // Verify enrollment exists
            $enrollment = UserCategoryEnrollment::where('user_id', $student->id)
                ->where('category_id', $diploma->id)
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير مسجل في هذه الدبلومة'
                ], 403);
            }

            // Backend validation: 100% completion
            $percentage = $this->eligibilityService->calculateCompletionPercentage($student, $diploma);
            if ($percentage < 100.0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن التوليد: نسبة الإنجاز أقل من 100%'
                ], 403);
            }

            // Check existing certificate
            $existing = DiplomaCertificate::where('user_id', $student->id)
                ->where('diploma_id', $diploma->id)
                ->where('status', '!=', 'revoked')
                ->first();
            if ($existing) {
                $fileUrl = null;
                if (!empty($existing->file_path) && Storage::disk('public')->exists($existing->file_path)) {
                    $fileUrl = Storage::url($existing->file_path);
                } elseif (!empty($existing->file_path) && preg_match('/^https?:\/\//i', $existing->file_path)) {
                    $fileUrl = $existing->file_path;
                }

                return response()->json([
                    'success' => true,
                    'message' => $existing->status === 'completed' ? 'تم إصدار الشهادة مسبقًا' : 'الشهادة قيد المعالجة',
                    'certificate' => [
                        'id' => $existing->id,
                        'status' => $existing->status,
                        'serial_number' => $existing->serial_number,
                        'file_url' => $fileUrl,
                    ],
                ]);
            }

            // Generate unique serial number
            $serialNumber = $this->generateSerialNumber($diploma);

            // Create certificate record (pending)
            $certificate = DiplomaCertificate::create([
                'user_id' => $student->id,
                'diploma_id' => $diploma->id,
                'user_category_enrollment_id' => $enrollment->id,
                'serial_number' => $serialNumber,
                'file_path' => "certificates/diplomas/{$serialNumber}.pdf",
                'status' => 'pending',
                'verification_token' => Str::uuid()->toString(),
                'student_name' => $student->name,
                'issued_at' => now(),
                'certificate_data' => [
                    'diploma_name' => $diploma->name,
                    'student_name' => $student->name,
                    'issued_date' => now()->toDateString(),
                    'total_courses_completed' => $diploma->courses()->count(),
                ]
            ]);

            // Dispatch job to generate PDF
            \App\Jobs\GenerateDiplomaCertificateJob::dispatch($certificate);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء سجل الشهادة وإرسال مهمة توليد الـ PDF',
                'certificate' => [
                    'id' => $certificate->id,
                    'serial_number' => $certificate->serial_number,
                    'status' => $certificate->status,
                    'verification_token' => $certificate->verification_token,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating certificate for student',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a unique serial number for a certificate
     * 
     * @param CategoryOfCourse $diploma
     * @return string
     */
    protected function generateSerialNumber(CategoryOfCourse $diploma): string
    {
        // Generate 7-digit numeric serial
        do {
            $random = mt_rand(0, 9999999);
            $serialNumber = str_pad($random, 7, '0', STR_PAD_LEFT);
        } while (
            \App\Models\Certificate::where('serial_number', $serialNumber)->exists() ||
            \App\Models\DiplomaCertificate::where('serial_number', $serialNumber)->exists()
        );

        return $serialNumber;
    }
}