<?php

return [
    // Cooldown between final exam attempts in seconds
    'retake_cooldown_seconds' => env('EXAM_RETAKE_COOLDOWN_SECONDS', 15 * 60), // default 15 minutes

    // Max attempts allowed per day for final exams
    'max_attempts_per_day' => env('EXAM_MAX_ATTEMPTS_PER_DAY', 2),

    // Max attempts allowed in total for final exams per course
    'max_attempts_total' => env('EXAM_MAX_ATTEMPTS_TOTAL', 5),
];