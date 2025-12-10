<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Quiz $quiz)
    {
        return QuestionResource::collection($quiz->questions);
    }

    public function store(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'options' => 'required|array|min:3|max:4',
            'correct_answer' => 'required',
        ]);

        // Sanitize options: drop empty/null, keep order, enforce 3-4 items
        $options = array_values(array_filter($validated['options'], function ($opt) {
            if (is_null($opt)) return false;
            if (is_string($opt)) return trim($opt) !== '';
            return true;
        }));
        if (count($options) < 3 || count($options) > 4) {
            return response()->json([
                'message' => 'Options must contain 3 or 4 non-empty answers'
            ], 422);
        }

        // Normalize correct_answer: accept index or text; store as option index (0-based)
        $correct = $validated['correct_answer'];
        if (is_numeric($correct)) {
            $idx = (int) $correct;
            if (!array_key_exists($idx, $options)) {
                return response()->json([
                    'message' => 'Correct answer index is out of range'
                ], 422);
            }
            $correct = (string) $idx;
        } else {
            $text = (string) $correct;
            $idx = array_search($text, $options, true);
            if ($idx === false) {
                return response()->json([
                    'message' => 'Correct answer text must match one of the provided options'
                ], 422);
            }
            $correct = (string) $idx;
        }

        $question = $quiz->questions()->create([
            'question' => $validated['question'],
            'options' => $options,
            'correct_answer' => $correct,
        ]);

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
            'options' => 'sometimes|required|array|min:3|max:4',
            'correct_answer' => 'sometimes|required',
        ]);

        $payload = [];
        if (array_key_exists('question', $validated)) {
            $payload['question'] = $validated['question'];
        }
        if (array_key_exists('options', $validated)) {
            $options = array_values(array_filter($validated['options'], function ($opt) {
                if (is_null($opt)) return false;
                if (is_string($opt)) return trim($opt) !== '';
                return true;
            }));
            if (count($options) < 3 || count($options) > 4) {
                return response()->json([
                    'message' => 'Options must contain 3 or 4 non-empty answers'
                ], 422);
            }
            $payload['options'] = $options;

            // If correct_answer provided alongside new options, validate alignment and store index
            if (array_key_exists('correct_answer', $validated)) {
                $correct = $validated['correct_answer'];
                if (is_numeric($correct)) {
                    $idx = (int) $correct;
                    if (!array_key_exists($idx, $options)) {
                        return response()->json([
                            'message' => 'Correct answer index is out of range'
                        ], 422);
                    }
                    $correct = (string) $idx;
                } else {
                    $text = (string) $correct;
                    if (!in_array($text, $options, true)) {
                        return response()->json([
                            'message' => 'Correct answer text must match one of the provided options'
                        ], 422);
                    }
                    $correct = (string) array_search($text, $options, true);
                }
                $payload['correct_answer'] = $correct;
            }
        } elseif (array_key_exists('correct_answer', $validated)) {
            // Validate correct_answer against existing options and store index
            $currentOptions = is_array($question->options) ? $question->options : (json_decode($question->options, true) ?? []);
            $correct = $validated['correct_answer'];
            if (is_numeric($correct)) {
                $idx = (int) $correct;
                if (!array_key_exists($idx, $currentOptions)) {
                    return response()->json([
                        'message' => 'Correct answer index is out of range'
                    ], 422);
                }
                $correct = (string) $idx;
            } else {
                $text = (string) $correct;
                if (!in_array($text, $currentOptions, true)) {
                    return response()->json([
                        'message' => 'Correct answer text must match one of the existing options'
                    ], 422);
                }
                $correct = (string) array_search($text, $currentOptions, true);
            }
            $payload['correct_answer'] = $correct;
        }

        $question->update($payload);

        return new QuestionResource($question);
    }

    public function destroy(Question $question)
    {
        $question->delete();

        return response()->noContent();
    }
}