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
    ];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
