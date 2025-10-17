<?php

/**
 * Laravel Debug Script for Production
 * This script tests Laravel application bootstrap and configuration
 */

// Include Laravel bootstrap
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Bootstrap Laravel application
    $app = require_once __DIR__ . '/bootstrap/app.php';
    
    // Create kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create a fake request to test the application
    $request = Illuminate\Http\Request::create('/api/admin/courses', 'GET');
    $request->headers->set('Origin', 'https://www.ofuq.academy');
    $request->headers->set('Accept', 'application/json');
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Laravel application bootstrapped successfully',
        'app_info' => [
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'app_url' => config('app.url'),
            'sanctum_stateful_domains' => config('sanctum.stateful'),
            'cors_allowed_origins' => config('cors.allowed_origins'),
            'cors_supports_credentials' => config('cors.supports_credentials'),
        ],
        'database_connection' => [
            'driver' => config('database.default'),
            'host' => config('database.connections.mysql.host'),
            'database' => config('database.connections.mysql.database'),
        ],
        'storage_permissions' => [
            'storage_path_exists' => is_dir(storage_path()),
            'storage_writable' => is_writable(storage_path()),
            'logs_path_exists' => is_dir(storage_path('logs')),
            'logs_writable' => is_writable(storage_path('logs')),
            'framework_path_exists' => is_dir(storage_path('framework')),
            'framework_writable' => is_writable(storage_path('framework')),
            'views_path_exists' => is_dir(storage_path('framework/views')),
            'views_writable' => is_writable(storage_path('framework/views')),
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Laravel application failed to bootstrap',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_PRETTY_PRINT);
}