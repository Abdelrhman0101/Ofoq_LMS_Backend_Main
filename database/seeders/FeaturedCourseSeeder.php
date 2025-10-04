<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeaturedCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get top 5 courses by number of subscriptions from user_courses
        $topCourses = DB::table('user_courses')
            ->select('course_id', DB::raw('COUNT(user_id) as subscriptions'))
            ->groupBy('course_id')
            ->orderByDesc('subscriptions')
            ->limit(5)
            ->get();

        foreach ($topCourses as $course) {
            DB::table('featured_courses')->insertOrIgnore([
                'course_id' => $course->course_id,
                'is_active' => true,
            ]);
        }
    }
}
