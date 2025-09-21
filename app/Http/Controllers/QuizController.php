<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Chapter;
use App\Http\Requests\QuizRequest;
use App\Http\Resources\QuizResource;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * Store a newly created quiz
     */
    public function store(QuizRequest $request, $chapter_id)
    {
        $chapter = Chapter::find($chapter_id);
        
        if (!$chapter) {
            return response()->json([
                'message' => 'Chapter not found'
            ], 404);
        }

        // Check if chapter already has a quiz
        if ($chapter->quiz) {
            return response()->json([
                'message' => 'Chapter already has a quiz'
            ], 422);
        }

        $quiz = Quiz::create(array_merge(
            $request->validated(),
            ['chapter_id' => $chapter_id]
        ));

        return response()->json([
            'message' => 'Quiz created successfully',
            'quiz' => new QuizResource($quiz)
        ], 201);
    }

    /**
     * Update the specified quiz
     */
    public function update(QuizRequest $request, $id)
    {
        $quiz = Quiz::find($id);
        
        if (!$quiz) {
            return response()->json([
                'message' => 'Quiz not found'
            ], 404);
        }

        $quiz->update($request->validated());

        return response()->json([
            'message' => 'Quiz updated successfully',
            'quiz' => new QuizResource($quiz)
        ]);
    }

    /**
     * Remove the specified quiz
     */
    public function destroy($id)
    {
        $quiz = Quiz::find($id);
        
        if (!$quiz) {
            return response()->json([
                'message' => 'Quiz not found'
            ], 404);
        }

        $quiz->delete();

        return response()->json([
            'message' => 'Quiz deleted successfully'
        ]);
    }

////quiz for lessons

    // public function storeForLesson(Request $request, $lesson_id)
    // {
    //     $lesson = Lesson::find($lesson_id);
        
    //     if (!$lesson) {
    //         return response()->json([
    //             'message' => 'Lesson not found'
    //         ], 404);
    //     }

    //     // Check if lesson already has a quiz
    //     if ($lesson->quiz) {
    //         return response()->json([
    //             'message' => 'Lesson already has a quiz'
    //         ], 422);
    //     }

    //     $quiz = Quiz::create(array_merge(
    //         $request->validated(),
    //         ['quizzable_id' => $lesson_id, 'quizzable_type' => Lesson::class]
    //     ));

    //     return response()->json([
    //         'message' => 'Quiz created successfully',
    //         'quiz' => new QuizResource($quiz)
    //     ], 201);
    // }
}