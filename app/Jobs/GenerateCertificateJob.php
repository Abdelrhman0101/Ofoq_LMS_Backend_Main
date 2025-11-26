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

            // 2. Generate or reuse unique serial number (7 digits only)
            $serial = $this->certificate->serial_number;
            if (empty($serial)) {
                do {
                    $random = mt_rand(0, 9999999);
                    $serial = str_pad($random, 7, '0', STR_PAD_LEFT);
                } while (CourseCertificate::where('serial_number', $serial)->exists());
                // Persist serial early to keep DB and PDF consistent
                $this->certificate->serial_number = $serial;
            }

            // 2.a Ensure a stable verification token exists before generating PDF
            $token = $this->certificate->verification_token;
            if (empty($token)) {
                $token = (string) Str::uuid();
                $this->certificate->verification_token = $token;
            }
            // Save early so QR/verification in PDF matches DB
            $this->certificate->save();
            
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
                'verification_token' => $token,
                'issued_date' => now()->format('F d, Y'),
                'h1_text' => 'شهادة إتمام الدورة التدريبية',
                'p1_text' => 'قد حضر المقرر الدراسي',
                'p2_line1_text' => 'ضمن دبلومة ' . ($diploma_name ?? ''),
                'p2_line2_text' => 'وقد اجتاز الاختبار بنجاح',
                'p2_line3_text' => 'وهذه شهادة منا بذلك سائلين المولي عز وجل له دوام التوفيق والسداد',
                'footer_text' => 'أكمل بتاريخ',
                'title_text' => 'شهادة',
            ];

            // --- بداية التعديل المصحح ---
            // 1. وضع قيمة افتراضية للمتغير لتجنب خطأ Undefined Variable في حالة عدم وجود الصورة
            $certificateData['backgroundImageBase64'] = '';

            // 2. تحديد مسار الصورة (بالاسم الذي أكدته)
            $imagePath = public_path('storage/certifecate_cover.jpg');

            // 3. التحقق والقراءة
            if (file_exists($imagePath) && is_readable($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                $certificateData['backgroundImageBase64'] = 'data:image/jpeg;base64,' . $imageData;
            } else {
                Log::warning('Certificate background image missing or unreadable', ['path' => $imagePath]);
            }
            // --- نهاية التعديل ---

            Log::info('Certificate data prepared', $certificateData);

            // 3. Generate PDF using Browsershot...
            
            Log::info('Certificate data prepared', $certificateData);
            // 3. Generate PDF using Browsershot instead of DomPDF

            // [الخطوة الجديدة] تحويل اسم الطالب لاسم مناسب للملفات (slug)
            $student_slug = Str::slug($user->name); 

            // توحيد المسار تحت certificates/courses باستخدام الرقم التسلسلي
            $fileName = 'certificates/courses/' . $serial . '.pdf';
            $fullPath = storage_path('app/public/' . $fileName);

            // تأكيد وجود مجلد الحفظ داخل قرص public قبل توليد الملف
            if (!Storage::disk('public')->exists('certificates/courses')) {
                Storage::disk('public')->makeDirectory('certificates/courses');
            }
            
            $browsershot = Browsershot::html(view('certificates.course_certificate_simple', $certificateData)->render())
                ->showBackground()
                ->landscape()
                ->format('A4');

            // Configure Browsershot from environment (OS-aware + Safety Checks)
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            
            // Prefer OS-specific keys, fallback to generic keys
            $nodePath = $isWindows 
                ? config('services.browsershot.node_path_windows') 
                : config('services.browsershot.node_path', '/usr/bin/node');
                
            $chromePath = $isWindows 
                ? config('services.browsershot.chrome_path_windows') 
                : config('services.browsershot.chrome_path_linux', config('services.browsershot.chrome_path'));

            $noSandboxDefault = $isWindows ? false : true;
            $noSandbox = config('services.browsershot.no_sandbox', $noSandboxDefault);

            // Only apply paths if they actually exist on the current machine
            if (!empty($nodePath) && file_exists($nodePath)) {
                $browsershot->setNodeBinary($nodePath);
            }
            if (!empty($chromePath) && file_exists($chromePath)) {
                $browsershot->setChromePath($chromePath);
            }
            if ($noSandbox) {
                $browsershot->noSandbox();
                // Add Linux-friendly chromium args to avoid sandbox and shared memory issues
                $browsershot->addChromiumArguments([
                    'disable-setuid-sandbox',
                    'disable-dev-shm-usage',
                ]);
            }

            $browsershot->save($fullPath);
            
            Log::info('PDF generated successfully', ['file_name' => $fileName, 'path' => $fullPath]);

            // 4. Update the record in database (must match PDF values)
            $this->certificate->update([
                'status' => 'completed',
                'file_path' => $fileName,
                'serial_number' => $serial,
                'verification_token' => $token,
                'certificate_data' => $certificateData,
            ]);

            Log::info('Certificate generated successfully', [
                'certificate_id' => $this->certificate->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'file_path' => $fileName,
            ]);

        } catch (\Exception $e) {
            // 5. In case of failure
            $this->certificate->update([
                'status' => 'failed',
                'certificate_data' => ['error' => $e->getMessage()] // Store error for debugging
            ]);
            
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
