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
    echo "🔄 Testing final Arabic certificate generation...\n";
    
    // Create comprehensive options
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
    
    // Create HTML with embedded styles and simplified approach
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
        
        body { 
            font-family: "DejaVu Sans", "Arial Unicode MS", sans-serif; 
            margin: 0; 
            padding: 40px;
            text-align: center;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            min-height: 100%;
            box-sizing: border-box;
        } 
        
        .certificate-container {
            border: 3px solid #d4af37;
            border-radius: 15px;
            padding: 60px 40px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .certificate-container::before {
            content: "";
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 1px solid #d4af37;
            border-radius: 10px;
            pointer-events: none;
        }
        
        .title {
            font-size: 42px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .subtitle {
            font-size: 24px;
            color: #34495e;
            margin-bottom: 20px;
        }
        
        .student-name {
            font-size: 36px;
            font-weight: bold;
            color: #e74c3c;
            margin: 30px 0;
            padding: 15px;
            background: rgba(231, 76, 60, 0.05);
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
            border-right: 4px solid #e74c3c;
        }
        
        .course-name {
            font-size: 28px;
            font-weight: bold;
            color: #3498db;
            margin: 25px 0;
            padding: 12px;
            background: rgba(52, 152, 219, 0.05);
            border-radius: 8px;
            border-left: 4px solid #3498db;
            border-right: 4px solid #3498db;
        }
        
        .details {
            font-size: 20px;
            color: #34495e;
            line-height: 1.8;
            margin: 20px 0;
            text-align: center;
        }
        
        .footer {
            font-size: 18px;
            color: #7f8c8d;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #bdc3c7;
        }
        
        .certificate-number {
            font-size: 16px;
            color: #95a5a6;
            margin-top: 15px;
            font-style: italic;
        }
        
        .decorative-line {
            width: 200px;
            height: 2px;
            background: linear-gradient(to right, transparent, #d4af37, transparent);
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="title">شهادة إتمام الدورة التدريبية</div>
        <div class="decorative-line"></div>
        <div class="subtitle">يتم منح هذه الشهادة إلى</div>
        <div class="student-name">' . $certificateData['student_name'] . '</div>
        <div class="subtitle">لإتمامه بنجاح الدورة التدريبية</div>
        <div class="course-name">' . $certificateData['course_name'] . '</div>
        <div class="details">
            بواقع <strong>' . $certificateData['course_hours'] . ' ساعة تدريبية</strong> ضمن دبلومة <strong>' . $certificateData['diploma_name'] . '</strong>
            <br><br>
            وقد اجتاز الاختبار بنجاح
            <br><br>
            وهذه شهادة منا بذلك سائلين المولى عز وجل له دوام التوفيق والسداد
        </div>
        <div class="decorative-line"></div>
        <div class="footer">
            <strong>تاريخ الإكمال:</strong> ' . $certificateData['completion_date'] . '
        </div>
        <div class="certificate-number">
            رقم الشهادة: ' . $certificateData['serial_number'] . '
        </div>
    </div>
</body>
</html>';
    
    // Create DomPDF instance
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    // Save the PDF
    $outputPath = 'test_final_arabic_certificate_output.pdf';
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
    file_put_contents('test_final_arabic_certificate_preview.html', $html);
    echo "📄 HTML preview saved as: test_final_arabic_certificate_preview.html\n";
    
    echo "\n🎯 Final Arabic certificate test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error generating certificate: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}