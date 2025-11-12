<?php

namespace App\Jobs;

use App\Models\CourseCertificate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

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

            // 2. تجميع البيانات (بدون معالجة عربية - Browsershot هتتعامل معاها)
            $certificateData = [
                'student_name' => $user->name,
                'course_name' => $course->title,
                'course_title' => $course->title, // Keep for backward compatibility
                'completion_date' => $this->certificate->updated_at->format('j F Y'),
                'course_hours' => $course_hours . ' ساعات تدريبية',
                'diploma_name' => $diploma_name ?? 'دبلومة عامة',
                'serial_number' => $serial,
                'verification_token' => $this->certificate->verification_token,
                'issued_date' => now()->format('F d, Y'),
                'h1_text' => 'شهادة إتمام الدورة التدريبية',
                'p1_text' => 'قد حضر المقرر الدراسي',
                'p2_line1_text' => 'ضمن دبلومة ' . ($diploma_name ?? ''),
                'p2_line2_text' => 'وقد اجتاز الاختبار بنجاح',
                'p2_line3_text' => 'وهذه شهادة منا بذلك سائلين المولي عز وجل له دوام التوفيق والسداد',
                'footer_text' => 'أكمل بتاريخ',
                'title_text' => 'شهادة',
            ];

            Log::info('Certificate data prepared', $certificateData);

            // 3. Generate PDF using Browsershot instead of DomPDF
            $fileName = 'certificates/course_' . $course->id . '_user_' . $user->id . '_' . Str::random(10) . '.pdf';
            $fullPath = storage_path('app/public/' . $fileName);
            
            Browsershot::html(view('certificates.course_certificate_simple', $certificateData)->render())
                ->landscape()
                ->format('A4')
                ->save($fullPath);
            
            Log::info('PDF generated successfully', ['file_name' => $fileName, 'path' => $fullPath]);

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
