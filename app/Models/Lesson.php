<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($lesson) {
            $lesson->quiz()->delete();
        });
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Get the quiz for this lesson using polymorphic relationship
     */
    public function quiz()
    {
        return $this->morphOne(Quiz::class, 'quizzable');
    }
    protected $casts = [
        'resources' => 'array',
        'is_visible' => 'boolean',
    ];
    protected $fillable = [
        'chapter_id',
        'title',
        'content',
        'order',
        'attachments',
        'resources',
        'is_visible',
        'video_url',
    ];
}
