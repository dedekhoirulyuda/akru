<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnomalyFinding extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_anomaly_findings';

    protected $fillable = [
        'company_id',
        'run_id',
        'rule_id',
        'fingerprint',
        'category',
        'severity',
        'risk_score',
        'confidence',
        'materiality_value',
        'currency',
        'title',
        'explanation_summary',
        'status',
        'assignee_id',
        'due_at',
        'resolved_by',
        'resolved_at',
        'resolution_code',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'confidence' => 'decimal:4',
        'materiality_value' => 'decimal:2',
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AiAnomalyRule::class, 'rule_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiAnomalyRun::class, 'run_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(AiAnomalyEvidence::class, 'finding_id');
    }
}
