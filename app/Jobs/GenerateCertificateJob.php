<?php

namespace App\Jobs;

use App\Models\CourseCertificate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

use ArPHP\I18N\Arabic;

// Temporary manual autoload for ArPHP until composer issue is resolved
if (!class_exists('ArPHP\I18N\Arabic')) {
    require_once base_path('vendor/khaled.alshamaa/ar-php/src/Arabic.php');
}

class GenerateCertificateJob implements ShouldQueue
{
    use Queueable;

    protected $certificate;

    /**
     * Create a new job instance.
     */
    public function __construct(CourseCertificate $certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting certificate generation', ['certificate_id' => $this->certificate->id]);
            
            // 1. Gather data (student name, course name, dates, etc.)
            $user = $this->certificate->user;
            $course = $this->certificate->course;

            if (!$user || !$course) {
                throw new \Exception('User or Course not found');
            }

            Log::info('Data gathered', ['user' => $user->name, 'course' => $course->title]);

            // 2. Generate unique serial number
            $serial = 'AFUQ-' . $course->id . '-' . $user->id . '-' . Str::random(8);
            Log::info('Serial generated', ['serial' => $serial]);

            // Get course hours (using hours_count field)
            $course_hours = $course->hours_count ?? 0;
            
            // Get diploma name through category (if available)
            $diploma_name = null;
            if ($course->category) {
                $diploma_name = $course->category->name;
            }

            // 2. !! الخطوة السحرية: معالجة النصوص العربية !!
            $obj = new Arabic('Glyphs'); // نهيئ المكتبة
            
            $student_name_ar = $obj->utf8Glyphs($user->name);
            $course_name_ar = $obj->utf8Glyphs($course->title);
            $course_hours_ar = $obj->utf8Glyphs($course_hours . ' ساعات تدريبية');
            $diploma_name_ar = $obj->utf8Glyphs($diploma_name ?? '');
            $completion_date_ar = $obj->utf8Glyphs($this->certificate->updated_at->format('j F Y'));

            // 3. (الخطوة السحرية 2) النصوص الثابتة أيضاً
            $h1_ar = $obj->utf8Glyphs('شهادة إتمام الدورة التدريبية');
            $p1_ar = $obj->utf8Glyphs('قد حضر المقرر الدراسي');
            $p2_line1_ar = $obj->utf8Glyphs('ضمن دبلومة ' . ($diploma_name ?? ''));
            $p2_line2_ar = $obj->utf8Glyphs('وقد اجتاز الاختبار بنجاح');
            $p2_line3_ar = $obj->utf8Glyphs('وهذه شهادة منا بذلك سائلين المولي عز وجل له دوام التوفيق والسداد');
            $footer_ar = $obj->utf8Glyphs('أكمل بتاريخ');
            $title_ar = $obj->utf8Glyphs('شهادة');

            // 4. تجميع البيانات النهائية للـ View
            $certificateData = [
                'student_name' => $student_name_ar,
                'course_name' => $course_name_ar,
                'course_title' => $course_name_ar, // Keep for backward compatibility
                'completion_date' => $completion_date_ar,
                'course_hours' => $course_hours_ar,
                'diploma_name' => $diploma_name_ar,
                'serial_number' => $serial,
                'verification_token' => $this->certificate->verification_token,
                'issued_date' => now()->format('F d, Y'),
                'h1_text' => $h1_ar,
                'p1_text' => $p1_ar,
                'p2_line1_text' => $p2_line1_ar,
                'p2_line2_text' => $p2_line2_ar,
                'p2_line3_text' => $p2_line3_ar,
                'footer_text' => $footer_ar,
                'title_text' => $title_ar,
            ];

            Log::info('Certificate data prepared', $certificateData);

            // Load the certificate view and generate PDF
            $pdf = Pdf::loadView('certificates.course_certificate_simple', $certificateData);
            $pdfContent = $pdf->output();
            Log::info('PDF generated successfully');

            // Save PDF to storage
            $fileName = 'certificates/course_' . $course->id . '_user_' . $user->id . '_' . Str::random(10) . '.pdf';
            $pdfPath = Storage::disk('public')->put($fileName, $pdfContent);
            Log::info('PDF saved to storage', ['file_name' => $fileName, 'path' => $pdfPath]);

            // 4. Update the record in database (most important)
            $this->certificate->update([
                'status' => 'completed',
                'file_path' => $fileName,
                'serial_number' => $serial,
                'verification_token' => Str::uuid(),
            ]);

            Log::info('Certificate generated successfully', [
                'certificate_id' => $this->certificate->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'file_path' => $fileName,
            ]);

        } catch (\Exception $e) {
            // 5. In case of failure
            $this->certificate->update(['status' => 'failed']);
            
            Log::error('Certificate Generation Failed: ' . $e->getMessage(), [
                'certificate_id' => $this->certificate->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
