<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\User;
use App\Models\Reviews;

class ReviewsSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            // Get users enrolled in the course
            $enrolledUserIds = DB::table('user_courses')
                ->where('course_id', $course->id)
                ->pluck('user_id')
                ->toArray();

            $users = User::whereIn('id', $enrolledUserIds)->get();

            // Ensure at least 3 reviews per course
            if ($users->count() < 3) {
                $additionalUsers = User::inRandomOrder()
                    ->whereNotIn('id', $enrolledUserIds)
                    ->take(3 - $users->count())
                    ->get();
                $users = $users->merge($additionalUsers);
            }

            foreach ($users as $user) {
                Reviews::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'user_id'   => $user->id,
                    ],
                    [
                        'rating'  => rand(3, 5),
                        'comment' => "Review for {$course->title} by {$user->name}: Great content and well-structured!",
                    ]
                );
            }
        }
    }
}