<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\DiplomaCertificate;
use Illuminate\Http\Request;

echo "🔍 Testing Route Registration\n";
echo "============================\n\n";

try {
    // Get a diploma certificate
    $certificate = DiplomaCertificate::first();
    
    if (!$certificate) {
        throw new Exception("No diploma certificates found");
    }
    
    echo "Found certificate:\n";
    echo "ID: {$certificate->id}\n";
    echo "Verification Token: {$certificate->verification_token}\n\n";
    
    // Test the route directly by calling the closure
    echo "Step 1: Testing route closure directly...\n";
    $token = $certificate->verification_token;
    
    // This is the exact code from the route
    $cert = DiplomaCertificate::where('verification_token', $token)
        ->with(['user', 'diploma'])
        ->first();

    if (!$cert) {
        echo "❌ Certificate not found with token: {$token}\n";
        return;
    }
    
    echo "✅ Certificate found!\n";
    echo "Certificate ID: {$cert->id}\n";
    echo "User: " . ($cert->user ? $cert->user->name : 'No user') . "\n";
    echo "Diploma: " . ($cert->diploma ? $cert->diploma->name : 'No diploma') . "\n";
    
    // Test the file logic
    echo "Step 2: Testing file logic...\n";
    if (!empty($cert->file_path)) {
        echo "File path: {$cert->file_path}\n";
        
        // Test external URL
        if (preg_match('/^https?:\/\//i', $cert->file_path)) {
            echo "✅ External URL detected - would redirect\n";
        } else {
            echo "Local file path detected\n";
            
            // Test file existence
            $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($cert->file_path);
            echo "File exists: " . ($exists ? 'Yes' : 'No') . "\n";
            
            if ($exists) {
                echo "✅ Would serve file successfully\n";
            } else {
                echo "❌ File not found - would return JSON\n";
            }
        }
    } else {
        echo "❌ No file path set - would return JSON\n";
    }
    
    // Test the actual route
    echo "\nStep 3: Testing actual route dispatch...\n";
    $request = Request::create("/api/diploma-certificate/verify/{$token}", 'GET');
    
    // Get the route
    $route = app('router')->getRoutes()->match($request);
    if ($route) {
        echo "✅ Route matched!\n";
        echo "Route name: " . ($route->getName() ?: 'unnamed') . "\n";
        echo "Route action: " . (is_callable($route->getAction()['uses']) ? 'closure' : 'controller') . "\n";
        
        // Try to call the route action directly
        $action = $route->getAction()['uses'];
        if (is_callable($action)) {
            echo "✅ Action is callable\n";
            
            // Call the closure directly
            $result = $action($token);
            
            if (is_object($result) && method_exists($result, 'getStatusCode')) {
                echo "Response status: " . $result->getStatusCode() . "\n";
                echo "Response type: " . get_class($result) . "\n";
            } else {
                echo "Result: " . print_r($result, true) . "\n";
            }
        } else {
            echo "❌ Action is not callable\n";
        }
    } else {
        echo "❌ Route not matched!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}