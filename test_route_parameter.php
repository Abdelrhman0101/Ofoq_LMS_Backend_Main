<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\DiplomaCertificate;
use Illuminate\Http\Request;

echo "🔍 Testing Route Parameter Binding\n";
echo "==================================\n\n";

try {
    // Get a diploma certificate
    $certificate = DiplomaCertificate::first();
    
    if (!$certificate) {
        throw new Exception("No diploma certificates found");
    }
    
    echo "Found certificate:\n";
    echo "ID: {$certificate->id}\n";
    echo "Verification Token: {$certificate->verification_token}\n\n";
    
    // Test the actual route dispatch
    echo "Step 1: Testing route dispatch...\n";
    $token = $certificate->verification_token;
    $request = Request::create("/api/diploma-certificate/verify/{$token}", 'GET');
    
    echo "Request URL: /api/diploma-certificate/verify/{$token}\n";
    echo "Request method: GET\n";
    
    // Get the route
    $router = app('router');
    $route = $router->getRoutes()->match($request);
    
    if ($route) {
        echo "✅ Route matched!\n";
        echo "Route URI: " . $route->uri() . "\n";
        echo "Route parameters: " . json_encode($route->parameters()) . "\n";
        
        // Get the route parameters
        $parameters = $route->parameters();
        echo "Token parameter: " . ($parameters['token'] ?? 'NOT FOUND') . "\n";
        
        // Now test the route action manually
        $action = $route->getAction()['uses'];
        echo "Route action type: " . gettype($action) . "\n";
        
        if (is_callable($action)) {
            echo "✅ Action is callable\n";
            
            // Call the closure with the token parameter
            echo "Calling action with token: {$token}\n";
            $result = $action($token);
            
            echo "✅ Action executed successfully!\n";
            echo "Response status: " . (method_exists($result, 'getStatusCode') ? $result->getStatusCode() : 'N/A') . "\n";
            
        } else if (is_string($action)) {
            echo "Action is string: {$action}\n";
            
            // For string actions (controller methods), we need to resolve and call
            if (strpos($action, '@') !== false) {
                list($controller, $method) = explode('@', $action);
                echo "Controller: {$controller}, Method: {$method}\n";
            }
        } else if (is_array($action)) {
            echo "Action is array: " . json_encode($action) . "\n";
        } else {
            echo "Action type: " . gettype($action) . "\n";
            echo "Action: " . print_r($action, true) . "\n";
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