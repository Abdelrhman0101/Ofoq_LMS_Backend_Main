<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Guid\Guid;

class UserQuizAttempt extends Model
{
    //
    protected $table='user_quizzes_attempts';
    protected $guarded = [];
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'user_quiz_attempt_questions');
    }
}
