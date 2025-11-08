<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;


class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'user_course_id',
        'verification_token',
        'file_path',
        'certificate_data',
        'issued_at'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'certificate_data' => 'array'
    ];
    public function userCourse()
    {
        return $this->belongsTo(UserCourse::class);
    }

    // Accessors shortcuts
    public function getUserAttribute()
    {
        return optional($this->userCourse)->user;
    }

    public function getCourseAttribute()
    {
        return optional($this->userCourse)->course;
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the verification URL for this certificate
     */
    public function getVerificationUrlAttribute(): string
    {
        return url("/api/certificate/verify/{$this->verification_token}");
    }

    /**
     * Get the download URL for this certificate
     */
    public function getDownloadUrlAttribute(): string
    {
        return url("/api/courses/{$this->course_id}/certificate");
    }

    /**
     * Check if certificate file exists
     */
    // public function hasFile(): bool
    // {
    //     return $this->file_path && \Storage::disk('public')->exists($this->file_path);
    // }
}
