<?php

// Test script to check certificate status endpoint

$baseUrl = 'http://localhost:8000';
$courseId = 1;

// First, let's test login to get token
$loginData = [
    'login' => 'admin@ofoq.com',
    'password' => 'admin123'
];

echo "Testing login...\n";
$loginResponse = makeRequest('/api/login', 'POST', $loginData, null, true);
echo "Login response: " . $loginResponse . "\n\n";

$loginArray = json_decode($loginResponse, true);
$token = $loginArray['token'] ?? null;

if (!$token) {
    echo "Failed to get login token\n";
    exit(1);
}

echo "Got token: " . substr($token, 0, 20) . "...\n\n";

// Now test the certificate status endpoint
echo "Testing certificate status endpoint...\n";
$statusResponse = makeRequest("/api/courses/{$courseId}/certificate-status", 'GET', null, $token);
echo "Certificate status response: " . $statusResponse . "\n\n";

$statusArray = json_decode($statusResponse, true);

if (isset($statusArray['status'])) {
    echo "Certificate Status: " . $statusArray['status'] . "\n";
    echo "File Path: " . ($statusArray['file_path'] ?? 'null') . "\n";
    echo "Serial Number: " . ($statusArray['serial_number'] ?? 'null') . "\n";
    
    if (isset($statusArray['file_path'])) {
        echo "\n✅ File path is being returned correctly!\n";
        echo "Full URL would be: " . $baseUrl . '/storage/' . $statusArray['file_path'] . "\n";
    } else {
        echo "\n❌ File path is missing from response\n";
    }
} else {
    echo "Error: " . ($statusArray['message'] ?? 'Unknown error') . "\n";
}

function makeRequest($endpoint, $method = 'GET', $data = null, $token = null, $forceJson = false) {
    $baseUrl = 'http://localhost:8000';
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    if ($data && $method !== 'GET') {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($forceJson && $method === 'POST') {
        $headers[] = 'Content-Type: application/json';
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        return json_encode(['error' => 'HTTP ' . $httpCode, 'message' => $response]);
    }
    
    return $response;
}