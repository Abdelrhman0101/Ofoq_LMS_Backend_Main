<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
use App\Http\Requests\ChapterRequest;
use App\Http\Resources\ChapterResource;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    /**
     * Store a newly created chapter
     */
    public function store(ChapterRequest $request, $course_id)
    {
        $course = Course::find($course_id);
        
        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }

        $chapter = Chapter::create(array_merge(
            $request->validated(),
            ['course_id' => $course_id]
        ));

        return response()->json([
            'message' => 'Chapter created successfully',
            'chapter' => new ChapterResource($chapter)
        ], 201);
    }

    /**
     * Update the specified chapter
     */
    public function update(ChapterRequest $request, $id)
    {
        $chapter = Chapter::find($id);
        
        if (!$chapter) {
            return response()->json([
                'message' => 'Chapter not found'
            ], 404);
        }

        $chapter->update($request->validated());

        return response()->json([
            'message' => 'Chapter updated successfully',
            'chapter' => new ChapterResource($chapter)
        ]);
    }

    /**
     * Remove the specified chapter
     */
    public function destroy($id)
    {
        $chapter = Chapter::find($id);
        
        if (!$chapter) {
            return response()->json([
                'message' => 'Chapter not found'
            ], 404);
        }

        $chapter->delete();

        return response()->json([
            'message' => 'Chapter deleted successfully'
        ]);
    }
}