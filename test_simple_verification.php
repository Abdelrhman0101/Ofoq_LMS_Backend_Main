<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\DiplomaCertificate;

echo "🔍 Testing Simple Diploma Certificate Verification\n";
echo "==================================================\n\n";

try {
    // Get a diploma certificate
    $certificate = DiplomaCertificate::first();
    
    if (!$certificate) {
        throw new Exception("No diploma certificates found");
    }
    
    echo "Found certificate:\n";
    echo "ID: {$certificate->id}\n";
    echo "Verification Token: {$certificate->verification_token}\n\n";
    
    // Test the exact query from the route
    echo "Testing route query...\n";
    $cert = DiplomaCertificate::where('verification_token', $certificate->verification_token)
        ->with(['user', 'diploma'])
        ->first();
    
    if ($cert) {
        echo "✅ Query successful!\n";
        echo "Certificate ID: {$cert->id}\n";
        echo "User: " . ($cert->user ? $cert->user->name : 'No user') . "\n";
        echo "Diploma: " . ($cert->diploma ? $cert->diploma->name : 'No diploma') . "\n";
        echo "File path: " . ($cert->file_path ?? 'No file path') . "\n";
        
        // Test file existence
        if ($cert->file_path) {
            echo "Testing file existence...\n";
            $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($cert->file_path);
            echo "File exists: " . ($exists ? 'Yes' : 'No') . "\n";
            
            if ($exists) {
                $path = \Illuminate\Support\Facades\Storage::disk('public')->path($cert->file_path);
                echo "Full path: {$path}\n";
            }
        }
        
    } else {
        echo "❌ Query failed - no certificate found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}