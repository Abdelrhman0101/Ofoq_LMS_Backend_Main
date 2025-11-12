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

            // 2. Generate unique serial number (7 digits only)
            // توليد رقم تسلسلي فريد (7 أرقام فقط)
            $serial = null;
            do {
                // 1. توليد رقم عشوائي بين 0 و 9999999
                $random = mt_rand(0, 9999999);

                // 2. تحويله لنص وإضافة أصفار على اليسار لضمان طول 7 خانات (مثلاً: 0054321)
                $serial = str_pad($random, 7, '0', STR_PAD_LEFT);

                // 3. التأكد من أنه غير مستخدم من قبل (Loop until unique)
            } while (CourseCertificate::where('serial_number', $serial)->exists());
            
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

            // --- بداية الكود الجديد ---
            // 1. تحديد مسار الصورة (زي ما إنت كتبته بالظبط)
            $imagePath = public_path('storage/certifecate_cover.jpg');

            // 2. قراءة الصورة وتحويلها لـ Base64
            $imageData = base64_encode(file_get_contents($imagePath));

            // 3. تجهيز الكود الكامل للـ CSS
            $imageBase64 = 'data:image/jpeg;base64,' . $imageData;

            // 4. إضافة الكود للمتغيرات اللي هتروح للـ View
            $certificateData['backgroundImageBase64'] = $imageBase64;
            // --- نهاية الكود الجديد ---

            Log::info('Certificate data prepared', $certificateData);

            // 3. Generate PDF using Browsershot...
            
            Log::info('Certificate data prepared', $certificateData);
            // 3. Generate PDF using Browsershot instead of DomPDF

            // [الخطوة الجديدة] تحويل اسم الطالب لاسم مناسب للملفات (slug)
            $student_slug = Str::slug($user->name); 

            // [السطر المعدل] إنشاء اسم ملف واضح وفريد (باسم الطالب + ID الكورس)
            $fileName = 'certificates/' . $student_slug . '_course_' . $course->id . '_user_' . $user->id . '.pdf';
            $fullPath = storage_path('app/public/' . $fileName);
            
            Browsershot::html(view('certificates.course_certificate_simple', $certificateData)->render())
                ->showBackground()    
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
