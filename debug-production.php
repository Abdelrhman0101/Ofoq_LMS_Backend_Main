<?php

/**
 * Production Debug Script
 * This script helps diagnose CORS and 500 errors on the production server
 */

// Set headers for CORS debugging
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

echo json_encode([
    'status' => 'success',
    'message' => 'Debug script is working',
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'http_origin' => $_SERVER['HTTP_ORIGIN'] ?? 'Not set',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
    ],
    'cors_headers' => [
        'Access-Control-Allow-Origin' => 'https://www.ofuq.academy',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN',
        'Access-Control-Allow-Credentials' => 'true',
    ],
    'timestamp' => date('Y-m-d H:i:s'),
]);