<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\UserCategoryEnrollment;
use App\Models\UserCourse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AttachCourseToActiveDiplomaEnrollmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $courseId;

    public function __construct(int $courseId)
    {
        $this->courseId = $courseId;
    }

    public function handle(): void
    {
        $course = Course::find($this->courseId);
        if (! $course || ! $course->category_id || ! $course->is_published) {
            return;
        }

        $categoryId = (int) $course->category_id;
        $courseId = (int) $course->id;
        $createdCount = 0;

        UserCategoryEnrollment::query()
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunk(200, function ($enrollments) use ($courseId, $course, &$createdCount) {
                foreach ($enrollments as $enrollment) {
                    $created = UserCourse::firstOrCreate(
                        [
                            'user_id' => $enrollment->user_id,
                            'course_id' => $courseId,
                        ],
                        [
                            'status' => 'in_progress',
                            'progress_percentage' => 0,
                        ]
                    );

                    if ($created->wasRecentlyCreated) {
                        $createdCount++;
                    }
                }
            });

        if ($createdCount > 0) {
            $course->students_count = ($course->students_count ?? 0) + $createdCount;
            $course->save();
        }
    }
}