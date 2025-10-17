<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Question::class, 'question');
    }

    public function index(Quiz $quiz)
    {
        return QuestionResource::collection($quiz->questions);
    }

    public function store(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'options' => 'required|array',
            'correct_answer' => 'required|string',
        ]);

        $question = $quiz->questions()->create($validated);

        return new QuestionResource($question);
    }

    public function show(Question $question)
    {
        return new QuestionResource($question);
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question' => 'sometimes|required|string',
            'options' => 'sometimes|required|array',
            'correct_answer' => 'sometimes|required|string',
        ]);

        $question->update($validated);

        return new QuestionResource($question);
    }

    public function destroy(Question $question)
    {
        $question->delete();

        return response()->noContent();
    }
}