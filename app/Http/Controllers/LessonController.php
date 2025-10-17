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
                        'explanation' => $questionData['explanation'] ?? null,
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

            $validatedData = $request->validated();

            // 🧩 Find the lesson
            $lesson = Lesson::findOrFail($id);

            // 🧱 Update lesson basic data
            $fields = [
                'title',
                'content',
                'order',
                'chapter_id',
                'is_visible',
                'video_url',
                'attachments',
                'resources'
            ];

            foreach ($fields as $field) {
                if (isset($validatedData[$field])) {
                    $lesson->{$field} = $validatedData[$field];
                }
            }

            $lesson->save();

            // 🎯 Handle Quiz
            if (isset($validatedData['quiz'])) {
                $quizData = $validatedData['quiz'];

                // 🗑️ Delete quiz if requested
                if (!empty($quizData['delete']) && $quizData['delete'] == true) {
                    if ($lesson->quiz) {
                        $lesson->quiz->delete();
                    }
                } else {
                    // 📘 Create or update quiz
                    $quiz = $lesson->quiz ?? new Quiz([
                        'quizzable_type' => Lesson::class,
                        'quizzable_id'   => $lesson->id,
                    ]);

                    if (isset($quizData['title'])) {
                        $quiz->title = $quizData['title'];
                    }

                    $quiz->save();

                    // 🧨 Replace old questions with new ones
                    if (isset($quizData['questions']) && is_array($quizData['questions'])) {
                        // 🧹 Delete all existing questions for this quiz
                        $quiz->questions()->delete();

                        // ➕ Insert new questions
                        foreach ($quizData['questions'] as $questionData) {
                            Question::create([
                                'quiz_id' => $quiz->id,
                                'question' => $questionData['question'] ?? '',
                                'type' => $questionData['type'] ?? 'multiple_choice',
                                'options' => isset($questionData['options'])
                                    ? json_encode($questionData['options'])
                                    : null,
                                'correct_answer' => $questionData['correct_answer'] ?? '',
                                'points' => $questionData['points'] ?? 1,
                                'explanation' => $questionData['explanation'] ?? null,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // 📦 Reload the relationships
            $lesson->load(['quiz.questions', 'chapter']);

            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully',
                'data' => new LessonResource($lesson),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson',
                'error' => $e->getMessage(),
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
