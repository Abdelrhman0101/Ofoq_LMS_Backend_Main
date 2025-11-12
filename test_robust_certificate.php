<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;

// Test data with Arabic text to verify encoding
$certificateData = [
    'student_name' => 'أحمد محمد علي',
    'course_name' => 'مقدمة في علوم الحاسوب',
    'course_hours' => '40',
    'diploma_name' => 'دبلومة تكنولوجيا المعلومات',
    'completion_date' => '15 ديسمبر 2024',
    'serial_number' => 'CERT-2024-001',
    'verification_token' => 'abc123xyz'
];

try {
    echo "🔄 Testing robust certificate generation...\n";
    
    // Create DomPDF instance with comprehensive options
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('isFontSubsettingEnabled', true);
    $options->set('defaultFont', 'Tajawal');
    $options->set('dpi', 96);
    $options->set('defaultMediaType', 'screen');
    $options->set('enable_php', true);
    $options->set('enable_javascript', true);
    $options->set('enable_css_float', true);
    $options->set('fontDir', storage_path('fonts'));
    $options->set('fontCache', storage_path('fonts'));
    $options->set('tempDir', sys_get_temp_dir());
    $options->set('chroot', realpath(base_path()));
    
    // Create custom HTML with embedded font and proper paths
    $html = '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <style>
        @page { 
            size: A4 landscape; 
            margin: 0; 
        } 
        
        @font-face { 
            font-family: "Tajawal"; 
            src: url("' . str_replace('\\', '/', public_path('fonts/Tajawal-Regular.ttf')) . '") format("truetype"); 
            font-weight: normal; 
            font-style: normal; 
        } 
        
        body { 
            font-family: "Tajawal", sans-serif; 
            margin: 0; 
            padding: 0; 
            text-align: center;
            background: url("' . str_replace('\\', '/', public_path('storage/certificate_bg.svg')) . '") no-repeat center center;
            background-size: cover;
            width: 100%;
            height: 100%;
        } 
        
        .certificate-content {
            padding: 100px 50px;
            position: relative;
            z-index: 10;
        }
        
        .title {
            font-size: 48px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        
        .student-name {
            font-size: 36px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .course-name {
            font-size: 28px;
            font-weight: bold;
            color: #3498db;
            margin: 20px 0;
        }
        
        .details {
            font-size: 20px;
            color: #34495e;
            line-height: 1.8;
            margin: 20px 0;
        }
        
        .footer {
            font-size: 18px;
            color: #7f8c8d;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="certificate-content">
        <div class="title">شهادة إتمام الدورة</div>
        <div class="details">يتم منح هذه الشهادة إلى</div>
        <div class="student-name">' . $certificateData['student_name'] . '</div>
        <div class="details">لإتمامه بنجاح الدورة التدريبية</div>
        <div class="course-name">' . $certificateData['course_name'] . '</div>
        <div class="details">بواقع ' . $certificateData['course_hours'] . ' ساعة تدريبية ضمن دبلومة ' . $certificateData['diploma_name'] . '</div>
        <div class="details">وقد اجتاز الاختبار بنجاح</div>
        <div class="footer">أكمل بتاريخ ' . $certificateData['completion_date'] . '</div>
        <div class="footer">رقم الشهادة: ' . $certificateData['serial_number'] . '</div>
    </div>
</body>
</html>';
    
    // Create DomPDF instance
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    // Save the PDF
    $outputPath = 'test_robust_certificate_output.pdf';
    file_put_contents($outputPath, $dompdf->output());
    
    echo "✅ Certificate generated successfully!\n";
    echo "📄 File saved as: $outputPath\n";
    echo "📊 File size: " . number_format(filesize($outputPath) / 1024, 2) . " KB\n";
    
    // Check if file contains Arabic text
    $content = file_get_contents($outputPath);
    if (strpos($content, 'أحمد') !== false) {
        echo "✅ Arabic text detected in PDF\n";
    } else {
        echo "⚠️  Arabic text may not be properly embedded\n";
    }
    
    // Save HTML for debugging
    file_put_contents('test_robust_certificate_preview.html', $html);
    echo "📄 HTML preview saved as: test_robust_certificate_preview.html\n";
    
    echo "\n🎯 Robust certificate test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error generating certificate: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}