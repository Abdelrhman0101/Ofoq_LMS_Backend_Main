<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\UserCourse;
use App\Models\UserQuizAttempt;
use App\Http\Resources\QuestionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserQuizController extends Controller
{
    /**
     * Get random quiz questions for a chapter
     */
    public function getQuiz($chapterId)
    {
        $user = Auth::user();

        // Find quiz for this chapter
        $quiz = Quiz::where('chapter_id', $chapterId)
            ->with(['chapter.course', 'questions'])
            ->first();

        if (!$quiz) {
            return response()->json([
                'message' => 'No quiz found for this chapter'
            ], 404);
        }

        $course = $quiz->chapter->course;

        // Check if user is enrolled in the course
        $enrollment = UserCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Check user's previous attempts
        $attempts = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Check if user has exceeded max attempts
        if ($attempts->count() >= $quiz->max_attempts) {
            $bestAttempt = $attempts->max('score');
            return response()->json([
                'message' => 'You have reached the maximum number of attempts for this quiz',
                'max_attempts' => $quiz->max_attempts,
                'attempts_used' => $attempts->count(),
                'best_score' => $bestAttempt
            ], 403);
        }

        // Get questions using smart randomization
        $questions = $this->getSmartRandomQuestions($quiz, $user->id);

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'max_attempts' => $quiz->max_attempts,
                'passing_score' => $quiz->passing_score,
                'time_limit' => $quiz->time_limit,
                'attempts_used' => $attempts->count(),
                'attempts_remaining' => $quiz->max_attempts - $attempts->count()
            ],
            'questions' => QuestionResource::collection($questions)
        ]);
    }

    /**
     * Submit quiz answers and calculate score
     */
    public function submitQuiz(Request $request, $quizId)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.selected_indices' => 'required_without:answers.*.selected_answers|array',
            'answers.*.selected_answers' => 'sometimes|array',
            'time_taken' => 'nullable|integer|min:0'
        ]);

        $user = Auth::user();
        $quiz = Quiz::with(['chapter.course', 'questions'])->find($quizId);

        if (!$quiz) {
            return response()->json([
                'message' => 'Quiz not found'
            ], 404);
        }

        $course = $quiz->chapter->course;

        // Check enrollment
        $enrollment = UserCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Check attempts limit
        $attemptsCount = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->count();

        if ($attemptsCount >= $quiz->max_attempts) {
            return response()->json([
                'message' => 'Maximum attempts exceeded'
            ], 403);
        }

        // Calculate score (index-based evaluation)
        $result = $this->calculateQuizScore($quiz, $request->answers);

        // Store attempt
        $attempt = UserQuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => $result['score'],
            'total_questions' => $result['total_questions'],
            'correct_answers' => $result['correct_answers'],
            'answers' => json_encode($request->answers),
            'time_taken' => $request->time_taken ?? null,
            'passed' => $result['score'] >= $quiz->passing_score
        ]);

        // Update course progress if quiz is passed
        if ($attempt->passed) {
            $this->updateCourseProgressAfterQuiz($user->id, $course->id);
        }

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'correct_answers' => $attempt->correct_answers,
                'passed' => $attempt->passed,
                'passing_score' => $quiz->passing_score,
                'time_taken' => $attempt->time_taken,
                'attempt_number' => $attemptsCount + 1
            ],
            'detailed_results' => $result['question_results']
        ]);
    }

    /**
     * Get smart random questions (avoid repetition until question bank is exhausted)
     */
    private function getSmartRandomQuestions($quiz, $userId)
    {
        $questionsPerQuiz = $quiz->questions_per_quiz ?? $quiz->questions->count();

        // Get previously used questions in recent attempts
        $recentAttempts = UserQuizAttempt::where('user_id', $userId)
            ->where('quiz_id', $quiz->id)
            ->latest()
            ->take(3) // Consider last 3 attempts
            ->get();

        $usedQuestionIds = [];
        foreach ($recentAttempts as $attempt) {
            $answers = json_decode($attempt->answers, true);
            if ($answers) {
                $usedQuestionIds = array_merge($usedQuestionIds, array_column($answers, 'question_id'));
            }
        }

        $usedQuestionIds = array_unique($usedQuestionIds);

        // Get available questions (not recently used)
        $availableQuestions = $quiz->questions()->whereNotIn('id', $usedQuestionIds)->get();

        // If not enough unused questions, include some used ones
        if ($availableQuestions->count() < $questionsPerQuiz) {
            $additionalQuestions = $quiz->questions()
                ->whereIn('id', $usedQuestionIds)
                ->inRandomOrder()
                ->take($questionsPerQuiz - $availableQuestions->count())
                ->get();

            $availableQuestions = $availableQuestions->merge($additionalQuestions);
        }

        // Shuffle and take required number
        $selectedQuestions = $availableQuestions->shuffle()->take($questionsPerQuiz);

        // Do not shuffle options, as correct_answer indices correspond to current options order
        return $selectedQuestions;
    }

    /**
     * Calculate quiz score using option indices
     */
    private function calculateQuizScore($quiz, $userAnswers)
    {
        $totalQuestions = count($userAnswers);
        $correctAnswers = 0;
        $questionResults = [];

        foreach ($userAnswers as $userAnswer) {
            $question = $quiz->questions()->find($userAnswer['question_id']);

            if (!$question) {
                continue;
            }

            $correctIndices = is_array($question->correct_answer) ? $question->correct_answer : [$question->correct_answer];
            $correctIndices = array_map('intval', $correctIndices);
            sort($correctIndices);

            $userSelected = $userAnswer['selected_indices'] ?? ($userAnswer['selected_answers'] ?? []);
            if (!is_array($userSelected)) {
                $userSelected = [$userSelected];
            }
            $userSelected = array_map('intval', $userSelected);
            sort($userSelected);

            // Compare indices ignoring order
            $isCorrect = ($correctIndices === $userSelected);

            if ($isCorrect) {
                $correctAnswers++;
            }

            $questionResults[] = [
                'question_id' => $question->id,
                'question_text' => $question->question,
                'user_selected_indices' => $userSelected,
                'correct_indices' => $correctIndices,
                'is_correct' => $isCorrect,
                'explanation' => $question->explanation,
            ];
        }

        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        return [
            'score' => $score,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'question_results' => $questionResults
        ];
    }

    /**
     * Update course progress after passing a quiz
     */
    private function updateCourseProgressAfterQuiz($userId, $courseId)
    {
        // This would integrate with the lesson progress system
        // For now, we'll just ensure the user course record exists
        $userCourse = UserCourse::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($userCourse && $userCourse->status !== 'completed') {
            // Check if all quizzes in the course are passed
            $totalQuizzes = Quiz::whereHas('chapter', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })->count();

            $passedQuizzes = UserQuizAttempt::where('user_id', $userId)
                ->where('passed', true)
                ->whereHas('quiz.chapter', function ($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })
                ->distinct('quiz_id')
                ->count();

            // Update progress (this is a simplified version)
            $quizProgress = $totalQuizzes > 0 ? ($passedQuizzes / $totalQuizzes) * 50 : 0; // 50% weight for quizzes

            // You would combine this with lesson progress for total progress
            // For now, just update if significant progress is made
            if ($quizProgress > $userCourse->progress_percentage * 0.5) {
                $userCourse->update([
                    'progress_percentage' => min(100, $userCourse->progress_percentage + $quizProgress)
                ]);
            }
        }
    }

    /**
     * Get user's quiz attempts for a specific quiz
     */
    public function getAttempts($quizId)
    {
        $user = Auth::user();

        $attempts = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'attempts' => $attempts->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'passed' => $attempt->passed,
                    'time_taken' => $attempt->time_taken,
                    'created_at' => $attempt->created_at
                ];
            })
        ]);
    }
}
