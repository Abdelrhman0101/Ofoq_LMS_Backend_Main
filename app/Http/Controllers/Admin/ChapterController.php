<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChapterResource;
use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Chapter::class, 'chapter');
    }

    public function index(Course $course)
    {
        return ChapterResource::collection($course->chapters);
    }

    public function store(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:1',
        ]);

        if (!array_key_exists('order', $validated) || empty($validated['order'])) {
            $validated['order'] = $course->chapters()->count() + 1;
        }

        $chapter = $course->chapters()->create($validated);

        return new ChapterResource($chapter);
    }

    public function show(Chapter $chapter)
    {
        return new ChapterResource($chapter);
    }

    public function update(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'order' => 'sometimes|integer|min:1',
        ]);

        $chapter->update($validated);

        return new ChapterResource($chapter);
    }

    public function destroy(Chapter $chapter)
    {
        $chapter->delete();

        return response()->noContent();
    }
}
