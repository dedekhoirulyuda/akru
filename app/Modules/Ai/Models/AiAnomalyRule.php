<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnomalyRule extends Model
{
    protected $table = 'ai_anomaly_rules';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'category',
        'version',
        'detector_type',
        'configuration_json',
        'base_severity',
        'is_active',
        'shadow_mode',
        'effective_at',
        'approved_by',
    ];

    protected $casts = [
        'configuration_json' => 'array',
        'is_active' => 'boolean',
        'shadow_mode' => 'boolean',
        'effective_at' => 'datetime',
    ];

    public function findings(): HasMany
    {
        return $this->hasMany(AiAnomalyFinding::class, 'rule_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
