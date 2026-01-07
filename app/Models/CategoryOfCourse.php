<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryOfCourse extends Model
{
    use HasFactory;

    protected $table = 'category_of_course';

    protected $fillable = [
        'name',
        'description',
        'slug',
        'is_published',
        'is_free',
        'price',
        'cover_image',
        'display_order',
        'total_views',
        'section_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_free' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected $appends = ['cover_image_url'];

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? Storage::url($this->cover_image) : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            // تحديث الـ slug إذا كان فارغاً أو إذا تغير الاسم
            if (empty($model->slug) || $model->isDirty('name')) {
                // استبدال المسافات بشرطات والحفاظ على الحروف العربية
                $base = preg_replace('/\s+/u', '-', trim($model->name));
                $slug = $base;
                $i = 1;
                while (self::where('slug', $slug)->where('id', '!=', $model->id ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $model->slug = $slug;
            }
        });
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }

    public function enrollments()
    {
        return $this->hasMany(\App\Models\UserCategoryEnrollment::class, 'category_id');
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

    public function certificates()
    {
        return $this->hasMany(DiplomaCertificate::class, 'diploma_id');
    }


    /**
     * Get total views from all courses in this diploma
     */
    public function getCalculatedTotalViewsAttribute()
    {
        return $this->courses()->sum('total_views');
    }
}