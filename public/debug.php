<?php

/**
 * Simple Production Debug Script
 * Place this in public/ folder to test server configuration
 */

// Set CORS headers
header('Access-Control-Allow-Origin: https://www.ofuq.academy');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$debug_info = [
    'status' => 'success',
    'message' => 'Debug script is working',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'http_origin' => $_SERVER['HTTP_ORIGIN'] ?? 'Not set',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
    ],
    'paths_check' => [
        'current_dir' => __DIR__,
        'parent_dir' => dirname(__DIR__),
        'storage_path' => dirname(__DIR__) . '/storage',
        'storage_exists' => is_dir(dirname(__DIR__) . '/storage'),
        'storage_writable' => is_writable(dirname(__DIR__) . '/storage'),
        'logs_path' => dirname(__DIR__) . '/storage/logs',
        'logs_exists' => is_dir(dirname(__DIR__) . '/storage/logs'),
        'logs_writable' => is_writable(dirname(__DIR__) . '/storage/logs'),
        'framework_path' => dirname(__DIR__) . '/storage/framework',
        'framework_exists' => is_dir(dirname(__DIR__) . '/storage/framework'),
        'framework_writable' => is_writable(dirname(__DIR__) . '/storage/framework'),
        'views_path' => dirname(__DIR__) . '/storage/framework/views',
        'views_exists' => is_dir(dirname(__DIR__) . '/storage/framework/views'),
        'views_writable' => is_writable(dirname(__DIR__) . '/storage/framework/views'),
    ],
];

// Try to test Laravel bootstrap
try {
    if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        $debug_info['laravel_autoload'] = 'success';
        
        if (file_exists(dirname(__DIR__) . '/bootstrap/app.php')) {
            $app = require_once dirname(__DIR__) . '/bootstrap/app.php';
            $debug_info['laravel_bootstrap'] = 'success';
            
            // Try to get some config values
            try {
                $debug_info['config_check'] = [
                    'app_env' => $_ENV['APP_ENV'] ?? 'unknown',
                    'app_debug' => $_ENV['APP_DEBUG'] ?? 'unknown',
                    'sanctum_domains' => $_ENV['SANCTUM_STATEFUL_DOMAINS'] ?? 'not set',
                ];
            } catch (Exception $e) {
                $debug_info['config_error'] = $e->getMessage();
            }
        } else {
            $debug_info['laravel_bootstrap'] = 'bootstrap/app.php not found';
        }
    } else {
        $debug_info['laravel_autoload'] = 'vendor/autoload.php not found';
    }
} catch (Exception $e) {
    $debug_info['laravel_error'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);