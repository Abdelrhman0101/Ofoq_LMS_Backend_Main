<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Models\Chapter;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Lesson::class, 'lesson');
    }

    public function index(Chapter $chapter)
    {
        return LessonResource::collection($chapter->lessons);
    }

    public function store(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $lesson = $chapter->lessons()->create($validated);

        return new LessonResource($lesson);
    }

    public function show(Lesson $lesson)
    {
        return new LessonResource($lesson);
    }

    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|nullable|string',
        ]);

        $lesson->update($validated);

        return new LessonResource($lesson);
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();

        return response()->noContent();
    }
}
