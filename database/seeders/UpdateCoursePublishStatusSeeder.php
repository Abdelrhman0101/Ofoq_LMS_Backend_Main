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
        $updated = Course::withoutGlobalScope('published')->update(['is_published' => 1]);
        $this->command->info("✅ All {$updated} courses have been published successfully!");
    }
}
