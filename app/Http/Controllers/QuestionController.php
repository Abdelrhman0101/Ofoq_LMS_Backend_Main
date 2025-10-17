<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use App\Http\Requests\QuestionRequest;
use App\Http\Resources\QuestionResource;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    /**
     * Store a newly created question
     */
    public function store(QuestionRequest $request, $quiz_id)
    {
        $quiz = Quiz::find($quiz_id);
        
        if (!$quiz) {
            return response()->json([
                'message' => 'Quiz not found'
            ], 404);
        }

        $data = $request->validated();
        // if (isset($data['options'])) {
        //     $data['options'] = json_encode($data['options']);
        // }
        $data['quiz_id'] = $quiz_id;

        $question = Question::create($data);

        return response()->json([
            'message' => 'Question created successfully',
            'question' => new QuestionResource($question)
        ], 201);
    }

    /**
     * Update the specified question
     */
    public function update(QuestionRequest $request, $id)
    {
        $question = Question::find($id);
        
        if (!$question) {
            return response()->json([
                'message' => 'Question not found'
            ], 404);
        }

        $data = $request->validated();
        // if (isset($data['options'])) {
        //     $data['options'] = json_encode($data['options']);
        // }

        $question->update($data);

        return response()->json([
            'message' => 'Question updated successfully',
            'question' => new QuestionResource($question)
        ]);
    }

    /**
     * Remove the specified question
     */
    public function destroy($id)
    {
        $question = Question::find($id);
        
        if (!$question) {
            return response()->json([
                'message' => 'Question not found'
            ], 404);
        }

        $question->delete();

        return response()->json([
            'message' => 'Question deleted successfully'
        ]);
    }
}