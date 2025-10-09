<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Course;
use App\Models\Question;
use App\Models\UserQuizAttempt;
use App\Models\Certificate;
use App\Models\UserCourse;
use Illuminate\Support\Facades\Auth;

class FinalExamController extends Controller
{
    public function start(Course $course)
    {
        $user = Auth::user();

        // 1. Find the final exam for the course
        $finalExam = $course->finalExam;

        if (!$finalExam) {
            return response()->json(['success' => false, 'message' => 'Final exam not found for this course.'], 404);
        }

        // 2. Get all questions from all lesson quizzes in the course
        $lessonQuizIds = $course->lessons()->with('quiz')->get()->pluck('quiz.id')->filter();
        $questions = Question::whereIn('quiz_id', $lessonQuizIds)->inRandomOrder()->limit(20)->get();

        if ($questions->count() < 20) {
            return response()->json(['success' => false, 'message' => 'Not enough questions in the course to generate a final exam.'], 400);
        }

        // 3. Create a new quiz attempt
        $attempt = UserQuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $finalExam->id,
            'start_time' => now(),
        ]);

        // Attach questions to the attempt
        $attempt->questions()->attach($questions->pluck('id'));

        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'questions' => $questions,
            ]
        ]);
    }

    public function submit(Request $request, Course $course, UserQuizAttempt $attempt)
    {
        $answers = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer' => 'required',
        ]);

        $questions = $attempt->questions;
        $correctAnswers = 0;

        foreach ($answers['answers'] as $answer) {
            $question = $questions->find($answer['question_id']);
            if ($question && $question->correct_answer === $answer['answer']) {
                $correctAnswers++;
            }
        }

        $totalQuestions = $questions->count();
        $score = ($correctAnswers / $totalQuestions) * 100;

        $attempt->update([
            'score' => $score,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'answers' => $answers['answers'],
            'passed' => $score >= 85,
        ]);

        $userCourse = UserCourse::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->first();

        if ($userCourse) {
            if (is_null($userCourse->final_exam_score) || $score > $userCourse->final_exam_score) {
                $userCourse->update([
                    'final_exam_score' => $score,
                ]);
            }
        }

        if ($score >= 85 && $userCourse && !$userCourse->certificate) {
            Certificate::create([
                'user_course_id' => $userCourse->id,
                'verification_token' => \Illuminate\Support\Str::uuid(),
                'issued_at' => now(),
            ]);
        }

        return response()->json([
            'score' => $score,
            'passed' => $score >= 85,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
        ]);
    }
}
/**
 * Display a listing of the resource.
 */
// public function index()
// {
//     //
// }

/**
 * Store a newly created resource in storage.
 */
// public function store(Request $request)
// {
//     //
// }

/**
 * Display the specified resource.
 */
// public function show(string $id)
// {
//     //
// }

/**
 * Update the specified resource in storage.
 */
// public function update(Request $request, string $id)
// {
//     //
// }

/**
 * Remove the specified resource from storage.
 */
    // public function destroy(string $id)
    // {
    //     //
    // }
