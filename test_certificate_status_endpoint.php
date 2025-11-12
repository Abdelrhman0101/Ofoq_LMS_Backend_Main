<?php

function makeRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    $url = 'http://localhost:8000' . $endpoint;
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => $response
    ];
}

// First, login to get token
echo "=== LOGIN TO GET TOKEN ===\n";
$loginData = json_encode([
    "email" => "admin@ofoq.com",
    "password" => "admin123"
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/login");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponseBody = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Status: " . $loginHttpCode . "\n";
echo "Login Response: " . $loginResponseBody . "\n\n";

if ($loginHttpCode !== 200) {
    die("Login failed!\n");
}

$loginData = json_decode($loginResponseBody, true);
$token = $loginData['token'];
$userId = $loginData['user']['id'];

echo "Token: " . $token . "\n";
echo "User ID: " . $userId . "\n\n";

// Test certificate status endpoint
echo "=== TEST CERTIFICATE STATUS ENDPOINT ===\n";
$courseId = 1; // Use course ID 1 for testing

$statusResponse = makeRequest("/api/courses/{$courseId}/certificate-status", 'GET', null, $token);

echo "Certificate Status Response:\n";
echo "HTTP Status: " . $statusResponse['status'] . "\n";
echo "Response: " . $statusResponse['body'] . "\n\n";

if ($statusResponse['status'] === 200) {
    $statusData = json_decode($statusResponse['body'], true);
    echo "Certificate Details:\n";
    echo "- ID: " . ($statusData['id'] ?? 'N/A') . "\n";
    echo "- Status: " . ($statusData['status'] ?? 'N/A') . "\n";
    echo "- File Path: " . ($statusData['file_path'] ?? 'N/A') . "\n";
    echo "- Serial Number: " . ($statusData['serial_number'] ?? 'N/A') . "\n";
    echo "- Created At: " . ($statusData['created_at'] ?? 'N/A') . "\n";
    echo "- Updated At: " . ($statusData['updated_at'] ?? 'N/A') . "\n";
} else {
    echo "Certificate status check failed!\n";
}