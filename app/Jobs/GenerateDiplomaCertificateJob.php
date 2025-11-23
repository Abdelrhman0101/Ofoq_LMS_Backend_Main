<?php

namespace App\Jobs;

use App\Models\DiplomaCertificate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class GenerateDiplomaCertificateJob implements ShouldQueue
{
    use Queueable;

    protected $diplomaCertificate;

    /**
     * Create a new job instance.
     */
    public function __construct(DiplomaCertificate $diplomaCertificate)
    {
        $this->diplomaCertificate = $diplomaCertificate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting diploma certificate generation', ['diploma_certificate_id' => $this->diplomaCertificate->id]);
            
            // 1. Gather data (student name, diploma name, dates, etc.)
            $user = $this->diplomaCertificate->user;
            $diploma = $this->diplomaCertificate->diploma;

            if (!$user || !$diploma) {
                throw new \Exception('User or Diploma not found');
            }

            Log::info('Data gathered', ['user' => $user->name, 'diploma' => $diploma->name]);

            // 2. Generate unique serial number (7 digits only)
            // توليد رقم تسلسلي فريد (7 أرقام فقط)
            $serial = null;
            do {
                // 1. توليد رقم عشوائي بين 0 و 9999999
                $random = mt_rand(0, 9999999);

                // 2. تحويله لنص وإضافة أصفار على اليسار لضمان طول 7 خانات (مثلاً: 0054321)
                $serial = str_pad($random, 7, '0', STR_PAD_LEFT);

                // 3. التأكد من أنه غير مستخدم من قبل (Loop until unique)
            } while (DiplomaCertificate::where('serial_number', $serial)->exists());
            
            Log::info('Serial generated', ['serial' => $serial]);

            // Get total courses in this diploma
            $totalCourses = $diploma->courses()->count();
            $coursesText = $totalCourses . ' مقررات تدريبية';

            // 2. تجميع البيانات (بدون معالجة عربية - Browsershot هتتعامل معاها)
            $certificateData = [
                'student_name' => $user->name,
                'diploma_name' => $diploma->name,
                'completion_date' => $this->diplomaCertificate->updated_at->format('j F Y'),
                'total_courses' => $coursesText,
                'serial_number' => $serial,
                'verification_token' => $this->diplomaCertificate->verification_token,
                'issued_date' => now()->format('F d, Y'),
                'h1_text' => 'شهادة إتمام دبلومة مهنية',
                'p1_text' => 'تشهد منصة أفق أن الطالب',
                'p2_text' => 'قد أتم بنجاح جميع متطلبات',
                'footer_text' => 'تم الاعتماد بتاريخ',
                'title_text' => 'شهادة دبلومة',
            ];

            // --- بداية الكود الجديد ---
            // 1. تحديد مسار الصورة
            $imagePath = public_path('storage/certifecate_cover.jpg');

            // 2. قراءة الصورة وتحويلها لـ Base64 بشكل آمن (تخطي عند عدم توفرها)
            if (is_readable($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                // 3. تجهيز الكود الكامل للـ CSS
                $imageBase64 = 'data:image/jpeg;base64,' . $imageData;
                // 4. إضافة الكود للمتغيرات اللي هتروح للـ View
                $certificateData['backgroundImageBase64'] = $imageBase64;
            } else {
                Log::warning('Diploma certificate background image missing or unreadable', ['path' => $imagePath]);
            }
            // --- نهاية الكود الجديد ---

            Log::info('Certificate data prepared', $certificateData);

            // 3. Generate PDF using Browsershot...
            
            Log::info('Certificate data prepared', $certificateData);
            // 3. Generate PDF using Browsershot instead of DomPDF

            // [الخطوة الجديدة] تحويل اسم الطالب لاسم مناسب للملفات (slug)
            $student_slug = Str::slug($user->name); 

            // [السطر المعدل] إنشاء اسم ملف واضح وفريد (باسم الطالب + ID الدبلومة)
            $fileName = 'certificates/diploma_' . Str::slug($diploma->name) . '_user_' . $user->id . '.pdf';
            $fullPath = storage_path('app/public/' . $fileName);

            // تأكيد وجود مجلد الحفظ داخل قرص public قبل توليد الملف
            if (!Storage::disk('public')->exists('certificates')) {
                Storage::disk('public')->makeDirectory('certificates');
            }

            $browsershot = Browsershot::html(view('certificates.diploma_certificate', $certificateData)->render())
                ->showBackground()
                ->landscape()
                ->format('A4');

            // Configure Browsershot from environment if provided
            $nodePath = env('BROWSERSHOT_NODE_PATH');
            $chromePath = env('BROWSERSHOT_CHROME_PATH');
            $noSandbox = env('BROWSERSHOT_NO_SANDBOX', true);

            if (!empty($nodePath)) {
                $browsershot->setNodeBinary($nodePath);
            }
            if (!empty($chromePath)) {
                $browsershot->setChromePath($chromePath);
            }
            if ($noSandbox) {
                $browsershot->noSandbox();
            }

            $browsershot->save($fullPath);
            
            Log::info('PDF generated successfully', ['file_name' => $fileName, 'path' => $fullPath]);

            // 4. Update the record in database (most important)
            $this->diplomaCertificate->update([
                'status' => 'completed',
                'file_path' => $fileName,
                'serial_number' => $serial,
                'verification_token' => Str::uuid(),
            ]);

            Log::info('Diploma Certificate generated successfully', [
                'diploma_certificate_id' => $this->diplomaCertificate->id,
                'user_id' => $user->id,
                'diploma_id' => $diploma->id,
                'file_path' => $fileName,
            ]);

        } catch (\Exception $e) {
            // 5. In case of failure
            $this->diplomaCertificate->update(['status' => 'failed']);
            
            Log::error('Diploma Certificate Generation Failed: ' . $e->getMessage(), [
                'diploma_certificate_id' => $this->diplomaCertificate->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}