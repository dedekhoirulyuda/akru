<?php

namespace App\Modules\Ai\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCompanyProviderSetting extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_company_provider_settings';

    protected $fillable = [
        'company_id',
        'provider_id',
        'is_enabled',
        'credential_mode',
        'encrypted_credential_ref',
        'default_model_id',
        'data_policy_json',
        'fallback_allowed',
        'last_tested_at',
        'last_test_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'fallback_allowed' => 'boolean',
        'data_policy_json' => 'array',
        'last_tested_at' => 'datetime',
        'encrypted_credential_ref' => 'encrypted',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function defaultModel(): BelongsTo
    {
        return $this->belongsTo(AiProviderModel::class, 'default_model_id');
    }
}
