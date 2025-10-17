<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@ofoq.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@ofuq.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create a test student user
        User::firstOrCreate(
            ['email' => 'student@ofoq.com'],
            [
                'name' => 'Test Student',
                'email' => 'student@ofoq.com',
                'password' => Hash::make('student123'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );
    }
}
