<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::limit(5)->get();

echo "Users in database:\n";
echo "Total users: " . User::count() . "\n\n";

foreach ($users as $user) {
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Phone: {$user->phone}\n";
    echo "Role: {$user->role}\n";
    echo "Created: {$user->created_at}\n";
    echo "---\n";
}