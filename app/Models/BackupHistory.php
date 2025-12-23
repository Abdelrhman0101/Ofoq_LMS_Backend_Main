<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupHistory extends Model
{
    protected $fillable = [
        'user_id', 'action', 'status', 'filename', 'details', 'ip_address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
