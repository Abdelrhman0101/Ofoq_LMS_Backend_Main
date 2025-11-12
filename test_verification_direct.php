<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request
$request = Illuminate\Http\Request::create('/api/public/verify-certificate', 'POST', [
    'serial_number' => '4W5QFLR'
]);

// Handle the request
$response = $kernel->handle($request);

// Get the response content
$content = $response->getContent();
$statusCode = $response->getStatusCode();

echo "Status Code: " . $statusCode . "\n";
echo "Response: " . $content . "\n";