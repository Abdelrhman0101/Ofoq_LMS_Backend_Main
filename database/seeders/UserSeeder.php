<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\UserCourse;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
            ['name' => 'Charlie', 'email' => 'charlie@example.com'],
            ['name' => 'David', 'email' => 'david@example.com'],
            ['name' => 'Eve', 'email' => 'eve@example.com'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name'     => $userData['name'],
                    'password' => Hash::make('password123'),
                ]
            );

            $courses = Course::inRandomOrder()->take(3)->get();
            foreach ($courses as $course) {
                UserCourse::firstOrCreate([
                    'user_id'   => $user->id,
                    'course_id' => $course->id,
                ]);
            }
        }
    }
}
