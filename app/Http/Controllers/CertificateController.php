<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\UserCourse;
use App\Models\Certificate;
use App\Models\CourseCertificate;
use App\Models\DiplomaCertificate;
use App\Models\CategoryOfCourse;
use App\Models\UserCategoryEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    /**
     * Generate a unique serial number for certificates.
     */
    private function generateSerialNumber(): string
    {
        // Prefix with OFQ and current year for readability; ensure uniqueness in DB
        $prefix = 'OFQ-' . now()->format('Y');
        do {
            $random = strtoupper(Str::random(6));
            $serial = $prefix . '-' . $random;
        } while (
            \App\Models\Certificate::where('serial_number', $serial)->exists() ||
            \App\Models\DiplomaCertificate::where('serial_number', $serial)->exists()
        );
        return $serial;
    }

    /**
     * Generate QR code for certificate verification
     */
    private function generateQRCode(string $url): string
    {
        // Simple QR code generation using Google Charts API
        // You can replace this with a more sophisticated QR code library if needed
        $qrUrl = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($url) . "&choe=UTF-8";

        // Download and encode the QR code image
        $qrImage = file_get_contents($qrUrl);
        return 'data:image/png;base64,' . base64_encode($qrImage);
    }

    /**
     * Get digital seal image
     */
    private function getDigitalSeal(): string
    {
        // Path to digital seal image - you can replace this with your actual seal
        $sealPath = public_path('storage/digital_seal.png');

        if (file_exists($sealPath)) {
            $sealImage = file_get_contents($sealPath);
            return 'data:image/png;base64,' . base64_encode($sealImage);
        }

        // Return empty string if seal doesn't exist
        return '';
    }
    /**
     * Normalize a stored file_path to a public, direct URL or return null.
     * - External URLs (http/https) are returned as-is.
     * - Local storage paths are converted via Storage::url if the file exists.
     * - Missing or non-existing paths return null.
     */
    private function filePathToUrl(?string $filePath): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        // External URL
        if (preg_match('/^https?:\/\//i', $filePath)) {
            return $filePath;
        }

        // Stored locally on public disk
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
            return \Illuminate\Support\Facades\Storage::url($filePath);
        }

        return null;
    }

    /**
     * Return certificate data for a completed course (no PDF generation)
     */
    public function getCourseCertificateData($courseId)
    {
        $user = Auth::user();

        $userCourse = UserCourse::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->with('course')
            ->first();

        if (!$userCourse) {
            return response()->json(['message' => 'غير مسجل في هذا المقرر'], 404);
        }

        if ($userCourse->status !== 'completed') {
            return response()->json(['message' => 'المقرر غير مكتمل بعد'], 403);
        }

        $certificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$certificate) {
            // Create record only, no PDF
            $verificationToken = Str::uuid();
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'course_id' => $userCourse->course_id,
                'user_course_id' => $userCourse->id,
                'verification_token' => $verificationToken,
                'issued_at' => now(),
                'certificate_data' => json_encode([
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'course_title' => $userCourse->course->title,
                    'completion_date' => $userCourse->completed_at,
                    'enrollment_date' => $userCourse->created_at,
                    'progress_percentage' => $userCourse->progress_percentage,
                ]),
            ]);
        }

        $data = is_array($certificate->certificate_data)
            ? $certificate->certificate_data
            : (json_decode($certificate->certificate_data ?? '[]', true) ?? []);
        return response()->json([
            'certificate' => [
                'id' => $certificate->id,
                'verification_token' => $certificate->verification_token,
                'verification_url' => url("/api/certificate/verify/{$certificate->verification_token}"),
                'issued_at' => optional($certificate->issued_at)->toIso8601String(),
                'completion_date' => optional($userCourse->completed_at)->toIso8601String(),
                'file_path' => $this->filePathToUrl($certificate->file_path),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'course' => [
                    'id' => $userCourse->course->id,
                    'title' => $userCourse->course->title,
                ],
                'data' => $data,
            ],
        ]);
    }

    /**
     * Return diploma certificate data for a completed diploma (no PDF generation)
     */
    public function getDiplomaCertificateData(CategoryOfCourse $category)
    {
        $user = Auth::user();

        $enrollment = UserCategoryEnrollment::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'غير مسجل في هذه الدبلومة'], 404);
        }

        if ($enrollment->status !== 'completed') {
            return response()->json(['message' => 'الدبلومة غير مكتملة بعد'], 403);
        }

        $certificate = DiplomaCertificate::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->first();

        if (!$certificate) {
            $verificationToken = Str::uuid();
            $certificate = DiplomaCertificate::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'user_category_enrollment_id' => $enrollment->id,
                'verification_token' => $verificationToken,
                'issued_at' => now(),
                'certificate_data' => json_encode([
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'diploma_name' => $category->name,
                    'completion_date' => $enrollment->completed_at ?? now(),
                ]),
            ]);
        }

        $data = is_array($certificate->certificate_data)
            ? $certificate->certificate_data
            : (json_decode($certificate->certificate_data ?? '[]', true) ?? []);
        return response()->json([
            'certificate' => [
                'id' => $certificate->id,
                'verification_token' => $certificate->verification_token,
                'verification_url' => url("/api/diploma-certificate/verify/{$certificate->verification_token}"),
                'issued_at' => optional($certificate->issued_at)->toIso8601String(),
                'completion_date' => optional($enrollment->completed_at)->toIso8601String(),
                'file_path' => $this->filePathToUrl($certificate->file_path),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'diploma' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'data' => $data,
            ],
        ]);
    }
    /**
     * Generate certificate data for completed course (no PDF generation)
     */
    public function generateCertificate($courseId)
    {
        $user = Auth::user();

        // Check if user completed the course
        $userCourse = UserCourse::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->where('status', 'completed')
            ->with('course')
            ->first();

        if (!$userCourse) {
            return response()->json([
                'message' => 'Course not completed or not enrolled'
            ], 403);
        }

        // Check if final exam score meets passing criteria
        $course = $userCourse->course;
        $passingScore = optional($course->finalExam)->passing_score ?? 50; // Default passing score is 50%

        if (!$userCourse->final_exam_score || $userCourse->final_exam_score < $passingScore) {
            return response()->json([
                'message' => 'لم يتم اجتياز الاختبار النهائي بنجاح',
                'required_score' => $passingScore,
                'current_score' => $userCourse->final_exam_score,
                'details' => 'يجب الحصول على درجة ' . $passingScore . '% أو أكثر في الاختبار النهائي للحصول على الشهادة'
            ], 403);
        }

        // Check if certificate already exists
        $existingCertificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if ($existingCertificate) {
            return response()->json([
                'message' => 'Certificate data already prepared',
                'certificate_id' => $existingCertificate->id,
                'verification_token' => $existingCertificate->verification_token,
                'verification_url' => url("/api/certificate/verify/{$existingCertificate->verification_token}"),
                'data' => json_decode($existingCertificate->certificate_data, true),
            ]);
        }

        // Create certificate record only (no PDF generation)
        $certificate = $this->createCertificateData($user, $userCourse->course, $userCourse);

        return response()->json([
            'message' => 'Certificate data prepared successfully',
            'certificate_id' => $certificate->id,
            'verification_token' => $certificate->verification_token,
            'verification_url' => url("/api/certificate/verify/{$certificate->verification_token}"),
            'data' => json_decode($certificate->certificate_data, true),
        ]);
    }

    /**
     * Create certificate record only (no PDF generation)
     */
    private function createCertificateData($user, $course, $userCourse)
    {
        // Generate unique verification token
        $verificationToken = Str::uuid();
        $serialNumber = $this->generateSerialNumber();

        // Create certificate record
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'user_course_id' => $userCourse->id,
            'verification_token' => $verificationToken,
            'serial_number' => $serialNumber,
            'student_name' => $user->name,
            'issued_at' => now(),
            'certificate_data' => json_encode([
                'user_name' => $user->name,
                'user_email' => $user->email,
                'course_title' => $course->title,
                'course_description' => $course->description,
                'completion_date' => $userCourse->completed_at,
                'enrollment_date' => $userCourse->created_at,
                'progress_percentage' => $userCourse->progress_percentage,
                'final_exam_score' => $userCourse->final_exam_score,
                'serial_number' => $serialNumber,
                'verification_token' => $verificationToken,
                'user_id' => $user->id,
                'course_id' => $course->id,
            ])
        ]);

        // Note: PDF generation removed - external tool will handle this
        // The certificate data is now ready for external processing

        return $certificate;
    }

    /**
     * PDF generation removed - external tool will handle certificate creation
     * 
     * This method previously generated PDF certificates using DomPDF.
     * Now the certificate data is prepared and stored, waiting for external processing.
     * 
     * External tool should:
     * 1. Receive certificate data via API
     * 2. Generate PDF using preferred technology
     * 3. Upload/store the generated PDF
     * 4. Update certificate record with file_path
     */
    private function generateCertificatePDF($certificate, $user, $course, $userCourse)
    {
        // This method is deprecated - PDF generation moved to external tool
        // Certificate data is available in $certificate->certificate_data
        // External tool should process this data and update file_path
        
        // For now, just log that external processing is needed
        \Illuminate\Support\Facades\Log::info('Certificate data ready for external PDF generation', [
            'certificate_id' => $certificate->id,
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    /**
     * Certificate PDF download removed - external tool will handle file creation
     * 
     * This method previously downloaded PDF certificates.
     * Now certificates are processed by external tool which should update file_path.
     */

    /**
     * Public certificate verification
     */
    public function verifyCertificate($token)
    {
        $certificate = Certificate::where('verification_token', $token)
            ->with(['user', 'course'])
            ->first();

        if (!$certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate not found or invalid token'
            ], 404);
        }

        // If file_path is an external URL, redirect to it
        if (!empty($certificate->file_path) && preg_match('/^https?:\/\//i', $certificate->file_path)) {
            return redirect()->away($certificate->file_path);
        }

        // If file exists in local storage, stream it inline
        if (!empty($certificate->file_path) && Storage::disk('public')->exists($certificate->file_path)) {
            $filePath = Storage::disk('public')->path($certificate->file_path);
            $fileName = "Certificate_{$certificate->course->title}_{$certificate->user->name}.pdf";
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        }

        // Fallback: return certificate data JSON when no accessible file
        $certificateData = is_array($certificate->certificate_data)
            ? $certificate->certificate_data
            : (json_decode($certificate->certificate_data ?? '[]', true) ?? []);
        return response()->json([
            'valid' => true,
            'certificate' => [
                'id' => $certificate->id,
                'user_name' => $certificate->user->name,
                'course_title' => $certificate->course->title,
                'issued_at' => optional($certificate->issued_at)->format('F d, Y'),
                'completion_date' => $certificateData['completion_date'] ?? null,
                'verification_token' => $certificate->verification_token,
                'serial_number' => $certificate->serial_number,
                'file_path' => $certificate->file_path,
            ]
        ]);
    }

    /**
     * Update course certificate file path (user-owned)
     */
    public function updateCertificateFilePath($certificateId, Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'file_path' => 'required|string|max:2048',
        ]);

        $certificate = Certificate::where('id', $certificateId)
            ->where('user_id', $user->id)
            ->with(['user', 'course'])
            ->first();

        if (!$certificate) {
            return response()->json(['message' => 'الشهادة غير موجودة أو لا تخص هذا المستخدم'], 404);
        }

        $certificate->update(['file_path' => $data['file_path']]);

        $payload = is_array($certificate->certificate_data)
            ? $certificate->certificate_data
            : (json_decode($certificate->certificate_data ?? '[]', true) ?? []);
        return response()->json([
            'message' => 'تم تحديث مسار ملف الشهادة',
            'certificate' => [
                'id' => $certificate->id,
                'verification_token' => $certificate->verification_token,
                'verification_url' => url("/api/certificate/verify/{$certificate->verification_token}"),
                'issued_at' => optional($certificate->issued_at)->toIso8601String(),
                'file_path' => $this->filePathToUrl($certificate->file_path),
                'user' => ['id' => $certificate->user->id, 'name' => $certificate->user->name],
                'course' => ['id' => $certificate->course->id, 'title' => $certificate->course->title],
                'data' => $payload,
            ],
        ]);
    }

    /**
     * Update diploma certificate file path (user-owned)
     */
    public function updateDiplomaCertificateFilePath($certificateId, Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'file_path' => 'required|string|max:2048',
        ]);

        $certificate = DiplomaCertificate::where('id', $certificateId)
            ->where('user_id', $user->id)
            ->with(['user', 'category'])
            ->first();

        if (!$certificate) {
            return response()->json(['message' => 'شهادة الدبلومة غير موجودة أو لا تخص هذا المستخدم'], 404);
        }

        $certificate->update(['file_path' => $data['file_path']]);

        $payload = is_array($certificate->certificate_data)
            ? $certificate->certificate_data
            : (json_decode($certificate->certificate_data ?? '[]', true) ?? []);
        return response()->json([
            'message' => 'تم تحديث مسار ملف شهادة الدبلومة',
            'certificate' => [
                'id' => $certificate->id,
                'verification_token' => $certificate->verification_token,
                'verification_url' => url("/api/diploma-certificate/verify/{$certificate->verification_token}"),
                'issued_at' => optional($certificate->issued_at)->toIso8601String(),
                'file_path' => $this->filePathToUrl($certificate->file_path),
                'user' => ['id' => $certificate->user->id, 'name' => $certificate->user->name],
                'diploma' => ['id' => $certificate->category->id, 'name' => $certificate->category->name],
                'data' => $payload,
            ],
        ]);
    }

    /**
     * Get user's certificates (authenticated user)
     */
    public function myCertificates()
    {
        $user = Auth::user();

        $certificates = Certificate::where('user_id', $user->id)
            ->with('course')
            ->orderBy('issued_at', 'desc')
            ->get();

        return response()->json([
            'certificates' => $certificates->map(function ($certificate) {
                $certificateData = is_array($certificate->certificate_data)
                    ? $certificate->certificate_data
                    : (json_decode($certificate->certificate_data ?? '[]', true) ?? []);

                return [
                    'id' => $certificate->id,
                    'course_title' => optional($certificate->course)->title,
                    'issued_at' => optional($certificate->issued_at)->format('F d, Y'),
                    'completion_date' => $certificateData['completion_date'] ?? null,
                    'verification_token' => $certificate->verification_token,
                    'serial_number' => $certificate->serial_number,
                    'verification_url' => url("/api/certificate/verify/{$certificate->verification_token}"),
                    'file_path' => $this->filePathToUrl($certificate->file_path),
                    'download_url' => url("/api/courses/{$certificate->course_id}/certificate"),
                ];
            })
        ]);
    }

    /**
     * Admin: Get certificates for a specific user
     */
    public function userCertificatesAdmin(User $user)
    {
        $certificates = Certificate::where('user_id', $user->id)
            ->with('course')
            ->orderBy('issued_at', 'desc')
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'certificates' => $certificates->map(function ($certificate) {
                $data = json_decode($certificate->certificate_data, true);
                return [
                    'id' => $certificate->id,
                    'course_id' => $certificate->course_id,
                    'course_title' => $certificate->course->title,
                    'issued_at' => optional($certificate->issued_at)->format('F d, Y'),
                    'completion_date' => $data['completion_date'] ?? null,
                    'verification_token' => $certificate->verification_token,
                    'serial_number' => $certificate->serial_number,
                    'verification_url' => url("/api/certificate/verify/{$certificate->verification_token}"),
                    'file_path' => $this->filePathToUrl($certificate->file_path),
                    'download_url' => url("/api/courses/{$certificate->course_id}/certificate"),
                ];
            })
        ]);
    }



    /**
     * Regenerate certificate data (admin only or in case of updates) - no PDF generation
     */
    public function regenerateCertificate($certificateId)
    {
        $user = Auth::user();

        $certificate = Certificate::where('id', $certificateId)
            ->where('user_id', $user->id)
            ->with(['user', 'course'])
            ->first();

        if (!$certificate) {
            return response()->json([
                'message' => 'Certificate not found'
            ], 404);
        }

        // Get user course data
        $userCourse = UserCourse::where('user_id', $user->id)
            ->where('course_id', $certificate->course_id)
            ->first();

        if (!$userCourse) {
            return response()->json([
                'message' => 'Course enrollment not found'
            ], 404);
        }

        // Update certificate data (no PDF regeneration)
        $certificate->update([
            'certificate_data' => json_encode([
                'user_name' => $user->name,
                'user_email' => $user->email,
                'course_title' => $certificate->course->title,
                'course_description' => $certificate->course->description,
                'completion_date' => $userCourse->completed_at,
                'enrollment_date' => $userCourse->created_at,
                'progress_percentage' => $userCourse->progress_percentage,
                'final_exam_score' => $userCourse->final_exam_score,
                'serial_number' => $certificate->serial_number,
                'verification_token' => $certificate->verification_token,
                'user_id' => $user->id,
                'course_id' => $certificate->course->id,
            ])
        ]);

        // Log that external PDF regeneration is needed
        \Illuminate\Support\Facades\Log::info('Certificate data updated - external PDF regeneration needed', [
            'certificate_id' => $certificate->id,
            'user_id' => $user->id,
            'course_id' => $certificate->course->id,
        ]);

        return response()->json([
            'message' => 'Certificate data updated successfully - external PDF generation required',
            'certificate_id' => $certificate->id,
            'verification_token' => $certificate->verification_token,
            'data' => json_decode($certificate->certificate_data, true),
        ]);
    }

    /**
     * Bulk certificate data preparation for admin (no PDF generation)
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        $courseId = $request->course_id;

        // Get all users who completed the course but don't have certificates
        $completedUsers = UserCourse::where('course_id', $courseId)
            ->where('status', 'completed')
            ->whereDoesntHave('certificates')
            ->with(['user', 'course'])
            ->get();

        $preparedCount = 0;
        $certificateIds = [];

        foreach ($completedUsers as $userCourse) {
            try {
                $certificate = $this->createCertificateData($userCourse->user, $userCourse->course, $userCourse);
                $preparedCount++;
                $certificateIds[] = $certificate->id;
            } catch (\Exception $e) {
                // Log error but continue with other certificates
                \Illuminate\Support\Facades\Log::error("Failed to prepare certificate data for user {$userCourse->user_id}: " . $e->getMessage());
            }
        }

        // Log that external PDF processing is needed
        if ($preparedCount > 0) {
            \Illuminate\Support\Facades\Log::info('Bulk certificate data prepared - external PDF generation needed', [
                'course_id' => $courseId,
                'certificate_ids' => $certificateIds,
                'count' => $preparedCount,
            ]);
        }

        return response()->json([
            'message' => "Prepared {$preparedCount} certificate records for external PDF generation",
            'total_eligible' => $completedUsers->count(),
            'prepared' => $preparedCount,
            'certificate_ids' => $certificateIds,
        ]);
    }

    /**
     * Admin: Issue diploma certificate for a user if all courses are completed
     */
    public function issueDiplomaCertificate(Request $request, \App\Models\CategoryOfCourse $category)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);

        // Verify active or completed enrollment exists
        $enrollment = \App\Models\UserCategoryEnrollment::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->orderByDesc('status')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => 'المستخدم غير مُسجل في هذه الدبلومة.'
            ], 403);
        }

        // Check that user has completed all courses in the category
        $courses = $category->courses()->withoutGlobalScopes()->get();
        $incomplete = [];
        foreach ($courses as $course) {
            $uc = \App\Models\UserCourse::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            $passedFinal = false;
            if ($uc) {
                $passingScore = optional($course->finalExam)->passing_score ?? 50;
                $passedFinal = !is_null($uc->final_exam_score) && $uc->final_exam_score >= (int)$passingScore;
            }
            $isCompleted = $uc && $uc->status === 'completed';
            if (!($isCompleted || $passedFinal)) {
                $incomplete[] = $course->title;
            }
        }

        if (count($courses) === 0) {
            return response()->json([
                'message' => 'لا توجد مقررات ضمن هذه الدبلومة.'
            ], 422);
        }

        if (!empty($incomplete)) {
            return response()->json([
                'message' => 'لا يمكن إصدار شهادة الدبلومة قبل إكمال جميع المقررات.',
                'incomplete_courses' => $incomplete,
            ], 403);
        }

        // Prevent duplicate issuance
        $existing = \App\Models\DiplomaCertificate::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->first();

        if ($existing) {
            // Return existing certificate data (no file download)
            return response()->json([
                'message' => 'Diploma certificate data already prepared',
                'certificate_id' => $existing->id,
                'verification_token' => $existing->verification_token,
                'verification_url' => url("/api/diploma-certificate/verify/{$existing->verification_token}"),
                'data' => json_decode($existing->certificate_data, true),
            ]);
        }

        // Create new diploma certificate
        $verificationToken = \Illuminate\Support\Str::uuid();
        $serialNumber = $this->generateSerialNumber();
        $certificate = \App\Models\DiplomaCertificate::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'user_category_enrollment_id' => $enrollment->id,
            'verification_token' => $verificationToken,
            'serial_number' => $serialNumber,
            'student_name' => $user->name,
            'issued_at' => now(),
            'certificate_data' => json_encode([
                'user_name' => $user->name,
                'user_email' => $user->email,
                'diploma_name' => $category->name,
                'completion_date' => $enrollment->completed_at ?? now(),
                'serial_number' => $serialNumber,
                'verification_token' => $verificationToken,
                'user_id' => $user->id,
                'category_id' => $category->id,
            ]),
        ]);

        // Log that external PDF processing is needed
        \Illuminate\Support\Facades\Log::info('New diploma certificate data prepared - external PDF generation needed', [
            'diploma_certificate_id' => $certificate->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        return response()->json([
            'message' => 'Diploma certificate data prepared successfully - external PDF generation required',
            'certificate_id' => $certificate->id,
            'verification_token' => $certificate->verification_token,
            'verification_url' => url("/api/diploma-certificate/verify/{$certificate->verification_token}"),
            'data' => json_decode($certificate->certificate_data, true),
        ]);
    }

    /**
     * Diploma PDF generation removed - external tool will handle certificate creation
     * 
     * This method previously generated diploma PDF certificates using DomPDF.
     * Now the diploma certificate data is prepared and stored, waiting for external processing.
     */
    private function generateDiplomaCertificatePDF(\App\Models\DiplomaCertificate $certificate, \App\Models\User $user, \App\Models\CategoryOfCourse $category, \App\Models\UserCategoryEnrollment $enrollment)
    {
        // This method is deprecated - PDF generation moved to external tool
        // Diploma certificate data is available in $certificate->certificate_data
        // External tool should process this data and update file_path
        
        // For now, just log that external processing is needed
        \Illuminate\Support\Facades\Log::info('Diploma certificate data ready for external PDF generation', [
            'diploma_certificate_id' => $certificate->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
    }

    /**
     * Student: Get diploma certificate data (no download)
     */
    public function downloadDiplomaCertificate(\App\Models\CategoryOfCourse $category)
    {
        $user = Auth::user();
        $certificate = \App\Models\DiplomaCertificate::where('user_id', $user->id)
            ->where('diploma_id', $category->id)
            ->first();

        if (!$certificate || $certificate->status !== 'completed') {
            return response()->json(['message' => 'لم يتم إصدار شهادة مكتملة لهذه الدبلومة بعد.'], 404);
        }

        return response()->json([
            'message' => 'Diploma certificate data retrieved successfully',
            'certificate_id' => $certificate->id,
            'verification_token' => $certificate->verification_token,
            'verification_url' => url("/api/diploma-certificate/verify/{$certificate->verification_token}"),
            'data' => json_decode($certificate->certificate_data, true),
            'file_path' => $this->filePathToUrl($certificate->file_path),
        ]);
    }

    /**
     * External tool: Get pending certificates for PDF generation
     */
    public function getPendingCertificates(Request $request)
    {
        // Validate API token or authentication
        $request->validate([
            'type' => 'sometimes|string|in:course,diploma,all',
            'limit' => 'sometimes|integer|min:1|max:100'
        ]);

        $type = $request->input('type', 'all');
        $limit = $request->input('limit', 50);

        $pendingCertificates = collect();

        // Get course certificates without file_path
        if ($type === 'course' || $type === 'all') {
            $courseCertificates = Certificate::whereNull('file_path')
                ->orWhere('file_path', '')
                ->with(['user', 'course'])
                ->limit($limit)
                ->get()
                ->map(function ($certificate) {
                    $data = json_decode($certificate->certificate_data, true) ?? [];
                    return [
                        'type' => 'course',
                        'id' => $certificate->id,
                        'serial_number' => $certificate->serial_number,
                        'verification_token' => $certificate->verification_token,
                        'user' => [
                            'id' => $certificate->user->id,
                            'name' => $certificate->user->name,
                            'email' => $certificate->user->email,
                        ],
                        'course' => [
                            'id' => $certificate->course->id,
                            'title' => $certificate->course->title,
                        ],
                        'data' => $data,
                        'created_at' => $certificate->created_at->toIso8601String(),
                        'updated_at' => $certificate->updated_at->toIso8601String(),
                    ];
                });
            
            $pendingCertificates = $pendingCertificates->merge($courseCertificates);
        }

        // Get diploma certificates without file_path
        if ($type === 'diploma' || $type === 'all') {
            $diplomaCertificates = DiplomaCertificate::whereNull('file_path')
                ->orWhere('file_path', '')
                ->with(['user', 'category'])
                ->limit($limit)
                ->get()
                ->map(function ($certificate) {
                    $data = json_decode($certificate->certificate_data, true) ?? [];
                    return [
                        'type' => 'diploma',
                        'id' => $certificate->id,
                        'serial_number' => $certificate->serial_number,
                        'verification_token' => $certificate->verification_token,
                        'user' => [
                            'id' => $certificate->user->id,
                            'name' => $certificate->user->name,
                            'email' => $certificate->user->email,
                        ],
                        'diploma' => [
                            'id' => $certificate->category->id,
                            'name' => $certificate->category->name,
                        ],
                        'data' => $data,
                        'created_at' => $certificate->created_at->toIso8601String(),
                        'updated_at' => $certificate->updated_at->toIso8601String(),
                    ];
                });
            
            $pendingCertificates = $pendingCertificates->merge($diplomaCertificates);
        }

        return response()->json([
            'pending_certificates' => $pendingCertificates->values(),
            'count' => $pendingCertificates->count(),
            'type' => $type,
        ]);
    }

    /**
     * External tool: Update certificate with generated PDF file path
     */
    public function updateCertificateFilePathExternal(Request $request, $certificateId)
    {
        $request->validate([
            'type' => 'required|string|in:course,diploma',
            'file_path' => 'required|string|max:2048',
        ]);

        $type = $request->input('type');
        $filePath = $request->input('file_path');

        if ($type === 'course') {
            $certificate = Certificate::findOrFail($certificateId);
        } else {
            $certificate = DiplomaCertificate::findOrFail($certificateId);
        }

        // Update certificate with file path
        $certificate->update(['file_path' => $filePath]);

        // Log successful external processing
        \Illuminate\Support\Facades\Log::info('Certificate PDF updated by external tool', [
            'type' => $type,
            'certificate_id' => $certificateId,
            'file_path' => $filePath,
        ]);

        return response()->json([
            'message' => 'Certificate file path updated successfully',
            'certificate_id' => $certificate->id,
            'type' => $type,
            'file_path' => $filePath,
            'verification_url' => url("/api/" . ($type === 'course' ? 'certificate' : 'diploma-certificate') . "/verify/{$certificate->verification_token}"),
        ]);
    }

    /**
     * Admin: Search certificates by serial number across course and diploma
     */
    public function searchCertificatesAdmin(Request $request)
    {
        $request->validate([
            'serial' => 'required|string|min:3',
        ]);
        $serial = $request->query('serial');

        $courses = Certificate::query()
            ->with(['user', 'course'])
            ->where('serial_number', 'LIKE', "%{$serial}%")
            ->orderByDesc('issued_at')
            ->limit(50)
            ->get()
            ->map(function ($c) {
                $data = json_decode($c->certificate_data, true) ?? [];
                return [
                    'type' => 'course',
                    'id' => $c->id,
                    'serial_number' => $c->serial_number,
                    'user' => [
                        'id' => $c->user->id,
                        'name' => $c->user->name,
                        'email' => $c->user->email,
                    ],
                    'course' => [
                        'id' => $c->course->id,
                        'title' => $c->course->title,
                    ],
                    'issued_at' => optional($c->issued_at)->format('F d, Y'),
                    'completion_date' => $data['completion_date'] ?? null,
                    'file_path' => $this->filePathToUrl($c->file_path),
                    'download_url' => url("/api/courses/{$c->course_id}/certificate"),
                ];
            });

        $diplomas = \App\Models\DiplomaCertificate::query()
            ->with(['user', 'category'])
            ->where('serial_number', 'LIKE', "%{$serial}%")
            ->orderByDesc('issued_at')
            ->limit(50)
            ->get()
            ->map(function ($c) {
                $data = json_decode($c->certificate_data, true) ?? [];
                return [
                    'type' => 'diploma',
                    'id' => $c->id,
                    'serial_number' => $c->serial_number,
                    'user' => [
                        'id' => $c->user->id,
                        'name' => $c->user->name,
                        'email' => $c->user->email,
                    ],
                    'diploma' => [
                        'id' => $c->category->id,
                        'name' => $c->category->name,
                    ],
                    'issued_at' => optional($c->issued_at)->format('F d, Y'),
                    'completion_date' => $data['completion_date'] ?? null,
                    'file_path' => $this->filePathToUrl($c->file_path),
                    'download_url' => url("/api/categories/{$c->category_id}/certificate"),
                ];
            });

        return response()->json([
            'query' => $serial,
            'results' => $courses->merge($diplomas)->values(),
        ]);
    }

    /**
     * Verify course certificate by token
     */
    public function verifyCourseCertificate($token)
    {
        $certificate = CourseCertificate::where('verification_token', $token)
            ->with(['user', 'course'])
            ->first();

        if (!$certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate not found or invalid token'
            ], 404);
        }

        // If file_path is an external URL, redirect to it
        if (!empty($certificate->file_path) && preg_match('/^https?:\/\//i', $certificate->file_path)) {
            return redirect()->away($certificate->file_path);
        }

        // If file exists in local storage, stream it inline
        if (!empty($certificate->file_path) && Storage::disk('public')->exists($certificate->file_path)) {
            $filePath = Storage::disk('public')->path($certificate->file_path);
            $fileName = "Certificate_{$certificate->course->title}_{$certificate->user->name}.pdf";
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        }

        // Fallback: return certificate data JSON when no accessible file
        $certificateData = is_array($certificate->certificate_data)
            ? $certificate->certificate_data
            : (json_decode($certificate->certificate_data ?? '[]', true) ?? []);
        return response()->json([
            'valid' => true,
            'certificate' => [
                'id' => $certificate->id,
                'user_name' => $certificate->user->name,
                'course_title' => $certificate->course->title,
                'issued_at' => optional($certificate->issued_at)->format('F d, Y'),
                'completion_date' => $certificateData['completion_date'] ?? null,
                'verification_token' => $certificate->verification_token,
                'serial_number' => $certificate->serial_number,
                'file_path' => $certificate->file_path,
            ]
        ]);
    }
    /**
     * Request a new course certificate
     * Creates a new certificate request for authenticated student
     */
    public function requestCertificate(Request $request, $courseId)
    {
        $user = Auth::user();

        // Check if user is enrolled in the course
        $userCourse = UserCourse::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->with('course')
            ->first();

        if (!$userCourse) {
            return response()->json([
                'message' => 'غير مسجل في هذا المقرر'
            ], 403);
        }

        // Check if course is completed
        if ($userCourse->status !== 'completed') {
            return response()->json([
                'message' => 'المقرر غير مكتمل بعد'
            ], 403);
        }

        // Check if final exam score meets passing criteria (50% or more)
        $course = $userCourse->course;
        $passingScore = optional($course->finalExam)->passing_score ?? 50; // Default passing score is 50%

        if (!$userCourse->final_exam_score || $userCourse->final_exam_score < $passingScore) {
            return response()->json([
                'message' => 'لم يتم اجتياز الاختبار النهائي بنجاح',
                'required_score' => $passingScore,
                'current_score' => $userCourse->final_exam_score,
                'details' => 'يجب الحصول على درجة ' . $passingScore . '% أو أكثر في الاختبار النهائي للحصول على الشهادة'
            ], 403);
        }

        // Check if there's already a certificate request (pending or otherwise)
        $existingCertificate = \App\Models\CourseCertificate::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if ($existingCertificate) {
            // If the status is 'failed', we allow retrying
            if ($existingCertificate->status === 'failed') {
                $existingCertificate->update([
                    'status' => 'pending',
                    'certificate_data' => null, // Reset data
                ]);

                // Dispatch the job again
                \App\Jobs\GenerateCertificateJob::dispatch($existingCertificate);

                return response()->json([
                    'message' => 'تم إعادة طلب الشهادة بنجاح',
                    'certificate_id' => $existingCertificate->id,
                    'status' => $existingCertificate->status,
                    'created_at' => $existingCertificate->created_at->toIso8601String(),
                ], 200);
            }

            return response()->json([
                'message' => 'يوجد طلب شهادة سابق لهذا المقرر',
                'certificate_id' => $existingCertificate->id,
                'status' => $existingCertificate->status,
                'file_path' => $existingCertificate->file_path,
                'serial_number' => $existingCertificate->serial_number,
            ], 422);
        }

        // Create new certificate request
        $certificate = \App\Models\CourseCertificate::create([
            'user_id' => $user->id,
            'course_id' => $courseId,
            'status' => 'pending',
            'serial_number' => null, // Will be generated when PDF is created
            'certificate_data' => null, // Will be populated when PDF is created
        ]);

        // Dispatch the job to generate the certificate PDF in the background
        \App\Jobs\GenerateCertificateJob::dispatch($certificate);

        return response()->json([
            'message' => 'تم إنشاء طلب الشهادة بنجاح',
            'certificate_id' => $certificate->id,
            'status' => $certificate->status,
            'created_at' => $certificate->created_at->toIso8601String(),
        ], 201);
    }

    public function getCertificateStatus(Request $request, $courseId)
    {
        $user = Auth::user();

        // Search for existing certificate record
        $certificate = \App\Models\CourseCertificate::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$certificate) {
            return response()->json([
                'message' => 'No certificate request found for this course'
            ], 404);
        }

        return response()->json([
            'id' => $certificate->id,
            'user_id' => $certificate->user_id,
            'course_id' => $certificate->course_id,
            'status' => $certificate->status,
            'file_path' => $certificate->file_path,
            'file_url' => $this->filePathToUrl($certificate->file_path),
            'serial_number' => $certificate->serial_number,
            'certificate_data' => $certificate->certificate_data,
            'created_at' => $certificate->created_at->toIso8601String(),
            'updated_at' => $certificate->updated_at->toIso8601String(),
        ], 200);
    }
}