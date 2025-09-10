<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Chapter;
use App\Http\Requests\LessonRequest;
use App\Http\Resources\LessonResource;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /**
     * Store a newly created lesson
     */
    public function store(LessonRequest $request, $chapter_id)
    {
        $chapter = Chapter::find($chapter_id);
        
        if (!$chapter) {
            return response()->json([
                'message' => 'Chapter not found'
            ], 404);
        }

        $lesson = Lesson::create(array_merge(
            $request->validated(),
            ['chapter_id' => $chapter_id]
        ));

        return response()->json([
            'message' => 'Lesson created successfully',
            'lesson' => new LessonResource($lesson)
        ], 201);
    }

    /**
     * Update the specified lesson
     */
    public function update(LessonRequest $request, $id)
    {
        $lesson = Lesson::find($id);
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found'
            ], 404);
        }

        $lesson->update($request->validated());

        return response()->json([
            'message' => 'Lesson updated successfully',
            'lesson' => new LessonResource($lesson)
        ]);
    }

    /**
     * Remove the specified lesson
     */
    public function destroy($id)
    {
        $lesson = Lesson::find($id);
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found'
            ], 404);
        }

        $lesson->delete();

        return response()->json([
            'message' => 'Lesson deleted successfully'
        ]);
    }
}