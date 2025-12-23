# دليل تطبيق نظام النسخ الاحتياطي (Backup System Implementation Guide)

هذا الدليل يلخص خطوات بناء نظام النسخ الاحتياطي كما هو مطبق في مشروع Ofoq LMS، لسهولة نقله وتطبيقه في مشاريع أخرى.

## 1. تثبيت الحزمة (Package Installation)
يعتمد النظام على حزمة `spatie/laravel-backup`.

```bash
composer require spatie/laravel-backup
```

بعد التثبيت، قم بنشر ملف الإعدادات:
```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

## 2. ضبط الإعدادات (`config/backup.php`)
قم بتعديل الملف `config/backup.php` لضبط ما يلي:

### أ. تحديد المصدر (Source)
تأكد من استثناء المجلدات الكبيرة وغير الضرورية لتوفير المساحة، وإدراج مسارات الصور الهامة بشكل صريح.

```php
'source' => [
    'files' => [
        'include' => [
            base_path(),
            storage_path('app/public'), // التأكد من شمول ملفات وصور المستخدمين
        ],
        'exclude' => [
            base_path('vendor'),
            base_path('node_modules'),
            base_path('storage/framework'),
            base_path('storage/logs'),
            base_path('public/hot'),
        ],
        // ...
    ],
    'databases' => [
        'mysql',
    ],
],
```

### ب. سياسة التنظيف (Cleanup Strategy)
ضبط الاحتفاظ بالنسخ لفترات زمنية محددة (يومي، أسبوعي، شهري).

```php
'cleanup' => [
    'default_strategy' => [
        'keep_all_backups_for_days' => 30,
        'keep_daily_backups_for_days' => 30,
        'keep_weekly_backups_for_weeks' => 4,
        'keep_monthly_backups_for_months' => 1,
        'keep_yearly_backups_for_years' => 2,
        'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
    ],
],
```

## 3. إعدادات قاعدة البيانات (`config/database.php`)
لضمان عمل `mysqldump` بشكل صحيح، أضف إعدادات الـ dump داخل اتصال `mysql`:

```php
'mysql' => [
    // ... الإعدادات الأخرى
    
    // إعدادات النسخ الاحتياطي
    'dump' => [
        'dump_binary_path' => env('DB_DUMP_PATH', ''), // مسار ملفات mysqldump
        'use_single_transaction' => true,
        'timeout' => 60 * 5, // 5 دقائق
    ],
],
```

### ضبط متغيرات البيئة (.env)
هام جداً في بيئة Windows أو السيرفرات التي لا تكون فيها أدوات MySQL مضافة إلى الـ PATH.

```env
# Backup Configurations
# مسار المجلد الذي يحتوي على mysqldump (ضروري لإنشاء النسخ)
DB_DUMP_PATH="C:/xampp/mysql/bin"

# مسار ملف mysql التنفيذي بالكامل (ضروري لاسترجاع النسخ)
DB_CLIENT_PATH="C:/xampp/mysql/bin/mysql.exe"
```

## 4. جدولة المهام (Scheduling)
في ملف `routes/console.php` (أو `app/Console/Kernel.php` في النسخ القديمة)، أضف الأوامر التالية لتعمل تلقائياً:

```php
use Illuminate\Support\Facades\Schedule;

// تشغيل النسخ الاحتياطي كل 3 أيام الساعة 2 صباحاً
Schedule::command('backup:run')->cron('0 2 */3 * *');

// تنظيف النسخ القديمة يومياً الساعة 3 صباحاً
Schedule::command('backup:clean')->daily()->at('03:00');

// مراقبة حالة النسخ يومياً الساعة 4 صباحاً
Schedule::command('backup:monitor')->daily()->at('04:00');
```

## 5. إنشاء نموذج سجل النسخ الاحتياطي (`BackupHistory`)

لتتبع العمليات بشكل منظم بدلاً من الاعتماد فقط على ملفات الـ Log، يفضل إنشاء Model وجدول قاعدة بيانات.

**Migration:**
```bash
php artisan make:model BackupHistory -m
```

```php
Schema::create('backup_histories', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->nullable(); // من قام بالعملية
    $table->string('action'); // create, restore, delete, download
    $table->string('status'); // success, failed
    $table->string('filename')->nullable();
    $table->text('details')->nullable(); // رسائل الخطأ أو تفاصيل إضافية
    $table->string('ip_address')->nullable();
    $table->timestamps();
});
```

**Model (`App\Models\BackupHistory`):**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupHistory extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'status',
        'filename',
        'details',
        'ip_address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

## 6. المتحكم الكامل (`BackupController`)

إليك الكود الكامل للمتحكم، مع دمج وظيفة `logActivity` لتخزين البيانات في قاعدة البيانات باستخدام `BackupHistory`.

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\BackupHistory; // تأكد من استيراد الموديل

class BackupController extends Controller
{
    /**
     * Minimum backup file size in bytes (10KB) - to detect empty/corrupt files
     */
    private const MIN_BACKUP_SIZE = 10240;

    /**
     * Maximum backup file size in bytes (500MB)
     */
    private const MAX_BACKUP_SIZE = 524288000;

    private function backupDiskName(): string
    {
        return config('backup.backup.destination.disks')[0] ?? 'local';
    }

    private function backupDisk()
    {
        return Storage::disk($this->backupDiskName());
    }

    private function backupDir(): string
    {
        return config('backup.backup.name');
    }

    private function sanitizeFilename(string $filename): string
    {
        // Remove any directory components to avoid path traversal
        return basename($filename);
    }

    private function backupPath(string $filename): string
    {
        return $this->backupDir() . '/' . $this->sanitizeFilename($filename);
    }

    private function mysqlBinary(): string
    {
        // Read from config (works when config is cached)
        // Falls back to 'mysql' expecting it to be in PATH
        $connection = config('database.default', 'mysql');
        return config("database.connections.{$connection}.mysql_binary_path", 'mysql');
    }

    /**
     * Log backup activity for audit trail (Database + File Log)
     */
    private function logActivity(string $action, array $context = []): void
    {
        $status = 'success';
        if (Str::contains(strtolower($action), ['fail', 'error', 'reject', 'abort'])) {
            $status = 'failed';
        }

        // 1. File Log
        $logContext = array_merge([
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            'status' => $status,
        ], $context);
        
        if ($status === 'failed') {
            Log::channel('daily')->error("[Backup] $action", $logContext);
        } else {
            Log::channel('daily')->info("[Backup] $action", $logContext);
        }

        // 2. Database History
        try {
            BackupHistory::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'status' => $status,
                'filename' => $context['filename'] ?? null,
                'details' => json_encode($context),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to save backup history: " . $e->getMessage());
        }
    }

    /**
     * Validate backup file integrity
     */
    private function validateBackupFile(string $absolutePath): array
    {
        $errors = [];

        // Check file exists
        if (!file_exists($absolutePath)) {
            return ['valid' => false, 'errors' => ['ملف النسخة الاحتياطية غير موجود']];
        }

        // Check file size
        $fileSize = filesize($absolutePath);
        if ($fileSize < self::MIN_BACKUP_SIZE) {
            $errors[] = 'حجم الملف صغير جداً (أقل من 10KB) - قد يكون الملف فارغاً أو تالفاً';
        }
        if ($fileSize > self::MAX_BACKUP_SIZE) {
            $errors[] = 'حجم الملف كبير جداً (أكثر من 500MB)';
        }

        // Validate ZIP integrity
        $zip = new \ZipArchive;
        $openResult = $zip->open($absolutePath, \ZipArchive::CHECKCONS);
        
        if ($openResult !== true) {
            $zipErrors = [
                \ZipArchive::ER_NOZIP => 'الملف ليس ملف ZIP صالح',
                \ZipArchive::ER_INCONS => 'ملف ZIP غير متسق أو تالف',
                \ZipArchive::ER_CRC => 'فشل فحص CRC - الملف تالف',
                \ZipArchive::ER_OPEN => 'لا يمكن فتح الملف',
                \ZipArchive::ER_READ => 'خطأ في قراءة الملف',
            ];
            $errors[] = $zipErrors[$openResult] ?? "خطأ في فتح ملف ZIP (كود: $openResult)";
            return ['valid' => false, 'errors' => $errors];
        }

        // Check for SQL file inside ZIP
        $hasSqlFile = false;
        $sqlFileSize = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];
            if (Str::endsWith(strtolower($name), '.sql')) {
                $hasSqlFile = true;
                $sqlFileSize = $stat['size'];
                break;
            }
        }
        $zip->close();

        if (!$hasSqlFile) {
            $errors[] = 'لا يوجد ملف SQL داخل النسخة الاحتياطية';
        } elseif ($sqlFileSize < 1000) {
            $errors[] = 'ملف SQL صغير جداً (أقل من 1KB) - قد تكون قاعدة البيانات فارغة';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_size' => $fileSize,
            'sql_size' => $sqlFileSize ?? 0,
        ];
    }

    /**
     * Create an automatic pre-restore backup
     */
    private function createPreRestoreBackup(): array
    {
        try {
            $this->logActivity('Pre-restore backup started');
            
            Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();
            
            $this->logActivity('Pre-restore backup completed', ['output' => $output]);
            
            return [
                'success' => true,
                'message' => 'تم إنشاء نسخة احتياطية من الحالة الحالية',
                'output' => $output,
            ];
        } catch (\Exception $e) {
            $this->logActivity('Pre-restore backup failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'فشل إنشاء نسخة احتياطية: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * List all backups
     */
    public function index()
    {
        try {
            $disk = $this->backupDisk();
            $backupDirectory = $this->backupDir();
            
            $files = $disk->files($backupDirectory);
            $files = array_values(array_filter($files, function ($file) {
                return Str::endsWith($file, '.zip');
            }));
            
            $backups = collect($files)->map(function ($file) use ($disk) {
                return [
                    'path' => $file,
                    'date' => date('Y-m-d H:i:s', $disk->lastModified($file)),
                    'size' => $this->formatBytes($disk->size($file)),
                    'size_bytes' => $disk->size($file),
                    'exists' => $disk->exists($file),
                    'filename' => basename($file),
                ];
            })->sortByDesc('date')->values()->toArray();

            return response()->json([
                'success' => true,
                'backups' => $backups,
                'disk' => $this->backupDiskName(),
                'total_backups' => count($backups),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list backups: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new backup manually
     */
    public function create()
    {
        try {
            $this->logActivity('Manual backup initiated');
            
            Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();
            
            $this->logActivity('Manual backup completed', ['output' => $output]);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            $this->logActivity('Manual backup failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($filename)
    {
        try {
            $disk = $this->backupDisk();
            $path = $this->backupPath($filename);

            if (!$disk->exists($path)) {
                return response()->json(['success' => false, 'message' => 'Backup file not found'], 404);
            }

            $this->logActivity('Backup downloaded', 'success', ['filename' => $filename]);

            return $disk->download($path);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Download failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload/Import a backup file
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|mimes:zip|max:512000', // Max 500MB
            ]);

            $file = $request->file('backup_file');
            $filename = $file->getClientOriginalName();
            $tempPath = $file->getRealPath();
            
            $validation = $this->validateBackupFile($tempPath);
            if (!$validation['valid']) {
                $this->logActivity('Backup upload rejected', 'error', [
                    'filename' => $filename,
                    'errors' => $validation['errors'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من صحة النسخة الاحتياطية',
                    'validation_errors' => $validation['errors'],
                ], 422);
            }
            
            $path = $file->storeAs($this->backupDir(), $this->sanitizeFilename($filename), $this->backupDiskName());
            
            $this->logActivity('Backup uploaded', 'success', [
                'filename' => $filename,
                'path' => $path,
                'size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم رفع النسخة الاحتياطية بنجاح',
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            $this->logActivity('Backup upload failed', 'error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a specific backup
     */
    public function delete($filename)
    {
        try {
            $disk = $this->backupDisk();
            $path = $this->backupPath($filename);
            
            if (!$disk->exists($path)) {
                return response()->json(['success' => false, 'message' => 'Backup file not found'], 404);
            }

            $disk->delete($path);
            
            $this->logActivity('Backup deleted', 'success', ['filename' => $filename]);

            return response()->json(['success' => true, 'message' => 'Backup deleted successfully']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Restore database from backup
     */
    public function restore(Request $request)
    {
        $dumpPath = null;
        
        try {
            $filename = $this->sanitizeFilename((string) $request->input('filename'));
            
            if (!$filename) {
                return response()->json(['success' => false, 'message' => 'Filename is required'], 400);
            }

            $disk = $this->backupDisk();
            $relativePath = $this->backupPath($filename);

            if (!$disk->exists($relativePath)) {
                return response()->json(['success' => false, 'message' => 'Backup file not found'], 404);
            }

            $absolutePath = $disk->path($relativePath);
            
            $this->logActivity('Restore initiated', 'info', ['filename' => $filename]);

            // 1. Validate
            $validation = $this->validateBackupFile($absolutePath);
            if (!$validation['valid']) {
                $this->logActivity('Restore aborted - validation failed', 'error', [
                    'filename' => $filename,
                    'errors' => $validation['errors'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من صحة النسخة الاحتياطية',
                    'validation_errors' => $validation['errors'],
                ], 422);
            }

            // 2. Pre-restore Backup
            $preRestoreBackup = $this->createPreRestoreBackup();
            if (!$preRestoreBackup['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل إنشاء نسخة احتياطية من الحالة الحالية. تم إلغاء العملية.',
                ], 500);
            }

            // 3. Extract and Restore
            $zip = new \ZipArchive;
            if ($zip->open($absolutePath) === TRUE) {
                $dbDumpFile = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (strpos($name, '.sql') !== false) {
                        $dbDumpFile = $name;
                        break;
                    }
                }

                if ($dbDumpFile) {
                    $tempDisk = Storage::disk('local');
                    $tempDisk->makeDirectory('temp');
                    $zip->extractTo($tempDisk->path('temp'));
                    $zip->close();

                    $connection = config('database.default', 'mysql');
                    $dbConfig = (array) config("database.connections.$connection", []);
                    $database = $dbConfig['database'] ?? env('DB_DATABASE');
                    $username = $dbConfig['username'] ?? env('DB_USERNAME');
                    $password = $dbConfig['password'] ?? env('DB_PASSWORD');
                    $host = $dbConfig['host'] ?? env('DB_HOST');
                    $port = (string) ($dbConfig['port'] ?? env('DB_PORT', '3306'));
                    $charset = $dbConfig['charset'] ?? 'utf8mb4';

                    $dumpPath = $tempDisk->path('temp/' . $dbDumpFile);
                    
                    $mysql = escapeshellcmd($this->mysqlBinary());
                    $command = $mysql
                        . ' --host=' . escapeshellarg($host)
                        . ' --port=' . escapeshellarg($port)
                        . ' --user=' . escapeshellarg($username)
                        . ' --password=' . escapeshellarg($password)
                        . ' --default-character-set=' . escapeshellarg($charset) . ' '
                        . escapeshellarg($database)
                        . ' < ' . escapeshellarg($dumpPath)
                        . ' 2>&1';
                    
                    exec($command, $output, $returnVar);

                    if ($returnVar === 0) {
                        $this->logActivity('Restore completed', 'success', ['filename' => $filename]);
                        return response()->json([
                            'success' => true,
                            'message' => 'تم استرجاع قاعدة البيانات بنجاح.',
                            'pre_restore_backup' => true,
                        ]);
                    } else {
                        $this->logActivity('Restore failed - mysql error', 'error', [
                            'filename' => $filename,
                            'output' => implode("\n", $output ?? []),
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'فشل استرجاع قاعدة البيانات.',
                            'error_output' => implode("\n", $output ?? []),
                        ], 500);
                    }
                }
            }

            return response()->json(['success' => false, 'message' => 'فشل فك ضغط ملف النسخة الاحتياطية'], 500);

        } catch (\Exception $e) {
            $this->logActivity('Restore failed - exception', 'error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()], 500);
        } finally {
            if ($dumpPath && file_exists($dumpPath)) {
                unlink($dumpPath);
            }
        }
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
```

## 7. تعريف المسارات (Routes)
في `routes/api.php` أو `routes/admin.php`:

```php
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/backups', [BackupController::class, 'index']);
    Route::post('/backups/create', [BackupController::class, 'create']);
    Route::post('/backups/upload', [BackupController::class, 'upload']);
    Route::post('/backups/restore', [BackupController::class, 'restore']);
    Route::get('/backups/{filename}/download', [BackupController::class, 'download']);
    Route::delete('/backups/{filename}', [BackupController::class, 'delete']);
});
```

## 8. حل المشاكل الشائعة (Troubleshooting)

### خطأ: `Can't create TCP/IP socket (10106)`
هذا الخطأ شائع جداً في بيئة Windows عند استخدام `mysqldump`.

**الحل:**
1. افتح ملف `.env`.
2. غيّر قيمة `DB_HOST` من `localhost` إلى `127.0.0.1`.
   ```env
   DB_HOST=127.0.0.1
   ```
   استخدام عنوان IP بدلاً من الاسم يجبر النظام على استخدام TCP/IP بشكل صحيح.

### خطأ: `The dump process failed with a none successful exitcode`
غالباً بسبب عدم قدرة النظام على العثور على `mysqldump`.

**الحل:**
1. تأكد من مسار `mysqldump` على جهازك.
2. أضف المسار في ملف `.env` (تأكد من استخدام `forward slashes /`):
   ```env
   DB_DUMP_PATH="C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin"
   ```
3. تأكد من أن ملف `config/database.php` يقرأ هذا المتغير:
   ```php
   'dump' => [
       'dump_binary_path' => env('DB_DUMP_PATH', ''),
       // ...
   ],
   ```

## ملاحظات إضافية
*   **الأمان:** تأكد من أن المجلد الذي تُحفظ فيه النسخ (`storage/app/Laravel`) غير متاح للوصول العام (ليس داخل `public`).
*   **الصلاحيات:** تأكد من أن مستخدم الويب (www-data) لديه صلاحيات الكتابة والقراءة على مجلدات الـ storage وتشغيل أوامر الـ shell.
