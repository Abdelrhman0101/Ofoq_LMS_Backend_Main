<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;

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
    echo "🔄 Generating certificate with comprehensive settings...\n";
    
    // Generate PDF with the fixed template
    $pdf = Pdf::loadView('certificates.course_certificate_simple', $certificateData);
    
    // Set paper size to A4 landscape
    $pdf->setPaper('a4', 'landscape');
    
    // Set additional options for better Arabic support
    $pdf->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true,
        'isFontSubsettingEnabled' => true,
        'defaultFont' => 'Tajawal',
        'dpi' => 96,
        'defaultMediaType' => 'screen',
        'isPhpEnabled' => true,
        'isJavascriptEnabled' => true,
    ]);
    
    // Save the PDF
    $outputPath = 'test_comprehensive_certificate_output.pdf';
    $pdf->save($outputPath);
    
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
    
    echo "\n🎯 Certificate generation completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error generating certificate: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}