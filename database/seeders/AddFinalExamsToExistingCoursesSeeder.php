<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddFinalExamsToExistingCoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $courses = Course::all();
        $count = 0;

        foreach ($courses as $course) {
            if (!$course->finalExam) {
                Quiz::create([
                    'title' => 'Final Exam - ' . $course->title,
                    'is_final' => true,
                    'quizzable_type' => Course::class,
                    'quizzable_id' => $course->id,
                ]);

                $count++;
            }
        }

        $this->command->info("✅ Added {$count} missing final exams for existing courses.");
    }

}
