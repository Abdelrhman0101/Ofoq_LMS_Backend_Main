<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackupController extends Controller
{
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
            Artisan::call('backup:run', ['--only-db' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'output' => Artisan::output(),
            ]);

        } catch (\Exception $e) {
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

            return $disk->download($path);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload/Import a backup file
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
            
            // Store in backup directory
            $path = $file->storeAs($this->backupDir(), $this->sanitizeFilename($filename), $this->backupDiskName());

            return response()->json([
                'success' => true,
                'message' => 'Backup uploaded successfully',
                'filename' => $filename,
                'path' => $path,
            ]);

        } catch (\Exception $e) {
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
     */
    public function restore(Request $request)
    {
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

            // Extract database name from backup
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

                    // Restore database
                    $database = env('DB_DATABASE');
                    $username = env('DB_USERNAME');
                    $password = env('DB_PASSWORD');
                    $host = env('DB_HOST');
                    $dumpPath = $tempDisk->path('temp/' . $dbDumpFile);
                    // Build mysql command (capture stderr with 2>&1 for diagnostics)
                    $port = env('DB_PORT', '3306');
                    $mysql = escapeshellcmd($this->mysqlBinary());
                    // Prefer long options with equals for better Windows compatibility
                    $command = $mysql
                        . ' --host=' . escapeshellarg($host)
                        . ' --port=' . escapeshellarg($port)
                        . ' --user=' . escapeshellarg($username)
                        . ' --password=' . escapeshellarg($password)
                        . ' --default-character-set=utf8mb4 '
                        . escapeshellarg($database)
                        . ' < ' . escapeshellarg($dumpPath)
                        . ' 2>&1';
                    
                    exec($command, $output, $returnVar);

                    // Clean up temp file
                    if (file_exists($dumpPath)) {
                        unlink($dumpPath);
                    }

                    if ($returnVar === 0) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Database restored successfully',
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Database restore failed',
                            'error_output' => implode("\n", $output ?? []),
                        ], 500);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to extract backup file',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
