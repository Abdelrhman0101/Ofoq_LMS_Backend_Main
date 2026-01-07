<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'is_published' => (bool) $this->is_published,
            'display_order' => $this->display_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'diplomas_count' => $this->whenCounted('diplomas'),
            'courses_count' => $this->whenCounted('courses'),
            'diplomas' => CategoryResource::collection($this->whenLoaded('diplomas')),
            'courses' => CourseResource::collection($this->whenLoaded('courses')),
        ];
    }
}
