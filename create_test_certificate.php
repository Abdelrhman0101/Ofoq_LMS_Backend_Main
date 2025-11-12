<?php
// Create a test certificate to verify file_path functionality

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ofuq_db', 'root', '');
    
    // Check if we have any users and courses first
    $userCount = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $courseCount = $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    
    echo "Users: $userCount, Courses: $courseCount\n";
    
    if ($userCount == 0 || $courseCount == 0) {
        echo "Need at least one user and one course to create test certificate\n";
        
        // Create a test user if none exists
        if ($userCount == 0) {
            $pdo->exec("INSERT INTO users (name, email, password, created_at, updated_at) VALUES ('Test User', 'test@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW())");
            $userId = $pdo->lastInsertId();
            echo "Created test user with ID: $userId\n";
        } else {
            $userId = $pdo->query('SELECT id FROM users LIMIT 1')->fetchColumn();
        }
        
        // Create a test course if none exists
        if ($courseCount == 0) {
            $pdo->exec("INSERT INTO courses (title, description, created_at, updated_at) VALUES ('Test Course', 'A test course for certificate', NOW(), NOW())");
            $courseId = $pdo->lastInsertId();
            echo "Created test course with ID: $courseId\n";
        } else {
            $courseId = $pdo->query('SELECT id FROM courses LIMIT 1')->fetchColumn();
        }
        
    } else {
        $userId = $pdo->query('SELECT id FROM users LIMIT 1')->fetchColumn();
        $courseId = $pdo->query('SELECT id FROM courses LIMIT 1')->fetchColumn();
    }
    
    // Create user_course relationship first
    $stmt = $pdo->prepare("INSERT INTO user_courses (user_id, course_id, status, progress_percentage, created_at, updated_at) VALUES (?, ?, 'completed', 100, NOW(), NOW())");
    $stmt->execute([$userId, $courseId]);
    $userCourseId = $pdo->lastInsertId();
    echo "Created user_course relationship with ID: $userCourseId\n";
    
    // Create a test certificate
    $verificationToken = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4)); // UUID format
    $filePath = 'certificates/certificate_6_10.pdf'; // Use existing file
    $studentName = 'Test User';
    $serialNumber = 'TEST-' . time();
    $certificateData = json_encode([
        'completion_date' => date('Y-m-d'),
        'course_title' => 'Test Course',
        'user_name' => 'Test User'
    ]);
    
    $stmt = $pdo->prepare("INSERT INTO certificates (user_id, user_course_id, course_id, student_name, verification_token, serial_number, file_path, certificate_data, issued_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())");
    $stmt->execute([$userId, $userCourseId, $courseId, $studentName, $verificationToken, $serialNumber, $filePath, $certificateData]);
    
    $certificateId = $pdo->lastInsertId();
    
    echo "Created test certificate with ID: $certificateId\n";
    echo "Verification Token: $verificationToken\n";
    echo "File Path: $filePath\n";
    
    // Now test the verification endpoint
    echo "\nTesting verification endpoint:\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/certificate/verify/$verificationToken");
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
        echo "\nChecking if file_path is in JSON response:\n";
        if (isset($jsonResponse['certificate']['file_path'])) {
            echo "✓ file_path found in response: " . $jsonResponse['certificate']['file_path'] . "\n";
        } else {
            echo "✗ file_path NOT found in response\n";
            echo "Available keys: " . implode(', ', array_keys($jsonResponse['certificate'])) . "\n";
        }
    }
    
    curl_close($ch);
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}