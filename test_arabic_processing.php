<?php

require_once __DIR__ . '/vendor/autoload.php';

// Manually require the Ar-PHP library since autoload isn't working
require_once __DIR__ . '/vendor/khaled.alshamaa/ar-php/src/Arabic.php';

use ArPHP\I18N\Arabic;

// Test Arabic text processing
echo "Testing Ar-PHP Arabic Text Processing\n";
echo "=====================================\n\n";

// Initialize Arabic object
$obj = new Arabic('Glyphs');

// Test Arabic text
$test_texts = [
    'شهادة إتمام الدورة التدريبية',
    'قد حضر المقرر الدراسي',
    'ضمن دبلومة البرمجة',
    'وقد اجتاز الاختبار بنجاح',
    'وهذه شهادة منا بذلك سائلين المولي عز وجل له دوام التوفيق والسداد',
    'أكمل بتاريخ',
    'شهادة',
    'أحمد محمد علي',
    'دورة البرمجة بلغة PHP',
    '24 ساعات تدريبية'
];

echo "Original Arabic Text => Processed Arabic Text\n";
echo "---------------------------------------------\n";

foreach ($test_texts as $text) {
    $processed = $obj->utf8Glyphs($text);
    echo "$text => $processed\n";
}

echo "\nTest completed successfully!\n";