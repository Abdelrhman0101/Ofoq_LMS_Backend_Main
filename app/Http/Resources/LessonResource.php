<?php

namespace App\Http\Resources;

use App\Models\UserCourse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;



class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray($request)
    {
        $user = Auth::user();
        $isAdmin = $user?->role === 'admin';
        $courseId = $this->chapter?->course_id;

        $isEnrolled = false;
        if ($user && $courseId) {
            $isEnrolled = DB::table('user_courses')
                ->where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->exists();
        }

        $canView = $isAdmin || $this->is_visible || $isEnrolled;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'order' => $this->order,
            'content' => $this->content,
            'video_url' => $canView ? $this->video_url : null,
            'is_visible' => $this->is_visible,
            'attachments' => $canView ? $this->attachments : null,
            'resources' => $canView ? $this->resources : null,
            'quiz' => new QuizResource($this->whenLoaded('quiz')),
            'views' => $this->views,
        ];
    }
}
