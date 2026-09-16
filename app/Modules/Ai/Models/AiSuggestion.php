<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiSuggestion extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_suggestions';

    protected $fillable = [
        'company_id',
        'suggestion_type',
        'source_type',
        'source_id',
        'provider_id',
        'model_id',
        'policy_version',
        'payload_json',
        'reason_summary',
        'confidence',
        'risk_level',
        'status',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'confidence' => 'decimal:4',
        'expires_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AiSuggestionReview::class, 'suggestion_id');
    }
}
