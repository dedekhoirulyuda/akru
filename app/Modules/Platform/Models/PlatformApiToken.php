<?php

namespace App\Modules\Platform\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PlatformApiToken extends Model
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
