<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConsentLog extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_consent_logs';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider_id',
        'consent_type',
        'policy_version',
        'granted',
        'granted_at',
        'revoked_at',
        'metadata',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }
}
