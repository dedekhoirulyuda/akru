<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageEvent extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_usage_events';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider_id',
        'model_id',
        'capability_code',
        'request_units',
        'input_tokens',
        'output_tokens',
        'estimated_cost',
        'currency',
        'cache_hit',
        'latency_ms',
        'status',
        'request_id',
    ];

    protected $casts = [
        'request_units' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'estimated_cost' => 'decimal:4',
        'cache_hit' => 'boolean',
        'latency_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiProviderModel::class, 'model_id');
    }
}
