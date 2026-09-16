<?php

namespace App\Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeChunk extends Model
{
    protected $table = 'ai_knowledge_chunks';

    protected $fillable = [
        'company_id',
        'document_id',
        'section_path',
        'chunk_text',
        'search_text',
        'embedding_reference',
        'token_count',
        'metadata_json',
    ];

    protected $casts = [
        'token_count' => 'integer',
        'metadata_json' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeDocument::class, 'document_id');
    }
}
