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
    echo "🔄 Generating certificate with final settings...\n";
    
    // Create DomPDF instance with custom options
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
    $options->set('fontDir', storage_path('fonts/'));
    $options->set('fontCache', storage_path('fonts/'));
    $options->set('tempDir', sys_get_temp_dir());
    $options->set('chroot', realpath(base_path()));
    
    $dompdf = new Dompdf($options);
    
    // Load the view with data
    $html = view('certificates.course_certificate_simple', $certificateData)->render();
    
    // Load HTML to DomPDF
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'landscape');
    
    // Render the PDF
    $dompdf->render();
    
    // Save the PDF
    $outputPath = 'test_final_certificate_output.pdf';
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
    
    // Check if file contains background image
    if (strpos($content, 'certificate_bg') !== false) {
        echo "✅ Background image reference detected\n";
    } else {
        echo "⚠️  Background image may not be properly embedded\n";
    }
    
    echo "\n🎯 Final certificate generation completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error generating certificate: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}