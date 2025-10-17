<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        "title",
        "description",
        "course_id",
        "order"
    ];

    public function quiz()
    {
        return $this->morphOne(Quiz::class, 'quizzable');
    }
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($chapter) {
            $chapter->quiz()->delete();
            foreach ($chapter->lessons as $lesson) {
                $lesson->delete();
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    // public function quiz(): HasOne
    // {
    //     return $this->hasOne(Quiz::class);
    // }
    // Chapter has one quiz

}
