<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Course;
use App\Models\Question;
use App\Models\UserQuizAttempt;
use App\Models\Certificate;
use App\Models\UserCourse;
use App\Models\Lesson;
use App\Models\UserLessonProgress;
use Illuminate\Support\Facades\Auth;

class FinalExamController extends Controller
{
    /**
     * Get the next lesson id the user should complete in a course
     * Strategy: earliest lesson (by chapter.order, then lesson.order) that is not completed
     */
    private function getNextIncompleteLessonId(int $userId, Course $course): ?int
    {
        // Ordered list of lessons within the course
        $orderedLessonIds = Lesson::query()
            ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
            ->where('chapters.course_id', $course->id)
            ->orderBy('chapters.order')
            ->orderBy('lessons.order')
            ->select('lessons.id')
            ->pluck('id');

        if ($orderedLessonIds->isEmpty()) {
            return null;
        }

        // Lessons the user has completed in this course
        $completedLessonIds = UserLessonProgress::query()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereHas('lesson.chapter', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->pluck('lesson_id');

        // Find the earliest lesson not completed
        foreach ($orderedLessonIds as $lid) {
            if (!$completedLessonIds->contains($lid)) {
                return $lid;
            }
        }

        // All lessons completed (shouldn't reach here if course is not completed)
        return null;
    }
    public function start(Course $course)
    {
        $user = Auth::user();

        // Gate: must have completed the course before starting final exam (admin bypass)
        if ($user->role !== 'admin') {
            $userCourse = UserCourse::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$userCourse || $userCourse->status !== 'completed') {
                $nextLessonId = $this->getNextIncompleteLessonId($user->id, $course);
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إنهاء المقرر بالكامل قبل بدء الاختبار النهائي',
                    'next_lesson_id' => $nextLessonId,
                ], 403);
            }
        }

        // 1. Find the final exam for the course
        $finalExam = $course->finalExam;

        if (!$finalExam) {
            return response()->json(['success' => false, 'message' => 'Final exam not found for this course.'], 404);
        }

        // 1.1 Idempotency: return active attempt if exists (admin also reuses)
        $activeAttempt = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $finalExam->id)
            ->where('status', 'in_progress')
            ->orderByDesc('start_time')
            ->first();
        if ($activeAttempt) {
            $questions = $activeAttempt->questions()->get();
            return response()->json([
                'success' => true,
                'data' => [
                    'attempt_id' => $activeAttempt->id,
                    'quiz_id' => $finalExam->id,
                    'questions' => $questions,
                ]
            ]);
        }

        // 1.2 Enforce cooldown and attempt limits using completed attempts only (admin bypasses)
        if ($user->role !== 'admin') {
            $cooldownSeconds = (int) config('exam.retake_cooldown_seconds', 15 * 60);
            $maxPerDay = (int) config('exam.max_attempts_per_day', 2);
            $maxTotal = (int) config('exam.max_attempts_total', 5);

            $now = now();
            $completedQuery = UserQuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $finalExam->id)
                ->where('status', 'submitted');

            $attemptsToday = (clone $completedQuery)
                ->where('updated_at', '>=', $now->copy()->startOfDay())
                ->count();

            $attemptsTotal = (clone $completedQuery)->count();
            $remainingToday = max(0, $maxPerDay - $attemptsToday);
            $remainingTotal = max(0, $maxTotal - $attemptsTotal);

            // Cooldown anchor: last submitted or canceled
            $lastGateAttempt = UserQuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $finalExam->id)
                ->whereIn('status', ['submitted', 'canceled'])
                ->orderByDesc('updated_at')
                ->first();
            $nextAllowedAt = null;
            if ($lastGateAttempt) {
                $nextAllowed = $lastGateAttempt->updated_at->copy()->addSeconds($cooldownSeconds);
                if ($nextAllowed->isFuture()) {
                    $nextAllowedAt = $nextAllowed->toIso8601String();
                }
            }

            // Check total limit first
            if ($attemptsTotal >= $maxTotal) {
                return response()->json([
                    'code' => 'EXAM_MAX_ATTEMPTS_TOTAL',
                    'message' => 'لقد وصلت إلى الحد الأقصى للمحاولات المسموح بها لهذا الاختبار.',
                    'next_allowed_at' => $nextAllowedAt,
                    'cooldown_seconds' => $cooldownSeconds,
                    'remaining_attempts_today' => $remainingToday,
                    'remaining_attempts_total' => 0,
                ], 429);
            }

            // Then check daily limit
            if ($attemptsToday >= $maxPerDay) {
                $nextAllowedAt = $now->copy()->addDay()->startOfDay()->toIso8601String();
                return response()->json([
                    'code' => 'EXAM_MAX_ATTEMPTS_PER_DAY',
                    'message' => 'لقد وصلت إلى الحد اليومي للمحاولات. يُمكنك المحاولة غدًا.',
                    'next_allowed_at' => $nextAllowedAt,
                    'cooldown_seconds' => $cooldownSeconds,
                    'remaining_attempts_today' => 0,
                    'remaining_attempts_total' => $remainingTotal,
                ], 429);
            }

            // Finally check cooldown if computed
            if ($nextAllowedAt) {
                return response()->json([
                    'code' => 'EXAM_RETRY_COOLDOWN',
                    'message' => 'لا يمكنك البدء الآن بسبب فترة التهدئة. يرجى المحاولة لاحقًا.',
                    'next_allowed_at' => $nextAllowedAt,
                    'cooldown_seconds' => $cooldownSeconds,
                    'remaining_attempts_today' => $remainingToday,
                    'remaining_attempts_total' => $remainingTotal,
                ], 429);
            }
        }

        // 2. Use the final exam's own question bank for this course
        $questionQuery = $finalExam->questions();
        $availableCount = $questionQuery->count();
        if ($availableCount <= 0) {
            return response()->json([
                'code' => 'question_bank_insufficient',
                'message' => 'بنك الأسئلة غير كافٍ للاختبار النهائي لهذا المقرر.'
            ], 422);
        }

        $questionsPerQuiz = (!is_null($finalExam->questions_per_quiz) && (int)$finalExam->questions_per_quiz > 0)
            ? (int)$finalExam->questions_per_quiz
            : $availableCount; // إذا لم يتم تحديد عدد الأسئلة، استخدم كل المتاح

        $questions = $questionQuery->inRandomOrder()->take($questionsPerQuiz)->get();

        // لا يوجد حد للمحاولات للاختبار النهائي وفقًا للمتطلبات

        // 3. Create a new quiz attempt (start marker)
        $attempt = UserQuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $finalExam->id,
            'start_time' => now(),
            'status' => 'in_progress',
        ]);

        // Attach questions to the attempt
        $attempt->questions()->attach($questions->pluck('id'));

        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'quiz_id' => $finalExam->id,
                'questions' => $questions, // options remain ordered; indices map to correct_answer
            ]
        ]);
    }

    public function submit(Request $request, Course $course, UserQuizAttempt $attempt)
    {
        // Accept index-based answers; keep backward compatibility for string answers
        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.selected_indices' => 'sometimes|array',
            'answers.*.answer' => 'sometimes',
            'time_taken' => 'nullable|integer|min:0'
        ]);

        // Security: ensure attempt belongs to current user and matches course final exam
        $finalExam = $course->finalExam;
        if (!$finalExam || $attempt->quiz_id !== $finalExam->id || $attempt->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid attempt for this course final exam.',
            ], 403);
        }

        $answers = $validated['answers'];
        $correctAnswers = 0;

        $questionIds = collect($answers)->pluck('question_id');
        $questions = Question::whereIn('id', $questionIds)->get();

        foreach ($answers as &$answer) { // mutate to normalized payload
            $question = $questions->firstWhere('id', $answer['question_id']);
            if (!$question) {
                continue;
            }

            $correctIndices = is_array($question->correct_answer) ? $question->correct_answer : [$question->correct_answer];
            $correctIndices = array_map('intval', $correctIndices);
            sort($correctIndices);

            // Normalize user selection to indices array
            $userSelected = $answer['selected_indices'] ?? null;

            if (is_null($userSelected) && isset($answer['answer'])) {
                // Attempt to resolve string answer to index by matching against options
                $options = is_array($question->options)
                    ? $question->options
                    : (json_decode($question->options, true) ?: []);
                $matchedIndex = null;
                foreach ($options as $idx => $opt) {
                    if (is_string($opt) && is_string($answer['answer']) && trim(strtolower($opt)) === trim(strtolower($answer['answer']))) {
                        $matchedIndex = $idx;
                        break;
                    }
                }
                if (!is_null($matchedIndex)) {
                    $userSelected = [$matchedIndex];
                } else {
                    $userSelected = [];
                }
            }

            if (!is_array($userSelected)) {
                $userSelected = [$userSelected];
            }
            $userSelected = array_map('intval', $userSelected);
            sort($userSelected);

            $isCorrect = ($correctIndices === $userSelected);
            if ($isCorrect) {
                $correctAnswers++;
            }

            // Persist normalized indices for this answer
            $answer['selected_indices'] = $userSelected;
            unset($answer['answer']);
        }

        $totalQuestions = $questions->count();
        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        // Determine passing threshold: use quiz passing_score if available, else 50
        $passingScore = (!is_null($finalExam?->passing_score)) ? (int)$finalExam->passing_score : 50;

        if (is_null($attempt->score) || $score > $attempt->score) {
            $attempt->update([
                'score' => $score,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'answers' => json_encode($answers),
                'time_taken' => $validated['time_taken'] ?? null,
                'passed' => $score >= $passingScore,
                'updated_at' => now(),
            ]);
        } else {
            // Always update status and timestamp even if score not improved
            $attempt->update([
                'answers' => json_encode($answers),
                'time_taken' => $validated['time_taken'] ?? null,
                'updated_at' => now(),
            ]);
        }

        // Mark attempt as submitted to activate cooldown/limits for next starts
        if ($attempt->status !== 'submitted') {
            $attempt->update(['status' => 'submitted']);
        }

        // Update best final exam score on enrollment
        $userCourse = UserCourse::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->first();

        if ($userCourse) {
            if (is_null($userCourse->final_exam_score) || $score > $userCourse->final_exam_score) {
                $userCourse->update(['final_exam_score' => $score]);
            }
        }

        // If passed, mark course completed and issue certificate ONLY on first successful attempt
        if ($score >= $passingScore && $userCourse) {
            if ($userCourse->status !== 'completed') {
                $userCourse->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            if (!$userCourse->certificate) {
                // Create certificate record with data
                $verificationToken = \Illuminate\Support\Str::uuid();
                // Generate unique serial number
                $prefix = 'OFQ-' . now()->format('Y');
                do {
                    $random = strtoupper(\Illuminate\Support\Str::random(6));
                    $serialNumber = $prefix . '-' . $random;
                } while (\App\Models\CourseCertificate::where('serial_number', $serialNumber)->exists());

                // Create CourseCertificate instead of Certificate
                $certificate = \App\Models\CourseCertificate::create([
                    'user_id' => Auth::id(),
                    'course_id' => $course->id,
                    'status' => 'pending', // Initial status
                    'verification_token' => $verificationToken,
                    'serial_number' => $serialNumber,
                    // certificate_data will be populated by the job
                    'certificate_data' => null, 
                ]);

                // Dispatch the job to generate the certificate PDF in the background
                \App\Jobs\GenerateCertificateJob::dispatch($certificate);
            }
        }

        return response()->json([
            'attempt_id' => $attempt->id,
            'quiz_id' => $finalExam->id,
            'score' => $score,
            'passed' => $score >= $passingScore,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'passing_score' => $passingScore,
        ]);
    }

    public function meta(Course $course)
    {
        $user = Auth::user();

        $finalExam = $course->finalExam;
        if (!$finalExam) {
            return response()->json(['success' => false, 'message' => 'Final exam not found for this course.'], 404);
        }

        // Count available questions in the final exam question bank
        $questionsCount = $finalExam->questions()->count();

        // Attempts (completed only)
        $attemptsTotal = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $finalExam->id)
            ->where('status', 'submitted')
            ->count();
        $attemptsToday = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $finalExam->id)
            ->where('status', 'submitted')
            ->where('updated_at', '>=', now()->copy()->startOfDay())
            ->count();
        $lastGateAttempt = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $finalExam->id)
            ->whereIn('status', ['submitted', 'canceled'])
            ->orderByDesc('updated_at')
            ->first();
        $lastAttemptAt = $lastGateAttempt?->updated_at;

        // Policy values
        $cooldownSeconds = (int) config('exam.retake_cooldown_seconds', 15 * 60);
        $maxPerDay = (int) config('exam.max_attempts_per_day', 2);
        $maxTotal = (int) config('exam.max_attempts_total', 5);

        // Next allowed and policy checks
        $nextAllowedAt = null;
        $isAllowedByPolicy = true;
        if ($attemptsTotal >= $maxTotal) {
            $isAllowedByPolicy = false;
        } elseif ($attemptsToday >= $maxPerDay) {
            $isAllowedByPolicy = false;
            $nextAllowedAt = now()->copy()->addDay()->startOfDay()->toIso8601String();
        } elseif ($lastAttemptAt) {
            $na = $lastAttemptAt->copy()->addSeconds($cooldownSeconds);
            if ($na->isFuture()) {
                $isAllowedByPolicy = false;
                $nextAllowedAt = $na->toIso8601String();
            }
        }
        $remainingCooldownSeconds = 0;
        if ($nextAllowedAt) {
            $remainingCooldownSeconds = max(0, \Carbon\Carbon::parse($nextAllowedAt)->diffInSeconds(now()));
        }

        // Eligibility: must have completed the course
        $userCourse = UserCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        $eligible = (bool) ($userCourse && $userCourse->status === 'completed');
        $nextLessonId = null;
        if (!$eligible) {
            $nextLessonId = $this->getNextIncompleteLessonId($user->id, $course);
        }

        $hasActiveAttempt = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $finalExam->id)
            ->where('status', 'in_progress')
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'quiz_id' => $finalExam->id,
                'questions_pool_count' => $questionsCount,
                'has_sufficient_question_bank' => $questionsCount > 0,
                'attempts_total' => $attemptsTotal,
                'attempts_today' => $attemptsToday,
                'eligible_to_start' => $eligible,
                'next_lesson_id' => $nextLessonId,
                'last_attempt_at' => $lastAttemptAt ? $lastAttemptAt->toIso8601String() : null,
                'next_allowed_at' => $nextAllowedAt,
                'remaining_cooldown_seconds' => $remainingCooldownSeconds,
                'retake_cooldown_seconds' => $cooldownSeconds,
                'max_attempts_per_day' => $maxPerDay,
                'max_attempts_total' => $maxTotal,
                'is_allowed_now' => $eligible && $isAllowedByPolicy && !$hasActiveAttempt,
            ],
        ]);
    }

    /**
     * List student courses with final exam readiness and status
     */
    public function myTests(Request $request)
    {
        $user = Auth::user();

        $enrollments = UserCourse::where('user_id', $user->id)
            ->with(['course.finalExam'])
            ->orderByDesc('id')
            ->get();

        $items = $enrollments->map(function ($enrollment) use ($user) {
            $course = $enrollment->course;
            $finalExam = $course?->finalExam;

            $eligible = $enrollment->status === 'completed';
            $attemptsCount = 0;
            $lastScore = null;
            $examStatus = 'not_taken';

            if ($finalExam) {
                $attempts = UserQuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $finalExam->id)
                    ->where('status', 'submitted')
                    ->orderByDesc('updated_at')
                    ->get();
                $attemptsCount = $attempts->count();
                $lastAttempt = $attempts->first();
                if ($lastAttempt) {
                    $lastScore = $lastAttempt->score;
                    $examStatus = $lastAttempt->passed ? 'passed' : 'failed';
                }
            }

            return [
                'course_id' => $course?->id,
                'course_title' => $course?->title,
                'status' => $enrollment->status,
                'progress_percentage' => $enrollment->progress_percentage,
                'eligible_to_start' => $eligible,
                'attempts_count' => $attemptsCount,
                'last_score' => $lastScore,
                'exam_status' => $examStatus,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    // Return current in-progress attempt with questions to handle refresh/idempotency
    public function activeAttempt(Course $course)
    {
        $user = Auth::user();
        $finalExam = $course->finalExam;
        if (!$finalExam) {
            return response()->json(['success' => false, 'message' => 'Final exam not found for this course.'], 404);
        }

        $attempt = UserQuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $finalExam->id)
            ->where('status', 'in_progress')
            ->orderByDesc('start_time')
            ->first();

        if (!$attempt) {
            return response()->json(['success' => false, 'message' => 'No active attempt found.'], 404);
        }

        $questions = $attempt->questions()->get();
        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'quiz_id' => $finalExam->id,
                'questions' => $questions,
            ]
        ]);
    }

    // Cancel an in-progress attempt without applying a submission; cooldown still anchors on updated_at
    public function cancel(Course $course, UserQuizAttempt $attempt)
    {
        $user = Auth::user();
        $finalExam = $course->finalExam;
        if (!$finalExam) {
            return response()->json(['success' => false, 'message' => 'Final exam not found for this course.'], 404);
        }

        if ($attempt->user_id !== $user->id || $attempt->quiz_id !== $finalExam->id) {
            return response()->json(['success' => false, 'message' => 'Invalid attempt for this course final exam.'], 403);
        }

        if ($attempt->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Attempt is not active and cannot be canceled.'], 409);
        }

        $attempt->update(['status' => 'canceled', 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'quiz_id' => $finalExam->id,
                'status' => 'canceled'
            ]
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
