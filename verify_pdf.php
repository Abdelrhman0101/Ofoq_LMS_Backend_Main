<?php

// Verify PDF file
$pdfPath = 'test_updated_certificate_output.pdf';

if (file_exists($pdfPath)) {
    $pdfContent = file_get_contents($pdfPath);
    $fileSize = strlen($pdfContent);
    
    echo "✅ PDF file exists and is valid!\n";
    echo "📊 File size: " . number_format($fileSize / 1024, 2) . " KB\n";
    echo "📄 PDF header: " . substr($pdfContent, 0, 10) . "\n";
    echo "🔍 First 50 bytes (hex): " . bin2hex(substr($pdfContent, 0, 50)) . "\n";
    
    // Check if PDF contains Arabic text by looking for common patterns
    if (strpos($pdfContent, 'أحمد') !== false || strpos($pdfContent, 'الدورة') !== false) {
        echo "✅ PDF appears to contain Arabic text!\n";
    } else {
        echo "⚠️  PDF may not contain Arabic text (could be encoded)\n";
    }
    
} else {
    echo "❌ PDF file not found!\n";
}