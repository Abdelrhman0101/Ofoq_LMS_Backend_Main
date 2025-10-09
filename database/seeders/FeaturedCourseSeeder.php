<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class FeaturedCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = DB::table('courses')
            ->where('is_published', true)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $priority = 1;

        foreach ($courses as $course) {
            DB::table('featured_courses')->insertOrIgnore([
                'course_id'   => $course->id,
                'priority'    => $priority++,
                'featured_at' => now(),
                'is_active'   => true,
            ]);
        }

        echo "✅ Added " . count($courses) . " random featured courses successfully.\n";
    }
}
