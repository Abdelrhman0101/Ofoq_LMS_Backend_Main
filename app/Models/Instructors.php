<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructors extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'name',
        'title',
        'email',
        'bio',
        'image',
        'rating',
        'courses_count',
        'students_count',
        'avg_rate',
    ];
    protected $casts = [
        'rating' => 'float',
    ];
    protected $appends = [
        'avg_rate',
        'students_count',
        'courses_count',
        'image_url'
    ];

    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id', 'id');
    }

    public function getCoursesCountAttribute(): int
    {
        
        if ($this->relationLoaded('courses')) {
            return $this->courses->count();
        }

        return (int) $this->courses()->count();
    }

    public function getStudentsCountAttribute(): int
    {
        if ($this->relationLoaded('courses')) {
            
            return (int) $this->courses->sum(function ($c) {
                return (int) ($c->students_count ?? 0);
            });
        }

        return (int) $this->courses()->sum('students_count');
    }

    public function getAvgRateAttribute(): float
    {
        if ($this->relationLoaded('courses')) {
            $avg = $this->courses->avg(function ($c) {
                return (float) ($c->rating ?? 0);
            });
        } else {
            $avg = $this->courses()->avg('rating');
        }

        return round((float) ($avg ?: 0), 1);
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        
        return null;
    }
}
