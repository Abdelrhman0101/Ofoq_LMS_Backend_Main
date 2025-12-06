<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        // Allow overriding mysql client path via env (e.g., C:\laragon\bin\mysql\...\mysql.exe)
        // Fallback to 'mysql' expecting it to be in PATH
        return env('DB_CLIENT_PATH', 'mysql');
    }

    /**
     * Log backup activity for audit trail
     */
    private function logActivity(string $action, array $context = []): void
    {
        $context = array_merge([
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::channel('daily')->info("[Backup] $action", $context);
    }

    /**
     * Validate backup file integrity
     * Returns array with 'valid' boolean and 'errors' array
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
            
            // Run backup with a special naming convention
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
     * GET /api/admin/backups
     */
    public function index()
    {
        try {
            $disk = $this->backupDisk();
            $backupDirectory = $this->backupDir();
            
            // Get all backup files
            $files = $disk->files($backupDirectory);
            // Only include zip files
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
     * POST /api/admin/backups/create
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
     * GET /api/admin/backups/{filename}/download
     */
    public function download($filename)
    {
        try {
            $disk = $this->backupDisk();
            $path = $this->backupPath($filename);

            if (!$disk->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found',
                ], 404);
            }

            $this->logActivity('Backup downloaded', ['filename' => $filename]);

            return $disk->download($path);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate an uploaded backup file before accepting it
     * POST /api/admin/backups/validate
     */
    public function validate(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|mimes:zip|max:512000',
            ]);

            $file = $request->file('backup_file');
            $tempPath = $file->getRealPath();
            
            $validation = $this->validateBackupFile($tempPath);
            
            return response()->json([
                'success' => $validation['valid'],
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
                'file_size' => $this->formatBytes($validation['file_size'] ?? 0),
                'sql_size' => $this->formatBytes($validation['sql_size'] ?? 0),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload/Import a backup file with validation
     * POST /api/admin/backups/upload
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
            
            // Validate backup file before storing
            $validation = $this->validateBackupFile($tempPath);
            if (!$validation['valid']) {
                $this->logActivity('Backup upload rejected - validation failed', [
                    'filename' => $filename,
                    'errors' => $validation['errors'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من صحة النسخة الاحتياطية',
                    'validation_errors' => $validation['errors'],
                ], 422);
            }
            
            // Store in backup directory
            $path = $file->storeAs($this->backupDir(), $this->sanitizeFilename($filename), $this->backupDiskName());
            
            $this->logActivity('Backup uploaded', [
                'filename' => $filename,
                'path' => $path,
                'size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم رفع النسخة الاحتياطية بنجاح وتم التحقق من صحتها',
                'filename' => $filename,
                'path' => $path,
                'validation' => [
                    'file_size' => $this->formatBytes($validation['file_size']),
                    'sql_size' => $this->formatBytes($validation['sql_size']),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logActivity('Backup upload failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a specific backup
     * DELETE /api/admin/backups/{filename}
     */
    public function delete($filename)
    {
        try {
            $disk = $this->backupDisk();
            $path = $this->backupPath($filename);
            
            if (!$disk->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found',
                ], 404);
            }

            $disk->delete($path);
            
            $this->logActivity('Backup deleted', ['filename' => $filename]);

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore database from backup
     * POST /api/admin/backups/restore
     * 
     * WARNING: This is a dangerous operation!
     * This will:
     * 1. Validate the backup file
     * 2. Create an automatic backup of current state
     * 3. Restore from the selected backup
     */
    public function restore(Request $request)
    {
        $dumpPath = null;
        
        try {
            // Add extra confirmation
            $confirmationCode = $request->input('confirmation_code');
            $filename = $this->sanitizeFilename((string) $request->input('filename'));
            
            if ($confirmationCode !== 'RESTORE-CONFIRM') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid confirmation code',
                ], 400);
            }

            if (!$filename) {
                return response()->json([
                    'success' => false,
                    'message' => 'Filename is required',
                ], 400);
            }

            $disk = $this->backupDisk();
            $relativePath = $this->backupPath($filename);

            if (!$disk->exists($relativePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found',
                ], 404);
            }

            $absolutePath = $disk->path($relativePath);
            
            $this->logActivity('Restore initiated', ['filename' => $filename]);

            // Step 1: Validate the backup file before proceeding
            $validation = $this->validateBackupFile($absolutePath);
            if (!$validation['valid']) {
                $this->logActivity('Restore aborted - backup validation failed', [
                    'filename' => $filename,
                    'errors' => $validation['errors'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من صحة النسخة الاحتياطية',
                    'validation_errors' => $validation['errors'],
                ], 422);
            }

            // Step 2: Create automatic backup of current state before restore
            $preRestoreBackup = $this->createPreRestoreBackup();
            if (!$preRestoreBackup['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل إنشاء نسخة احتياطية من الحالة الحالية قبل الاسترجاع. تم إلغاء العملية للحفاظ على البيانات.',
                    'pre_restore_error' => $preRestoreBackup['message'],
                ], 500);
            }

            // Step 3: Extract and restore
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
                    // Ensure temp directory exists
                    $tempDisk = Storage::disk('local');
                    $tempDisk->makeDirectory('temp');
                    $zip->extractTo($tempDisk->path('temp'));
                    $zip->close();

                    // Restore database - read from configured connection to avoid env() issues when config is cached
                    $connection = config('database.default', 'mysql');
                    $dbConfig = (array) config("database.connections.$connection", []);
                    $database = $dbConfig['database'] ?? env('DB_DATABASE');
                    $username = $dbConfig['username'] ?? env('DB_USERNAME');
                    $password = $dbConfig['password'] ?? env('DB_PASSWORD');
                    $host = $dbConfig['host'] ?? env('DB_HOST');
                    $port = (string) ($dbConfig['port'] ?? env('DB_PORT', '3306'));
                    $charset = $dbConfig['charset'] ?? 'utf8mb4';

                    $dumpPath = $tempDisk->path('temp/' . $dbDumpFile);
                    
                    // Build mysql command (capture stderr with 2>&1 for diagnostics)
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
                        $this->logActivity('Restore completed successfully', ['filename' => $filename]);
                        return response()->json([
                            'success' => true,
                            'message' => 'تم استرجاع قاعدة البيانات بنجاح. تم إنشاء نسخة احتياطية تلقائية من الحالة السابقة.',
                            'pre_restore_backup' => true,
                        ]);
                    } else {
                        $this->logActivity('Restore failed - mysql error', [
                            'filename' => $filename,
                            'return_var' => $returnVar,
                            'output' => implode("\n", $output ?? []),
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'فشل استرجاع قاعدة البيانات. لا تقلق، تم إنشاء نسخة احتياطية من الحالة السابقة.',
                            'error_output' => implode("\n", $output ?? []),
                            'password_provided' => !empty($password),
                            'pre_restore_backup' => true,
                        ], 500);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل فك ضغط ملف النسخة الاحتياطية',
            ], 500);

        } catch (\Exception $e) {
            $this->logActivity('Restore failed - exception', [
                'filename' => $filename ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage(),
            ], 500);
        } finally {
            // Always clean up temp file
            if ($dumpPath && file_exists($dumpPath)) {
                unlink($dumpPath);
            }
        }
    }

    /**
     * Helper: Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
