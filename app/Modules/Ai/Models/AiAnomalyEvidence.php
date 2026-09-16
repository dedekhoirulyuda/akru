<?php

namespace App\Modules\Ai\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnomalyEvidence extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_anomaly_evidence';

    protected $fillable = [
        'company_id',
        'finding_id',
        'source_type',
        'source_id',
        'observed_json',
        'expected_json',
        'snapshot_at',
    ];

    protected $casts = [
        'observed_json' => 'array',
        'expected_json' => 'array',
        'snapshot_at' => 'datetime',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(AiAnomalyFinding::class, 'finding_id');
    }
}
