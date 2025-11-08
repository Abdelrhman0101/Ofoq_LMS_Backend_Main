<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\UserCourse;
use App\Models\Certificate;
use App\Models\DiplomaCertificate;
use App\Models\CategoryOfCourse;
use App\Models\UserCategoryEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
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
     * Generate and download certificate for completed course
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

        // Check if certificate already exists
        $existingCertificate = Certificate::where('user_id', $user->id)
                                         ->where('course_id', $courseId)
                                         ->first();
        
        if ($existingCertificate) {
            return $this->downloadCertificate($existingCertificate);
        }

        // Generate new certificate
        $certificate = $this->createCertificate($user, $userCourse->course, $userCourse);
        
        return $this->downloadCertificate($certificate);
    }

    /**
     * Create certificate record and PDF
     */
    private function createCertificate($user, $course, $userCourse)
    {
        // Generate unique verification token
        $verificationToken = Str::uuid();
        
        // Create certificate record
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'user_course_id' => $userCourse->id,
            'verification_token' => $verificationToken,
            'issued_at' => now(),
            'certificate_data' => json_encode([
                'user_name' => $user->name,
                'user_email' => $user->email,
                'course_title' => $course->title,
                'course_description' => $course->description,
                'completion_date' => $userCourse->completed_at,
                'enrollment_date' => $userCourse->created_at,
                'progress_percentage' => $userCourse->progress_percentage
            ])
        ]);

        // Generate PDF certificate
        $this->generateCertificatePDF($certificate, $user, $course, $userCourse);
        
        return $certificate;
    }

    /**
     * Generate PDF certificate
     */
    private function generateCertificatePDF($certificate, $user, $course, $userCourse)
    {
        // Verification URL for QR code
        $verificationUrl = url("/api/certificate/verify/{$certificate->verification_token}");
        
        // Certificate data for PDF
        $data = [
            'user_name' => $user->name,
            'course_title' => $course->title,
            'completion_date' => $userCourse->completed_at->format('F d, Y'),
            'verification_token' => $certificate->verification_token,
            'verification_url' => $verificationUrl,
            'certificate_id' => $certificate->id
        ];

        // Generate PDF
        $pdf = Pdf::loadView('certificates.template', $data);
        $pdf->setPaper('A4', 'landscape');
        
        // Save PDF to storage
        $filename = "certificate_{$certificate->id}_{$user->id}.pdf";
        $pdfContent = $pdf->output();
        
        Storage::disk('public')->put("certificates/{$filename}", $pdfContent);
        
        // Update certificate with file path
        $certificate->update([
            'file_path' => "certificates/{$filename}"
        ]);
    }

    /**
     * Download certificate PDF
     */
    private function downloadCertificate($certificate)
    {
        if (!$certificate->file_path || !Storage::disk('public')->exists($certificate->file_path)) {
            return response()->json([
                'message' => 'Certificate file not found'
            ], 404);
        }

        $filePath = Storage::disk('public')->path($certificate->file_path);
        $fileName = "Certificate_{$certificate->course->title}_{$certificate->user->name}.pdf";
        
        return response()->download($filePath, $fileName);
    }

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
            'certificates' => $certificates->map(function($certificate) {
                $certificateData = is_array($certificate->certificate_data)
                    ? $certificate->certificate_data
                    : (json_decode($certificate->certificate_data ?? '[]', true) ?? []);
                
                return [
                    'id' => $certificate->id,
                    'course_title' => optional($certificate->course)->title,
                    'issued_at' => optional($certificate->issued_at)->format('F d, Y'),
                    'completion_date' => $certificateData['completion_date'] ?? null,
                    'verification_token' => $certificate->verification_token,
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
            'certificates' => $certificates->map(function($certificate) {
                $data = json_decode($certificate->certificate_data, true);
                return [
                    'id' => $certificate->id,
                    'course_id' => $certificate->course_id,
                    'course_title' => $certificate->course->title,
                    'issued_at' => optional($certificate->issued_at)->format('F d, Y'),
                    'completion_date' => $data['completion_date'] ?? null,
                    'verification_token' => $certificate->verification_token,
                    'verification_url' => url("/api/certificate/verify/{$certificate->verification_token}"),
                    'file_path' => $this->filePathToUrl($certificate->file_path),
                    'download_url' => url("/api/courses/{$certificate->course_id}/certificate"),
                ];
            })
        ]);
    }

    /**
     * Regenerate certificate (admin only or in case of updates)
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

        // Delete old PDF file
        if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
            Storage::disk('public')->delete($certificate->file_path);
        }

        // Regenerate PDF
        $this->generateCertificatePDF($certificate, $certificate->user, $certificate->course, $userCourse);
        
        return response()->json([
            'message' => 'Certificate regenerated successfully',
            'download_url' => url("/api/courses/{$certificate->course_id}/certificate")
        ]);
    }

    /**
     * Bulk certificate generation for admin
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

        $generatedCount = 0;
        
        foreach ($completedUsers as $userCourse) {
            try {
                $this->createCertificate($userCourse->user, $userCourse->course, $userCourse);
                $generatedCount++;
            } catch (\Exception $e) {
                // Log error but continue with other certificates
                \Log::error("Failed to generate certificate for user {$userCourse->user_id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => "Generated {$generatedCount} certificates",
            'total_eligible' => $completedUsers->count(),
            'generated' => $generatedCount
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
            // Return existing download info
            if ($existing->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($existing->file_path)) {
                $path = \Illuminate\Support\Facades\Storage::disk('public')->path($existing->file_path);
                $name = "Diploma_{$category->name}_{$user->name}.pdf";
                return response()->download($path, $name);
            }
            // Regenerate file if missing (admin issuance only)
            $this->generateDiplomaCertificatePDF($existing, $user, $category, $enrollment);
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($existing->file_path);
            $name = "Diploma_{$category->name}_{$user->name}.pdf";
            return response()->download($path, $name);
        }

        // Create new diploma certificate
        $verificationToken = \Illuminate\Support\Str::uuid();
        $certificate = \App\Models\DiplomaCertificate::create([
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

        $this->generateDiplomaCertificatePDF($certificate, $user, $category, $enrollment);

        $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($certificate->file_path);
        $fileName = "Diploma_{$category->name}_{$user->name}.pdf";
        return response()->download($filePath, $fileName);
    }

    /**
     * Generate Diploma PDF
     */
    private function generateDiplomaCertificatePDF(\App\Models\DiplomaCertificate $certificate, \App\Models\User $user, \App\Models\CategoryOfCourse $category, \App\Models\UserCategoryEnrollment $enrollment)
    {
        $verificationUrl = url("/api/diploma-certificate/verify/{$certificate->verification_token}");
        $data = [
            'user_name' => $user->name,
            'diploma_name' => $category->name,
            'completion_date' => ($enrollment->completed_at ?? now())->format('F d, Y'),
            'verification_token' => $certificate->verification_token,
            'verification_url' => $verificationUrl,
            'certificate_id' => $certificate->id,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.diploma', $data);
        $pdf->setPaper('A4', 'landscape');
        $filename = "diploma_certificate_{$certificate->id}_{$user->id}.pdf";
        $pdfContent = $pdf->output();
        \Illuminate\Support\Facades\Storage::disk('public')->put("certificates/{$filename}", $pdfContent);
        $certificate->update(['file_path' => "certificates/{$filename}"]);
    }

    /**
     * Student: Download diploma certificate
     */
    public function downloadDiplomaCertificate(\App\Models\CategoryOfCourse $category)
    {
        $user = Auth::user();
        $certificate = \App\Models\DiplomaCertificate::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->first();

        if (!$certificate) {
            return response()->json(['message' => 'لم يتم إصدار شهادة لهذه الدبلومة بعد.'], 404);
        }

        if (!$certificate->file_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($certificate->file_path)) {
            return response()->json(['message' => 'ملف الشهادة غير موجود.'], 404);
        }

        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($certificate->file_path);
        $name = "Diploma_{$category->name}_{$user->name}.pdf";
        return response()->download($path, $name);
    }
}