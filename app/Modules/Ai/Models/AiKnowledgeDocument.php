<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiKnowledgeDocument extends Model
{
    protected $table = 'ai_knowledge_documents';

    protected $fillable = [
        'company_id',
        'source_id',
        'title',
        'version',
        'language',
        'effective_at',
        'expires_at',
        'status',
        'checksum',
        'file_reference',
        'supersedes_id',
        'created_by',
        'reviewed_by',
        'approved_by',
    ];

    protected $casts = [
        'effective_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeSource::class, 'source_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AiKnowledgeChunk::class, 'document_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
