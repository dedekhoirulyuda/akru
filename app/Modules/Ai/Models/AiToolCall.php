<?php

namespace App\Modules\Ai\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolCall extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_tool_calls';

    protected $fillable = [
        'company_id',
        'message_id',
        'tool_code',
        'tool_version',
        'arguments_redacted_json',
        'result_summary_json',
        'authorization_result',
        'duration_ms',
        'status',
        'error_code',
    ];

    protected $casts = [
        'arguments_redacted_json' => 'array',
        'result_summary_json' => 'array',
        'duration_ms' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'message_id');
    }
}
