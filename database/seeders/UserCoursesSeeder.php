<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
class UserCoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $users = User::take(5)->get();


        $courses = Course::take(5)->get();

        foreach ($users as $user) {
            foreach ($courses as $course) {

                UserCourse::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'status' => 'in_progress',
                        'progress_percentage' => rand(0, 100),
                        'completed_at' => null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );
            }
        }

        $this->command->info('✅ UserCoursesSeeder: تم ربط المستخدمين بالكورسات بنجاح!');
    }

}
