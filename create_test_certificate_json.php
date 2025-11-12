<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ofuq_db', 'root', '');
    
    // Get second user and course to avoid unique constraint
    $user = $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1 OFFSET 1')->fetch(PDO::FETCH_ASSOC);
    $course = $pdo->query('SELECT id FROM courses ORDER BY id ASC LIMIT 1 OFFSET 1')->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$course) {
        echo "No users or courses found\n";
        exit;
    }
    
    $userId = $user['id'];
    $courseId = $course['id'];
    
    // Create user_course relationship first
    $stmt = $pdo->prepare("INSERT INTO user_courses (user_id, course_id, status, progress_percentage, created_at, updated_at) VALUES (?, ?, 'completed', 100, NOW(), NOW())");
    $stmt->execute([$userId, $courseId]);
    $userCourseId = $pdo->lastInsertId();
    echo "Created user_course relationship with ID: $userCourseId\n";
    
    // Create a test certificate with NON-EXISTENT file path
    $verificationToken = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4)); // UUID format
    $filePath = 'certificates/non_existent_file.pdf'; // This file doesn't exist
    $studentName = 'Test User JSON';
    $serialNumber = 'TEST-JSON-' . time();
    $certificateData = json_encode([
        'completion_date' => date('Y-m-d'),
        'course_title' => 'Test Course',
        'user_name' => 'Test User JSON'
    ]);
    
    $stmt = $pdo->prepare("INSERT INTO certificates (user_id, user_course_id, course_id, student_name, verification_token, serial_number, file_path, certificate_data, issued_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())");
    $stmt->execute([$userId, $userCourseId, $courseId, $studentName, $verificationToken, $serialNumber, $filePath, $certificateData]);
    
    $certificateId = $pdo->lastInsertId();
    echo "Test certificate created with ID: $certificateId\n";
    echo "Verification token: $verificationToken\n";
    echo "File path: $filePath (this file does not exist)\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}