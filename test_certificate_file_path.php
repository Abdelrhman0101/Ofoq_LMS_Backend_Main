<?php
// Comprehensive test for certificate file_path functionality

echo "=== Certificate File Path Test ===\n\n";

// Test 1: Check if we can get any certificate from database
echo "1. Testing database connection and getting first certificate:\n";
try {
    // We'll use a simple approach to avoid Laravel bootstrap issues
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ofuq_db', 'root', '');
    $stmt = $pdo->query("SELECT id, verification_token, file_path FROM certificates LIMIT 1");
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($certificate) {
        echo "Found certificate:\n";
        echo "  ID: " . $certificate['id'] . "\n";
        echo "  Token: " . $certificate['verification_token'] . "\n";
        echo "  File Path: " . $certificate['file_path'] . "\n";

        
        // Test 2: Test verification endpoint with real token
        echo "\n2. Testing verification endpoint with real token:\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/certificate/verify/' . $certificate['verification_token']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        
        echo "HTTP Code: " . $httpCode . "\n";
        echo "Response: " . $body . "\n";
        
        // Parse JSON response to check if file_path is included
        $jsonResponse = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($jsonResponse['certificate'])) {
            echo "\n3. Checking if file_path is in JSON response:\n";
            if (isset($jsonResponse['certificate']['file_path'])) {
                echo "✓ file_path found in response: " . $jsonResponse['certificate']['file_path'] . "\n";
            } else {
                echo "✗ file_path NOT found in response\n";
                echo "Available keys: " . implode(', ', array_keys($jsonResponse['certificate'])) . "\n";
            }
        }
        
        curl_close($ch);
        
    } else {
        echo "No certificates found in database\n";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    echo "Will test with dummy token instead\n";
    
    // Fallback test with dummy token
    echo "\n2. Testing verification endpoint with dummy token:\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/certificate/verify/dummy-token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    
    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $body . "\n";
    curl_close($ch);
}

echo "\n=== Test Completed ===\n";