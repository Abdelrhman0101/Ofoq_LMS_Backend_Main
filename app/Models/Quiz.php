<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    public function quizzable()
    {
        return $this->morphTo();
    }
    public function userAttempts()
    {
        return $this->hasMany(UserQuizAttempt::class, 'quiz_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
    protected $fillable = [
        'title',
        'description',
        'quizzable_type',
        'quizzable_id',
        'is_final',
        // 'passing_score',
        // 'time_limit',
    ];

    protected $casts = [
        'is_final' => 'boolean',
    ];
}
