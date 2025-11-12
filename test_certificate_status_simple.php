<?php

// First, login to get token
echo "=== LOGIN TO GET TOKEN ===\n";
$data = json_encode([
    "email" => "admin@ofoq.com",
    "password" => "admin123"
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/login");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Status: " . $loginHttpCode . "\n";
echo "Login Response: " . $loginResponse . "\n\n";

if ($loginHttpCode !== 200) {
    die("Login failed!\n");
}

$loginData = json_decode($loginResponse, true);
$token = $loginData['token'];
$userId = $loginData['user']['id'];

echo "Token: " . $token . "\n";
echo "User ID: " . $userId . "\n\n";

// Test certificate status endpoint
echo "=== TEST CERTIFICATE STATUS ENDPOINT ===\n";
$courseId = 1; // Use course ID 1 for testing

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/courses/{$courseId}/certificate-status");
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $token,
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$statusResponse = curl_exec($ch);
$statusHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Certificate Status Response:\n";
echo "HTTP Status: " . $statusHttpCode . "\n";
echo "Response: " . $statusResponse . "\n\n";

if ($statusHttpCode === 200) {
    $statusData = json_decode($statusResponse, true);
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