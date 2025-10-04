<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuizRequest;
use App\Models\Lesson;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Question;
use App\Http\Requests\LessonRequest;
use App\Http\Resources\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
    /**
     * Store a newly created lesson
     */
    public function store(LessonRequest $request, $chapter_id)
    {
        $data = $request->validated();

        $chapter = Chapter::find($chapter_id);
        if (!$chapter) {
            return response()->json(['message' => 'Chapter not found'], 404);
        }
        $lesson = Lesson::create(array_merge(
            $data,
            [
                'chapter_id' => $chapter_id,
                // 'attachments' => $data['attachments'] ?? [],
                'resources'  => $data['resources'] ?? [],
            ]
        ));



        if (!empty($data['quiz'])) {
            $quizData = [
                'title' => $data['quiz']['title'],
                'quizzable_type' => 'Lesson',
                'quizzable_id' => $lesson->id,
                // 'description' => $data['quiz']['description'] ?? null,
                // 'passing_score' => $data['quiz']['passing_score'] ?? 70,
                // 'time_limit' => $data['quiz']['time_limit'] ?? null,
                // 'is_active' => true,
            ];

            $quiz = $lesson->quiz()->create($quizData);


            if (!empty($data['quiz']['questions'])) {
                foreach ($data['quiz']['questions'] as $questionData) {
                    $quiz->questions()->create([
                        'question' => $questionData['question'],
                        'options' => isset($questionData['options'])
                            ? json_encode($questionData['options'])
                            : null,
                        'correct_answer' => $questionData['correct_answer'],
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Lesson created successfully',
            'lesson' => new LessonResource($lesson->load('quiz.questions'))
        ], 201);
        // return response()->json([
        //     "date"=>$data,
        // ]);
    }

    /**
     * Update the specified lesson
     */
    public function update(LessonRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            // Get validated data
            $validatedData = $request->validated();

            // Find the lesson
            $lesson = Lesson::findOrFail($id);

            // Update lesson basic data
            if (isset($validatedData['title'])) {
                $lesson->title = $validatedData['title'];
            }
            if (isset($validatedData['content'])) {
                $lesson->content = $validatedData['content'];
            }
            if (isset($validatedData['order'])) {
                $lesson->order = $validatedData['order'];
            }
            if (isset($validatedData['chapter_id'])) {
                $lesson->chapter_id = $validatedData['chapter_id'];
            }

            $lesson->save();

            // Handle Quiz operations
            if (isset($validatedData['quiz'])) {
                $quizData = $validatedData['quiz'];

                // Check if user wants to delete the quiz
                if (isset($quizData['delete']) && $quizData['delete']) {
                    if ($lesson->quiz) {
                        $lesson->quiz->delete();
                    }
                } else {
                    // Update or create quiz
                    $quiz = $lesson->quiz;

                    if (!$quiz) {
                        // Create new quiz
                        $quiz = new Quiz();
                        $quiz->quizzable_type = Lesson::class;
                        $quiz->quizzable_id = $lesson->id;
                    }

                    // Update quiz data
                    if (isset($quizData['title'])) {
                        $quiz->title = $quizData['title'];
                    }
                    // if (isset($quizData['description'])) {
                    //     $quiz->description = $quizData['description'];
                    // }
                    // if (isset($quizData['passing_score'])) {
                    //     $quiz->passing_score = $quizData['passing_score'];
                    // }
                    // if (isset($quizData['time_limit'])) {
                    //     $quiz->time_limit = $quizData['time_limit'];
                    // }

                    $quiz->save();

                    // Handle Questions
                    if (isset($quizData['questions'])) {
                        foreach ($quizData['questions'] as $questionData) {
                            // Check if user wants to delete this question
                            if (isset($questionData['delete']) && $questionData['delete']) {
                                if (isset($questionData['id'])) {
                                    Question::where('id', $questionData['id'])
                                        ->where('quiz_id', $quiz->id)
                                        ->delete();
                                }
                                continue;
                            }

                            // Update existing question or create new one
                            if (isset($questionData['id'])) {
                                // Update existing question
                                $question = Question::where('id', $questionData['id'])
                                    ->where('quiz_id', $quiz->id)
                                    ->first();
                                if ($question) {
                                    $question->question = $questionData['question'];
                                    $question->type = $questionData['type'];
                                    $question->options = isset($questionData['options']) ? json_encode($questionData['options']) : null;
                                    $question->correct_answer = $questionData['correct_answer'];
                                    $question->points = $questionData['points'] ?? 1;
                                    $question->save();
                                }
                            } else {
                                // Create new question
                                $question = new Question();
                                $question->quiz_id = $quiz->id;
                                $question->question = $questionData['question'];
                                $question->type = $questionData['type'];
                                $question->options = isset($questionData['options']) ? json_encode($questionData['options']) : null;
                                $question->correct_answer = $questionData['correct_answer'];
                                $question->points = $questionData['points'] ?? 1;
                                $question->save();
                            }
                        }
                    }
                }
            }

            DB::commit();

            // Load the updated lesson with relationships
            $lesson->load(['quiz.questions', 'chapter']);

            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully',
                'data' => new LessonResource($lesson->load('quiz.questions'))
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson',
                'error' => $e->getMessage()
            ], 500);
        }
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


    public function addQuizToLesson(QuizRequest $request, $lessonId)
    {
        $lesson = Lesson::find($lessonId);

        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found'
            ], 404);
        }

        // validation
        $data = $request->validated();
        // create quiz for lesson
        $quiz = $lesson->quiz()->create(array_merge(
            $data,
            ['chapter_id' => $lesson->chapter_id]
        ));

        return response()->json([
            'message' => 'Quiz created successfully for this lesson',
            'quiz' => $quiz
        ], 201);
    }

}