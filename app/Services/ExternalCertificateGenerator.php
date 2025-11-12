<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalCertificateGenerator
{
    /**
     * Configuration for external certificate generation service
     */
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'base_url' => config('services.certificate_generator.url', 'http://localhost:3000'),
            'api_key' => config('services.certificate_generator.api_key', ''),
            'timeout' => config('services.certificate_generator.timeout', 60),
        ];
    }

    /**
     * Get pending certificates from the backend
     */
    public function getPendingCertificates(string $type = 'all', int $limit = 50): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Accept' => 'application/json',
            ])->timeout($this->config['timeout'])
            ->get(config('app.url') . '/api/external/certificates/pending', [
                'type' => $type,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                return $response->json('pending_certificates', []);
            }

            Log::error('Failed to get pending certificates', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error getting pending certificates', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Update certificate with generated PDF file path
     */
    public function updateCertificateFilePath(string $type, int $certificateId, string $filePath): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Accept' => 'application/json',
            ])->timeout($this->config['timeout'])
            ->post(config('app.url') . "/api/external/certificates/{$certificateId}/file", [
                'type' => $type,
                'file_path' => $filePath,
            ]);

            if ($response->successful()) {
                Log::info('Certificate file path updated successfully', [
                    'type' => $type,
                    'certificate_id' => $certificateId,
                    'file_path' => $filePath,
                ]);
                return true;
            }

            Log::error('Failed to update certificate file path', [
                'status' => $response->status(),
                'body' => $response->body(),
                'type' => $type,
                'certificate_id' => $certificateId,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error updating certificate file path', [
                'error' => $e->getMessage(),
                'type' => $type,
                'certificate_id' => $certificateId,
            ]);
            return false;
        }
    }

    /**
     * Process certificate generation (example implementation)
     * This would be called by your external tool
     */
    public function processCertificate(array $certificateData): ?string
    {
        // This is an example implementation
        // In a real scenario, this would:
        // 1. Generate the PDF using your preferred library (React, Puppeteer, etc.)
        // 2. Upload the PDF to cloud storage (S3, etc.)
        // 3. Return the file path/URL
        
        Log::info('Processing certificate generation', [
            'certificate_id' => $certificateData['id'] ?? 'unknown',
            'type' => $certificateData['type'] ?? 'unknown',
            'user_name' => $certificateData['user']['name'] ?? 'unknown',
        ]);

        // Simulate processing time
        sleep(2);

        // Return a mock file path
        // In reality, this would be the actual path where you saved the PDF
        return "certificates/{$certificateData['type']}/{$certificateData['id']}_certificate.pdf";
    }

    /**
     * Run the certificate generation process
     * This method can be called by a scheduled task or queue worker
     */
    public function runCertificateGenerationProcess(int $batchSize = 10): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Get pending certificates
        $pendingCertificates = $this->getPendingCertificates('all', $batchSize);

        if (empty($pendingCertificates)) {
            Log::info('No pending certificates found for processing');
            return $results;
        }

        foreach ($pendingCertificates as $certificate) {
            $results['processed']++;

            try {
                // Process the certificate (generate PDF)
                $filePath = $this->processCertificate($certificate);

                if ($filePath) {
                    // Update the certificate with the generated file path
                    $success = $this->updateCertificateFilePath(
                        $certificate['type'],
                        $certificate['id'],
                        $filePath
                    );

                    if ($success) {
                        $results['successful']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to update certificate {$certificate['id']}";
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to generate PDF for certificate {$certificate['id']}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error processing certificate {$certificate['id']}: " . $e->getMessage();
                Log::error('Error processing certificate', [
                    'certificate_id' => $certificate['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Certificate generation process completed', $results);
        return $results;
    }
}