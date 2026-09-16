<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptVersion extends Model
{
    protected $table = 'ai_prompt_versions';

    protected $fillable = [
        'template_id',
        'version',
        'system_policy_ref',
        'template_text',
        'output_schema_json',
        'change_summary',
        'created_by',
        'approved_by',
        'effective_at',
    ];

    protected $casts = [
        'output_schema_json' => 'array',
        'effective_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AiPromptTemplate::class, 'template_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
