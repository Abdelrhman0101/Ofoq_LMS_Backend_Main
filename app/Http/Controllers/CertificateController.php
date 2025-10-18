<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\UserCourse;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
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

        $certificateData = json_decode($certificate->certificate_data, true);
        
        return response()->json([
            'valid' => true,
            'certificate' => [
                'id' => $certificate->id,
                'user_name' => $certificate->user->name,
                'course_title' => $certificate->course->title,
                'issued_at' => $certificate->issued_at->format('F d, Y'),
                'completion_date' => $certificateData['completion_date'] ?? null,
                'verification_token' => $certificate->verification_token
            ]
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
                $certificateData = json_decode($certificate->certificate_data, true);
                
                return [
                    'id' => $certificate->id,
                    'course_title' => $certificate->course->title,
                    'issued_at' => $certificate->issued_at->format('F d, Y'),
                    'completion_date' => $certificateData['completion_date'] ?? null,
                    'verification_token' => $certificate->verification_token,
                    'verification_url' => url("/api/certificate/verify/{$certificate->verification_token}"),
                    'download_url' => url("/api/courses/{$certificate->course_id}/certificate")
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
                    'download_url' => url("/api/courses/{$certificate->course_id}/certificate")
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
}