<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateCoursePublishStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $courses = Course::all();

        foreach ($courses as $course) {
            $course->update([
                'is_published' => (bool)random_int(0, 1),
            ]);
        }

        echo "✅ Courses updated with random publish status.\n";
    }
}
