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

if ($httpCode == 201) {
    $responseData = json_decode($response, true);
    $token = $responseData['token'];
    $userId = $responseData['user']['id'];
    
    echo "Login successful!\n";
    echo "Token: " . $token . "\n";
    echo "User ID: " . $userId . "\n";
    
    // Now test certificate generation endpoint
    echo "\nTesting certificate generation endpoint...\n";
    $courseId = 1;
    
    $ch2 = curl_init($baseUrl . '/api/courses/' . $courseId . '/certificate');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    
    $response2 = curl_exec($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    echo "Certificate Generation HTTP Code: " . $httpCode2 . "\n";
    echo "Certificate Generation Response: " . $response2 . "\n";
    
    // Wait a few seconds and check status again
    echo "\nWaiting 5 seconds and checking status again...\n";
    sleep(5);
    
    $ch3 = curl_init($baseUrl . '/api/courses/' . $courseId . '/certificate-status');
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch3, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    
    $response3 = curl_exec($ch3);
    $httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    curl_close($ch3);
    
    echo "Final Status HTTP Code: " . $httpCode3 . "\n";
    echo "Final Status Response: " . $response3 . "\n";
} else {
    echo "Login failed!\n";
}