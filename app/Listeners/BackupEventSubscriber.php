<?php

namespace App\Listeners;

use App\Models\BackupHistory;
use Illuminate\Events\Dispatcher;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\BackupHasFailed;
use Illuminate\Support\Facades\Log;

class BackupEventSubscriber
{
    /**
     * Handle successful backup events.
     */
    public function handleBackupWasSuccessful(BackupWasSuccessful $event)
    {
        try {
            // Check if this backup was already logged via Controller (to avoid duplicates if possible)
            // But usually controller logs 'creating' action. Here we log the result.
            // Or we can just log everything from here and remove manual logging from Controller?
            // No, Controller logging has user context. This one is for system/CLI events mainly.

            $backupDestination = $event->backupDestination;
            $newestBackup = $backupDestination->newestBackup();
            
            $filename = $newestBackup ? $newestBackup->path() : 'unknown.zip';
            $size = $newestBackup ? $newestBackup->sizeInBytes() : 0;

            BackupHistory::create([
                'user_id' => request()->user()?->id, // Will be null for CLI/Cron
                'action' => 'backup:run (success)',
                'status' => 'success',
                'filename' => $filename,
                'details' => json_encode([
                    'disk' => $backupDestination->diskName(),
                    'size' => $size,
                    'path' => $filename
                ]),
                'ip_address' => request()->ip() ?? 'CLI',
            ]);

            Log::info("Backup successful: $filename");
        } catch (\Exception $e) {
            Log::error("Failed to log backup success: " . $e->getMessage());
        }
    }

    /**
     * Handle failed backup events.
     */
    public function handleBackupHasFailed(BackupHasFailed $event)
    {
        try {
            BackupHistory::create([
                'user_id' => request()->user()?->id,
                'action' => 'backup:run (failed)',
                'status' => 'failed',
                'filename' => null,
                'details' => json_encode([
                    'error' => $event->exception->getMessage(),
                    'trace' => $event->exception->getTraceAsString()
                ]),
                'ip_address' => request()->ip() ?? 'CLI',
            ]);

            Log::error("Backup failed: " . $event->exception->getMessage());
        } catch (\Exception $e) {
            Log::error("Failed to log backup failure: " . $e->getMessage());
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            BackupWasSuccessful::class,
            [BackupEventSubscriber::class, 'handleBackupWasSuccessful']
        );

        $events->listen(
            BackupHasFailed::class,
            [BackupEventSubscriber::class, 'handleBackupHasFailed']
        );
    }
}
