<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\DiplomaCertificate;
use Illuminate\Http\Request;

echo "🔍 Testing Route Debug\n";
echo "======================\n\n";

try {
    // Get a diploma certificate
    $certificate = DiplomaCertificate::first();
    
    if (!$certificate) {
        throw new Exception("No diploma certificates found");
    }
    
    echo "Found certificate:\n";
    echo "ID: {$certificate->id}\n";
    echo "Verification Token: {$certificate->verification_token}\n\n";
    
    // Create a request to test the route
    $request = Request::create("/api/diploma-certificate/verify/{$certificate->verification_token}", 'GET');
    
    echo "Testing route: /api/diploma-certificate/verify/{$certificate->verification_token}\n\n";
    
    // Get the route
    $router = app('router');
    $route = $router->getRoutes()->match($request);
    
    if ($route) {
        echo "✅ Route found!\n";
        echo "Route name: " . ($route->getName() ?? 'No name') . "\n";
        echo "Route action: " . json_encode($route->getAction()) . "\n\n";
        
        // Try to execute the route
        echo "Testing route execution...\n";
        
        // Manually execute the route logic
        $token = $certificate->verification_token;
        
        // This is the exact code from the route
        $cert = DiplomaCertificate::where('verification_token', $token)
            ->with(['user', 'diploma'])
            ->first();

        if (!$cert) {
            echo "❌ Certificate not found\n";
            return;
        }

        echo "✅ Certificate found!\n";
        echo "User: " . ($cert->user ? $cert->user->name : 'No user') . "\n";
        echo "Diploma: " . ($cert->diploma ? $cert->diploma->name : 'No diploma') . "\n";
        
        // Test file path
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
                }
            }
        } else {
            echo "No file path set\n";
        }
        
    } else {
        echo "❌ Route not found!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}