<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $isAdmin = Auth::user()?->role === 'admin';

        // Helper صغير يضمن URL مطلق للصور سواء كانت مخزنة كمسار نسبي (inst/...) أو كرابط كامل
        $mediaUrl = function (?string $path): ?string {
            if (!$path) return null;
            return Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : asset('storage/' . ltrim($path, '/'));
        };

        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'rank'             => $this->rank,
            'duration'         => $this->duration,
            'price'            => $this->price,
            'discount_price'   => $this->discount_price,
            'discount_ends_at' => $this->discount_ends_at,
            'is_free'          => (bool) $this->is_free,
            'rating'           => $this->rating,
            'average_rating'   => $this->when(isset($this->reviews_avg_rating), round($this->reviews_avg_rating, 1)),

            // صور الكورس
            // Ensure we don't return false/0 values which cause broken URLs like /storage/0
            'cover_image'      => $this->cover_image && $this->cover_image !== '0' ? $this->cover_image : null,
            'cover_image_url'  => ($this->cover_image && $this->cover_image !== '0') 
                ? ($this->cover_image_url ?? $mediaUrl($this->cover_image)) 
                : null,

            // حالة النشر (للأدمن فقط)
            'is_published'     => $isAdmin ? (bool) $this->is_published : null,
            'status'           => $isAdmin ? ($this->is_published ? 'published' : 'draft') : null,

            // بيانات المحاضر:
            // - لو علاقة instructor محمّلة: نرجّعها كاملة
            // - لو مش محمّلة: نعمل fallback من الأعمدة المنزوعة التطبيع داخل جدول courses
            'instructor' => $this->whenLoaded('instructor', function () use ($mediaUrl) {
                return [
                    'id'     => $this->instructor->id,
                    'name'   => $this->instructor->name,
                    'title'  => $this->instructor->title,
                    'bio'    => $this->instructor->bio,
                    'image'  => $this->instructor->image ? $mediaUrl($this->instructor->image) : null,
                    'rating' => $this->instructor->avg_rate,
                ];
            }, function () use ($mediaUrl) {
                return [
                    'id'     => $this->instructor_id,
                    'name'   => $this->name_instructor,
                    'title'  => $this->title_instructor,
                    'bio'    => $this->bio_instructor,
                    'image'  => $this->image_instructor_url ?? $mediaUrl($this->image_instructor),
                    'rating' => null,
                ];
            }),

            // التصنيف:
            // - لو علاقة category محمّلة: نرجّع كائن {id, name}
            // - وإلا fallback على الأعمدة النصّية/المعرف داخل الكورس
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id'   => $this->category->id,
                    'name' => $this->category->name,
                ];
            }, function () {
                return $this->category || $this->category_id
                    ? ['id' => $this->category_id, 'name' => $this->category]
                    : null;
            }),

            // Counts
            'chapters_count' => $this->chapters_count ?? $this->whenLoaded('chapters', $this->chapters->count()),
            'lessons_count'  => $this->lessons_count ?? null,
            'reviews_count'  => $this->reviews_count ?? null,

            // علاقات (تُحمّل عند الحاجة)
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'reviews'  => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
