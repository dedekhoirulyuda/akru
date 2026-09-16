<?php

namespace App\Modules\Ai\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCapabilityRoute extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_capability_routes';

    protected $fillable = [
        'company_id',
        'capability_code',
        'primary_provider_id',
        'primary_model_id',
        'fallback_provider_id',
        'fallback_model_id',
        'max_cost_per_request',
        'timeout_seconds',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_cost_per_request' => 'decimal:4',
        'timeout_seconds' => 'integer',
    ];

    public function primaryProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'primary_provider_id');
    }

    public function primaryModel(): BelongsTo
    {
        return $this->belongsTo(AiProviderModel::class, 'primary_model_id');
    }

    public function fallbackProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'fallback_provider_id');
    }

    public function fallbackModel(): BelongsTo
    {
        return $this->belongsTo(AiProviderModel::class, 'fallback_model_id');
    }
}
