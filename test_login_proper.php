<?php

$baseUrl = 'http://localhost:8000';

// Test login with proper format
$loginData = [
    'login' => 'admin@ofuq.academy',
    'password' => 'admin123'
];

echo "Testing login with login field...\n";
$ch = curl_init($baseUrl . '/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

if ($httpCode == 201) {
    $responseData = json_decode($response, true);
    $token = $responseData['token'];
    $userId = $responseData['user']['id'];
    
    echo "Login successful!\n";
    echo "Token: " . $token . "\n";
    echo "User ID: " . $userId . "\n";
    
    // Now test certificate status endpoint
    echo "\nTesting certificate status endpoint...\n";
    $courseId = 1;
    
    $ch2 = curl_init($baseUrl . '/api/courses/' . $courseId . '/certificate-status');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    
    $response2 = curl_exec($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    echo "Certificate Status HTTP Code: " . $httpCode2 . "\n";
    echo "Certificate Status Response: " . $response2 . "\n";
} else {
    echo "Login failed!\n";
}