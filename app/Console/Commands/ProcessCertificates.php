<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalCertificateGenerator;

class ProcessCertificates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:process 
                            {--type=all : Certificate type (course, diploma, or all)}
                            {--limit=10 : Number of certificates to process}
                            {--dry-run : Show what would be processed without actually processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending certificates using external certificate generation service';

    /**
     * Execute the console command.
     */
    public function handle(ExternalCertificateGenerator $generator): int
    {
        $type = $this->option('type');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('Starting certificate processing...');
        $this->info("Type: {$type}");
        $this->info("Limit: {$limit}");
        $this->info("Dry run: " . ($dryRun ? 'Yes' : 'No'));

        if ($dryRun) {
            $this->info('Running in dry-run mode - no certificates will be processed');
            $pendingCertificates = $generator->getPendingCertificates($type, $limit);
            
            if (empty($pendingCertificates)) {
                $this->info('No pending certificates found');
                return Command::SUCCESS;
            }

            $this->info("Found " . count($pendingCertificates) . " pending certificates:");
            
            foreach ($pendingCertificates as $certificate) {
                $this->line("  - {$certificate['type']} certificate #{$certificate['id']} ");
                $this->line("    Serial: {$certificate['serial_number']}");
                $this->line("    User: {$certificate['user']['name']} ({$certificate['user']['email']})");
                
                if ($certificate['type'] === 'course') {
                    $this->line("    Course: {$certificate['course']['title']}");
                } else {
                    $this->line("    Diploma: {$certificate['diploma']['name']}");
                }
                $this->line('');
            }
            
            return Command::SUCCESS;
        }

        // Process certificates
        $this->info('Processing certificates...');
        
        try {
            $results = $generator->runCertificateGenerationProcess($limit);
            
            $this->info('Certificate processing completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Processed', $results['processed']],
                    ['Successful', $results['successful']],
                    ['Failed', $results['failed']],
                ]
            );

            if (!empty($results['errors'])) {
                $this->error('Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            return $results['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error processing certificates: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}