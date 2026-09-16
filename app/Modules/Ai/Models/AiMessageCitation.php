<?php

namespace App\Modules\Ai\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessageCitation extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_message_citations';

    protected $fillable = [
        'company_id',
        'message_id',
        'source_type',
        'source_id',
        'source_version',
        'label',
        'locator_json',
        'deep_link',
        'data_as_of',
    ];

    protected $casts = [
        'locator_json' => 'array',
        'data_as_of' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'message_id');
    }
}
