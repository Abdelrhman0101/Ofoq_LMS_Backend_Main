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
            if (empty($model->slug) && !empty($model->name)) {
                $base = Str::slug($model->name);
                $slug = $base;
                $i = 1;
                while (self::where('slug', $slug)->where('id', '!=', $model->id ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $model->slug = $slug;
            }
        });
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }
}