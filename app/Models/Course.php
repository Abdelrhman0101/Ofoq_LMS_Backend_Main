<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    public function chapters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Chapter::class);
    }
    public function featured()
    {
        return $this->hasOne(FeaturedCourse::class);
    }


    public function instructor()
    {
        return $this->belongsTo(Instructors::class);
    }


    public function reviews()
    {
        return $this->hasMany(Reviews::class);
    }

    // protected $casts = [
    //     'is_best' => 'boolean',
    // ];
    protected $fillable = [
        'title',
        'description',
        'price',
        'is_free',
        'duration',
        'rating',
        'discount_price',
        'discount_ends_at',
        'instructor_id',
        'chapters_count',
        'students_count',
        'hours_count',
        'reviews_count',
    ];

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

    
    // public function scopeLevel($query, $level)
    // {
    //     if (!empty($level)) {
    //         $query->where('level', $level);
    //     }
    //     return $query;
    // }


    // public function scopeLanguage($query, $language)
    // {
    //     if (!empty($language)) {
    //         $query->where('language', $language);
    //     }
    //     return $query;
    // }

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
