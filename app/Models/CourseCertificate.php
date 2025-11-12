<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCertificate extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'file_path',
        'serial_number',
        'certificate_data',
        'verification_token',
    ];

    protected $casts = [
        'certificate_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
