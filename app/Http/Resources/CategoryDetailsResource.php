<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CategoryDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $mediaUrl = function (?string $path): ?string {
            if (!$path) return null;
            return Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : asset('storage/' . ltrim($path, '/'));
        };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_published' => (bool) $this->is_published,
            'is_free' => (bool) $this->is_free,
            'price' => $this->price,
            'cover_image' => $this->cover_image,
            'cover_image_url' => $this->cover_image_url ?? $mediaUrl($this->cover_image),
            'display_order' => $this->display_order,
            'section' => $this->whenLoaded('section', function () {
                return [
                    'id' => $this->section->id,
                    'name' => $this->section->name,
                ];
            }),

            // إحصاءات
            'courses_count' => $this->courses_count ?? $this->whenLoaded('courses', fn() => $this->courses->count()),

            // قائمة الكورسات (محملة مسبقًا في الكنترولر)
            'courses' => CourseResource::collection($this->whenLoaded('courses')),
        ];
    }
}