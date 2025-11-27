<?php

namespace App\Http\Controllers;

use App\Models\CourseCertificate;
use App\Models\UserQuiz;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CertificateVerificationController extends Controller
{
    /**
     * Verify certificate by serial number
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        // 1. التحقق من المدخلات
        $request->validate([
            'serial_number' => 'required|string',
        ]);

        $serial = $request->serial_number;

        // 2. البحث عن شهادة المقرر (Case Insensitive)
        $certificate = CourseCertificate::whereRaw('LOWER(serial_number) = ?', [strtolower($serial)])
                                        ->with(['user', 'course'])
                                        ->first();

        // 3. إذا وجدت شهادة مقرر، نعيد بياناتها
        if ($certificate) {
            try {
                $grade = null;
                if (!empty($certificate->certificate_data) && isset($certificate->certificate_data['grade'])) {
                     $grade = $certificate->certificate_data['grade'];
                }
                if (!$grade) {
                     $grade = 'ناجح';
                }

                $completionDate = $certificate->updated_at->format('Y-m-d');

                return response()->json([
                    'valid' => true,
                    'data' => [
                        'student_name' => $certificate->user->name ?? 'طالب',
                        'course_title' => $certificate->course->title ?? 'مقرر',
                        'exam_grade' => $grade,
                        'exam_date' => $completionDate,
                        'serial_number' => strtoupper($certificate->serial_number),
                        'type' => 'course'
                    ]
                ], 200);

            } catch (\Exception $e) {
                Log::error('Verification Error (Course): ' . $e->getMessage());
                return response()->json([
                    'valid' => true,
                    'data' => [
                        'student_name' => $certificate->user->name ?? 'طالب',
                        'course_title' => $certificate->course->title ?? 'مقرر',
                        'exam_grade' => 'ناجح',
                        'exam_date' => $certificate->updated_at->format('Y-m-d'),
                        'serial_number' => strtoupper($certificate->serial_number),
                        'type' => 'course'
                    ]
                ], 200);
            }
        }

        // 4. البحث عن شهادة دبلومة (إذا لم نجد شهادة مقرر)
        $diplomaCertificate = \App\Models\DiplomaCertificate::whereRaw('LOWER(serial_number) = ?', [strtolower($serial)])
                                        ->with(['user', 'diploma'])
                                        ->first();

        if ($diplomaCertificate) {
            try {
                // شهادات الدبلومة عادة لا تحتوي على درجة اختبار محددة، بل إتمام
                $grade = 'تم إتمام الدبلومة بنجاح';
                $completionDate = $diplomaCertificate->updated_at->format('Y-m-d');

                return response()->json([
                    'valid' => true,
                    'data' => [
                        'student_name' => $diplomaCertificate->user->name ?? 'طالب',
                        'course_title' => $diplomaCertificate->diploma->name ?? 'دبلومة', // نستخدم حقل course_title ليتوافق مع الواجهة الأمامية
                        'exam_grade' => $grade,
                        'exam_date' => $completionDate,
                        'serial_number' => strtoupper($diplomaCertificate->serial_number),
                        'type' => 'diploma'
                    ]
                ], 200);

            } catch (\Exception $e) {
                Log::error('Verification Error (Diploma): ' . $e->getMessage());
                return response()->json([
                    'valid' => true,
                    'data' => [
                        'student_name' => $diplomaCertificate->user->name ?? 'طالب',
                        'course_title' => $diplomaCertificate->diploma->name ?? 'دبلومة',
                        'exam_grade' => 'ناجح',
                        'exam_date' => $diplomaCertificate->updated_at->format('Y-m-d'),
                        'serial_number' => strtoupper($diplomaCertificate->serial_number),
                        'type' => 'diploma'
                    ]
                ], 200);
            }
        }

        // 5. إذا لم يتم العثور على أي منهما
        return response()->json([
            'valid' => false,
            'message' => 'الرقم التسلسلي غير صحيح أو الشهادة غير موجودة.'
        ], 404);
    }
}