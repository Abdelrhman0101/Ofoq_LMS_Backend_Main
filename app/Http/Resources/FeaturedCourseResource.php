<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeaturedCourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray(Request $request): array
    // {
    //     return parent::toArray($request);
    // }
    public function toArray($request)
    {
        $course = $this->course;

        return [
            'priority' => $this->priority,
            'featured_at' => $this->featured_at,
            'course' => $course ? [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'duration' => $course->duration,
                'price' => $course->price,
                'discount_price' => $course->discount_price,
                'discount_ends_at' => $course->discount_ends_at,
                'is_free' => $course->is_free,
                'rating' => $course->rating,
                'chapters_count' => $course->chapters_count ?? 0,
                'reviews_count' => $course->reviews_count ?? 0,
                'average_rating' => isset($course->reviews_avg_rating)
                    ? round($course->reviews_avg_rating, 1)
                    : null,

                'instructor' => $course->instructor ? [
                    'id' => $course->instructor->id,
                    'name' => $course->instructor->name,
                    'title' => $course->instructor->title,
                    'bio' => $course->instructor->bio,
                    'image' => $course->instructor->image
                        ? asset('storage/' . $course->instructor->image)
                        : null,
                    'rating' => $course->instructor->avg_rate,
                ] : null,
            ] : null,
        ];

        // // relations
        // 'chapters' => ChapterResource::collection($course->whenLoaded('chapters')),
        // 'reviews' => ReviewResource::collection($course->whenLoaded('reviews')),
    }
}
