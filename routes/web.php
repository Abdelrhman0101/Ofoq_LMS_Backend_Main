<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
// No API routes should be defined here; all API endpoints belong in routes/api.php

// Debug certificate previews (non-auth)
Route::get('/debug/cert/course', function () {
    $imagePath = public_path('storage/certifecate_cover.jpg');
    $backgroundImageBase64 = '';
    if (file_exists($imagePath)) {
        $backgroundImageBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imagePath));
    }

    return view('certificates.course_certificate_simple', [
        'student_name' => 'الاسم: أحمد محمد علي',
        'course_name' => 'مبادئ علوم البيانات',
        'lectures_count' => 24,
        'issued_date' => now()->format('j F Y'),
        'serial_number' => '1234567',
        'backgroundImageBase64' => $backgroundImageBase64,
    ]);
});

Route::get('/debug/cert/diploma', function () {
    $imagePath = public_path('storage/certifecate_cover.jpg');
    $backgroundImageBase64 = '';
    if (file_exists($imagePath)) {
        $backgroundImageBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imagePath));
    }

    return view('certificates.diploma_certificate', [
        'student_name' => 'الاسم: سارة محمود إبراهيم',
        'diploma_name' => 'دبلومة التحليل وإدارة الأعمال',
        'issued_date' => now()->format('j F Y'),
        'serial_number' => '7654321',
        'backgroundImageBase64' => $backgroundImageBase64,
    ]);
});
