<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray(Request $request): array
    //     {
    //         return parent::toArray($request);
    //     }

    public function toArray($request)
    {
        $isAdmin = Auth::user()?->role === 'admin';
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'discount_ends_at' => $this->discount_ends_at,
            'is_free' => $this->is_free,
            'rating' => $this->rating,
            'average_rating' => $this->when(isset($this->reviews_avg_rating), round($this->reviews_avg_rating, 1)),
            'cover_image' => $this->cover_image,
            'cover_image_url' => $this->cover_image_url,
            'is_published' => $isAdmin?$this->is_published:null,


            // instructor data
            // 'instructorData' => [
            //     'id' => $this->instructor_id,
            //     'name' => $this->name_instructor,
            //     'title' => $this->title_instructor,
            //     'bio' => $this->bio_instructor,
            //     'image' => $this->image_instructor,
            // ],
            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id' => $this->instructor->id,
                    'name' => $this->instructor->name,
                    'title' => $this->instructor->title,
                    'bio' => $this->instructor->bio,
                    'image' => $this->instructor->image
                        ? asset('storage/' . $this->instructor->image)
                        : null,

                    'rating' => $this->instructor->avg_rate,
                ];
            }),


            // counts
            'chapters_count' => $this->chapters_count ?? $this->whenLoaded('chapters', $this->chapters->count()),
            'lessons_count' => $this->lessons_count ?? null,
            'reviews_count' => $this->reviews_count ?? null,

            // relations
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
