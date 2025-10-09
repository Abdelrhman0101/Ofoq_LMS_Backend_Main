<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\Reviews;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     */
    public function created(Reviews $review): void
    {
        $this->updateCourseStats($review->course_id);
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Reviews $review): void
    {
        $this->updateCourseStats($review->course_id);
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Reviews $review): void
    {
        $this->updateCourseStats($review->course_id);
    }

    protected function updateCourseStats($courseId): void
    {
        if (! $courseId) {
            return;
        }

        $course = Course::find($courseId);
        if (! $course) {
            return;
        }

        $reviewsCount = $course->reviews()->count();
        $avgRating = $course->reviews()->avg('rating') ?? 0;

        $course->reviews_count = $reviewsCount;
        $course->rating = round($avgRating, 1);
        $course->save();
    }
}
