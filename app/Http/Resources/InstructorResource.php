<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'email' => $this->email,
            'bio' => $this->bio,
            'image' => $this->image_url, // Use the accessor for proper URL
            'rating' => $this->rating,
            'courses_count' => $this->courses_count,
            'students_count' => $this->students_count,
            'avg_rate' => $this->avg_rate,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}