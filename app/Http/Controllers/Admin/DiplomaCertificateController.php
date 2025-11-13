<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryOfCourse;
use App\Models\DiplomaCertificate;
use App\Services\DiplomaEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DiplomaCertificateController extends Controller
{
    protected DiplomaEligibilityService $eligibilityService;

    public function __construct(DiplomaEligibilityService $eligibilityService)
    {
        $this->eligibilityService = $eligibilityService;
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
     * Generate a unique serial number for a certificate
     * 
     * @param CategoryOfCourse $diploma
     * @return string
     */
    protected function generateSerialNumber(CategoryOfCourse $diploma): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $diploma->name), 0, 3));
        $year = date('Y');
        
        do {
            $random = strtoupper(Str::random(6));
            $serialNumber = "{$prefix}-{$year}-{$random}";
        } while (DiplomaCertificate::where('serial_number', $serialNumber)->exists());

        return $serialNumber;
    }
}