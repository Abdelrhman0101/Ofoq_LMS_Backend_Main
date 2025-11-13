<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "🔍 Debugging Diploma Certificate Verification\n";
echo "============================================\n\n";

try {
    // Get a diploma certificate
    $certificate = DB::table('diploma_certificates')->first();
    
    if (!$certificate) {
        throw new Exception("No diploma certificates found");
    }
    
    echo "Found certificate:\n";
    echo "ID: {$certificate->id}\n";
    echo "User ID: {$certificate->user_id}\n";
    echo "Diploma ID: {$certificate->diploma_id}\n";
    echo "Serial Number: {$certificate->serial_number}\n";
    echo "Verification Token: {$certificate->verification_token}\n";
    echo "File Path: {$certificate->file_path}\n\n";
    
    // Test step by step
    echo "Step 1: Testing model query...\n";
    $certModel = \App\Models\DiplomaCertificate::where('verification_token', $certificate->verification_token)
        ->with(['user', 'diploma'])
        ->first();
    
    if ($certModel) {
        echo "✅ Model query successful!\n";
        echo "User: " . ($certModel->user ? $certModel->user->name : 'No user') . "\n";
        echo "Diploma: " . ($certModel->diploma ? $certModel->diploma->name : 'No diploma') . "\n";
    } else {
        echo "❌ Model query failed!\n";
    }
    
    echo "\nStep 2: Testing file existence...\n";
    $filePath = $certificate->file_path;
    if (!empty($filePath)) {
        echo "File path: {$filePath}\n";
        
        if (preg_match('/^https?:\/\//i', $filePath)) {
            echo "✅ External URL detected\n";
        } else {
            echo "Local file path detected\n";
            $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($filePath);
            echo $exists ? "✅ File exists in storage\n" : "❌ File not found in storage\n";
            
            if ($exists) {
                $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($filePath);
                echo "Full path: {$fullPath}\n";
                echo "Real path: " . realpath($fullPath) . "\n";
            }
        }
    } else {
        echo "❌ No file path set\n";
    }
    
    echo "\nStep 3: Testing JSON data decoding...\n";
    $data = json_decode($certificate->certificate_data, true) ?? [];
    echo "Certificate data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    echo "\nStep 4: Testing response generation...\n";
    
    // Simulate the route logic step by step
    if ($certModel) {
        // Test file serving
        if (!empty($certModel->file_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($certModel->file_path)) {
            echo "✅ File exists, testing file response...\n";
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($certModel->file_path);
            $name = "Diploma_{$certModel->diploma->name}_{$certModel->user->name}.pdf";
            echo "Would serve file: {$path}\n";
            echo "With name: {$name}\n";
        } else {
            echo "❌ File not found, would return JSON response\n";
            $responseData = [
                'valid' => true,
                'certificate' => [
                    'id' => $certModel->id,
                    'user_name' => $certModel->user->name,
                    'diploma_name' => $certModel->diploma->name,
                    'issued_at' => optional($certModel->issued_at)->format('F d, Y'),
                    'completion_date' => $data['completion_date'] ?? null,
                    'verification_token' => $certModel->verification_token,
                    'serial_number' => $certModel->serial_number,
                    'file_path' => $certModel->file_path,
                ],
            ];
            echo "JSON response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}