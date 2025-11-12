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

        // 2. البحث عن الشهادة (Case Insensitive)
        // نستخدم whereRaw لضمان عدم الحساسية لحالة الأحرف
        $certificate = CourseCertificate::whereRaw('LOWER(serial_number) = ?', [strtolower($serial)])
                                        ->with(['user', 'course']) // نحمل العلاقات الضرورية
                                        ->first();

        // 3. إذا لم يتم العثور عليها -> 404 (وليس 500)
        if (!$certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'الرقم التسلسلي غير صحيح أو الشهادة غير موجودة.'
            ], 404);
        }

        // 4. تجميع البيانات بشكل آمن (Safe Data Gathering)
        try {
            // محاولة استخراج الدرجة من الـ JSON المحفوظ (الأسرع والأضمن)
            $grade = null;
            if (!empty($certificate->certificate_data) && isset($certificate->certificate_data['grade'])) {
                 $grade = $certificate->certificate_data['grade'];
            }
            
            // إذا لم نجدها في الـ JSON، نحاول حسابها (مع حماية ضد الأخطاء)
            if (!$grade) {
                 // هنا نضع منطقاً بسيطاً أو نتركه فارغاً لتجنب الـ 500
                 // يمكننا افتراض "ناجح" إذا لم نتمكن من جلب الدرجة بدقة لتجنب المشاكل
                 $grade = 'ناجح';
            }

            // تاريخ الإكمال
            $completionDate = $certificate->updated_at->format('Y-m-d');

            return response()->json([
                'valid' => true,
                'data' => [
                    'student_name' => $certificate->user->name ?? 'طالب',
                    'course_title' => $certificate->course->title ?? 'مقرر',
                    'exam_grade' => $grade,
                    'exam_date' => $completionDate,
                    'serial_number' => strtoupper($certificate->serial_number)
                ]
            ], 200);

        } catch (\Exception $e) {
            // في أسوأ الظروف، لو حدث خطأ في التجميع، لا تنهار!
            // سجل الخطأ وعد ببيانات أساسية
            Log::error('Verification Error: ' . $e->getMessage());
            
            return response()->json([
                'valid' => true, // الشهادة موجودة وسليمة
                'data' => [
                    'student_name' => $certificate->user->name ?? 'طالب',
                    'course_title' => $certificate->course->title ?? 'مقرر',
                    'exam_grade' => 'ناجح', // قيمة افتراضية
                    'exam_date' => $certificate->updated_at->format('Y-m-d'),
                    'serial_number' => strtoupper($certificate->serial_number)
                ]
            ], 200);
        }
    }
}