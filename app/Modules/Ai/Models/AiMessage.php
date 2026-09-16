<?php

namespace App\Modules\Ai\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiMessage extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_messages';

    protected $fillable = [
        'company_id',
        'conversation_id',
        'role',
        'message_type',
        'content',
        'structured_content_json',
        'provider_id',
        'model_id',
        'confidence',
        'risk_level',
        'data_as_of',
        'status',
        'parent_message_id',
    ];

    protected $casts = [
        'structured_content_json' => 'array',
        'confidence' => 'decimal:4',
        'data_as_of' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function citations(): HasMany
    {
        return $this->hasMany(AiMessageCitation::class, 'message_id');
    }

    public function toolCalls(): HasMany
    {
        return $this->hasMany(AiToolCall::class, 'message_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiProviderModel::class, 'model_id');
    }
}
