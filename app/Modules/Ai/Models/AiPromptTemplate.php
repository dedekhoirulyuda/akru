<?php

namespace App\Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptTemplate extends Model
{
    protected $table = 'ai_prompt_templates';

    protected $fillable = [
        'code',
        'capability_code',
        'scope_type',
        'company_id',
        'status',
        'current_version_id',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(AiPromptVersion::class, 'template_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(AiPromptVersion::class, 'current_version_id');
    }
}
