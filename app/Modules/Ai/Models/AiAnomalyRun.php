<?php

namespace App\Modules\Ai\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnomalyRun extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_anomaly_runs';

    protected $fillable = [
        'company_id',
        'rule_set_version',
        'trigger_type',
        'scope_json',
        'started_at',
        'finished_at',
        'status',
        'records_scanned',
        'findings_count',
        'error_summary',
    ];

    protected $casts = [
        'scope_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'records_scanned' => 'integer',
        'findings_count' => 'integer',
    ];

    public function findings(): HasMany
    {
        return $this->hasMany(AiAnomalyFinding::class, 'run_id');
    }
}
