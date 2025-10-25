<?php

namespace App\Observers;

use App\Jobs\AttachCourseToActiveDiplomaEnrollmentsJob;
use App\Models\Course;

class CourseObserver
{
    /**
     * When a course is created, attach it to active diploma enrollments (if published).
     */
    public function created(Course $course): void
    {
        if ($course->is_published && $course->category_id) {
            AttachCourseToActiveDiplomaEnrollmentsJob::dispatch((int) $course->id);
        }
    }

    /**
     * When a course is updated, trigger attachment if it becomes published or category changes.
     */
    public function updated(Course $course): void
    {
        $publishedTurnedOn = $course->wasChanged('is_published') && $course->is_published;
        $categoryChanged = $course->wasChanged('category_id');

        if (($publishedTurnedOn || $categoryChanged) && $course->is_published && $course->category_id) {
            AttachCourseToActiveDiplomaEnrollmentsJob::dispatch((int) $course->id);
        }
    }
}