<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiKnowledgeSource extends Model
{
    protected $table = 'ai_knowledge_sources';

    protected $fillable = [
        'company_id',
        'source_type',
        'name',
        'owner_id',
        'jurisdiction',
        'sensitivity',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AiKnowledgeDocument::class, 'source_id');
    }
}
