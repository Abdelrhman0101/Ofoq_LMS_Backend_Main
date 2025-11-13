<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\DiplomaCertificate;
use Illuminate\Http\Request;

echo "🔍 Debugging Route Issue\n";
echo "========================\n\n";

try {
    // Get a diploma certificate
    $certificate = DiplomaCertificate::first();
    
    if (!$certificate) {
        throw new Exception("No diploma certificates found");
    }
    
    echo "Found certificate:\n";
    echo "ID: {$certificate->id}\n";
    echo "Verification Token: {$certificate->verification_token}\n\n";
    
    // Test the exact route logic step by step
    echo "Step 1: Testing token lookup...\n";
    $token = $certificate->verification_token;
    echo "Token: {$token}\n";
    
    echo "Step 2: Testing database query...\n";
    $cert = DiplomaCertificate::where('verification_token', $token)
        ->with(['user', 'diploma'])
        ->first();
    
    if ($cert) {
        echo "✅ Certificate found!\n";
        echo "Certificate ID: {$cert->id}\n";
        echo "User: " . ($cert->user ? $cert->user->name : 'No user') . "\n";
        echo "Diploma: " . ($cert->diploma ? $cert->diploma->name : 'No diploma') . "\n";
        
        // Test the rest of the route logic
        echo "Step 3: Testing file path...\n";
        if (!empty($cert->file_path)) {
            echo "File path: {$cert->file_path}\n";
            
            // Test external URL
            if (preg_match('/^https?:\/\//i', $cert->file_path)) {
                echo "✅ External URL detected\n";
            } else {
                echo "Local file path detected\n";
                
                // Test file existence
                $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($cert->file_path);
                echo "File exists: " . ($exists ? 'Yes' : 'No') . "\n";
                
                if ($exists) {
                    $path = \Illuminate\Support\Facades\Storage::disk('public')->path($cert->file_path);
                    echo "Full path: {$path}\n";
                    
                    // Test file response
                    $name = "Diploma_{$cert->diploma->name}_{$cert->user->name}.pdf";
                    echo "Would serve file with name: {$name}\n";
                    
                    echo "✅ Route logic should work!\n";
                } else {
                    echo "❌ File not found\n";
                }
            }
        } else {
            echo "❌ No file path set\n";
        }
        
    } else {
        echo "❌ Certificate not found with token: {$token}\n";
        
        // Debug: Check all certificates
        echo "Available certificates:\n";
        $allCerts = DiplomaCertificate::all();
        foreach ($allCerts as $cert) {
            echo "- ID: {$cert->id}, Token: {$cert->verification_token}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}