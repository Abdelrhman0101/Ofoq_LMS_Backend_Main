<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CourseSeeder::class,
            UpdateCoursePublishStatusSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            ReviewsSeeder::class,
            FeaturedCourseSeeder::class,
            UserCoursesSeeder::class,
            AddFinalExamsToExistingCoursesSeeder::class
        ]);
    }
}
