<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class FeaturedCourse extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'course_id',
        'priority',
        // 'featured_at',
        // 'expires_at',
        'is_active',
    ];

    protected $casts = [
        // 'featured_at' => 'datetime',
        // 'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }


    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            $query->whereHas('course', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            });
        }

        return $query;
    }


    public function scopeField($query, $field)
    {
        if (!empty($field)) {
            $query->whereHas('course', function ($q) use ($field) {
                $q->where('field', $field);
            });
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
