<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reason',
        'is_blocked',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCurrentlyBlocked(): bool
    {
        // User is considered blocked based on boolean flag
        return (bool) $this->is_blocked;
    }
}