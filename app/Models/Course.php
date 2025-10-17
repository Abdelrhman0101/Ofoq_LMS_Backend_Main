<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;



class Course extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($course) {
            $course->chapters()->get()->each(function ($chapter) {
                $chapter->delete();
            });
        });
    }

    // protected static function booted(): void
    // {
    //     static::addGlobalScope('published', function (Builder $builder) {
    //         $builder->where('is_published', true);
    //     });
    // }

    public function chapters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Chapter::class);
    }
    public function featured()
    {
        return $this->hasOne(FeaturedCourse::class);
    }

    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Chapter::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructors::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\CategoryOfCourse::class, 'category_id');
    }


    public function reviews()
    {
        return $this->hasMany(Reviews::class, 'course_id');
    }

    public function favoriteByUsers()
    {
        return $this->belongsToMany(
            User::class,
            'user_favorite_courses',
            'course_id',
            'user_id'
        )->withTimestamps();
    }
    public function quizzes()
    {
        return $this->morphMany(\App\Models\Quiz::class, 'quizzable');
    }


    public function finalExam()
    {
        return $this->morphOne(\App\Models\Quiz::class, 'quizzable')
            ->where('is_final', true);
    }


    protected static function booted()
    {
        static::addGlobalScope('published', function (Builder $builder) {
            $builder->where('is_published', true);
        });
        static::created(function ($course) {
            if (!$course->finalExam) {
                \App\Models\Quiz::create([
                    'title' => 'Final Exam - ' . $course->title,
                    'is_final' => true,
                    'quizzable_type' => self::class,
                    'quizzable_id' => $course->id,
                ]);
            }
        });
    }

    protected $casts = [
        'is_free' => 'boolean',
        'is_published' => 'boolean',
    ];
    protected $fillable = [
        'title',
        'category',
        'description',
        'price',
        'is_free',
        'is_published',
        'duration',
        'rating',
        'discount_price',
        'discount_ends_at',
        'instructor_id',
        'category_id',
        'name_instructor',
        'bio_instructor',
        'image_instructor',
        'title_instructor',
        'chapters_count',
        'students_count',
        'hours_count',
        'reviews_count',
        'cover_image'
    ];
    protected $appends = ['cover_image_url'];

    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image
            ? Storage::url($this->cover_image)
            : null;
    }
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        return $query;
    }


    public function scopeField($query, $field)
    {
        if (!empty($field)) {
            $query->where('field', $field);
        }
        return $query;
    }


    public function scopeSort($query, $sort)
    {
        if ($sort === 'latest') {
            $query->latest();
        } elseif ($sort === 'oldest') {
            $query->oldest();
        }
        return $query;
    }
}
