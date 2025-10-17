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
            CategoriesSeeder::class,
            InstructorsSeeder::class,
            CoursesSeeder::class,
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
