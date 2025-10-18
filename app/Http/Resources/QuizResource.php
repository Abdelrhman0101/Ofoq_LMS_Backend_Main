<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


class QuizResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdmin = Auth::user()?->role === 'admin';
        return [
            'id' => $this->id,
            'title' => $this->title,
            // 'chapter_id' => $this->chapter_id,
            // لو عندك علاقة polymorphic
            // 'quizzable_type' => $this->quizzable_type,
            // 'quizzable_id' => $this->quizzable_id,

            'questions' => $this->when(
                $isAdmin,
                QuestionResource::collection($this->whenLoaded('questions'))
            ),
        ];
    }
}
