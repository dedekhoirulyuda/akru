<?php

namespace App\Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProviderModel extends Model
{
    protected $table = 'ai_provider_models';

    protected $fillable = [
        'provider_id',
        'model_code',
        'display_name',
        'capability_flags_json',
        'context_limit',
        'output_limit',
        'pricing_metadata_json',
        'status',
        'deprecated_at',
    ];

    protected $casts = [
        'capability_flags_json' => 'array',
        'pricing_metadata_json' => 'array',
        'deprecated_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }
}
