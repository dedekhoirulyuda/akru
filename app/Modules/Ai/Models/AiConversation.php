<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_conversations';

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'mode',
        'branch_scope_json',
        'period_scope_json',
        'provider_preference',
        'status',
        'last_message_at',
        'retention_expires_at',
    ];

    protected $casts = [
        'branch_scope_json' => 'array',
        'period_scope_json' => 'array',
        'last_message_at' => 'datetime',
        'retention_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('id', 'asc');
    }
}
