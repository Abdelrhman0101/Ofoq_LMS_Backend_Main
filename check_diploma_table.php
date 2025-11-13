<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "🔍 Checking Diploma Certificates Table\n";
echo "====================================\n\n";

try {
    // Check if table exists
    if (!Schema::hasTable('diploma_certificates')) {
        throw new Exception("❌ Diploma certificates table not found");
    }
    echo "✅ Table exists\n";
    
    // Get table columns
    $columns = Schema::getColumnListing('diploma_certificates');
    echo "📋 Table columns:\n";
    foreach ($columns as $column) {
        echo "   - {$column}\n";
    }
    
    echo "\n📊 Table data:\n";
    $certificates = DB::table('diploma_certificates')->limit(5)->get();
    echo "Found " . $certificates->count() . " certificates\n";
    
    foreach ($certificates as $cert) {
        echo "\n   Certificate ID: {$cert->id}\n";
        echo "   User ID: {$cert->user_id}\n";
        echo "   Diploma ID: {$cert->diploma_id}\n";
        echo "   Status: {$cert->status}\n";
        echo "   Serial: {$cert->serial_number}\n";
        echo "   File Path: {$cert->file_path}\n";
        
        // Check for missing required fields
        $requiredFields = ['user_category_enrollment_id'];
        foreach ($requiredFields as $field) {
            if (!isset($cert->$field) || $cert->$field === null) {
                echo "   ⚠️  Missing field: {$field}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}