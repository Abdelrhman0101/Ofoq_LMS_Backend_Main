<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiplomaCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'diploma_id', // Changed from category_id to diploma_id
        'user_category_enrollment_id',
        'verification_token',
        'serial_number',
        'student_name',
        'file_path',
        'certificate_data',
        'issued_at',
        'status',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'certificate_data' => 'array',
        'status' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diploma(): BelongsTo
    {
        return $this->belongsTo(CategoryOfCourse::class, 'diploma_id', 'id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(UserCategoryEnrollment::class, 'user_category_enrollment_id');
    }

    public function getVerificationUrlAttribute(): string
    {
        return url("/api/diploma-certificate/verify/{$this->verification_token}");
    }

    public function getDownloadUrlAttribute(): string
    {
        return url("/api/diplomas/{$this->diploma_id}/certificate");
    }
}