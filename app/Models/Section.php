<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'is_published',
        'display_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

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
                    $slug = $base . '-' . $i++;
                }
                $model->slug = $slug;
            }
        });
    }

    public function diplomas()
    {
        return $this->hasMany(CategoryOfCourse::class, 'section_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'section_id');
    }
}
