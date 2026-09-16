<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Models\AiKnowledgeChunk;
use App\Modules\Ai\Models\AiKnowledgeDocument;
use Illuminate\Support\Facades\DB;

class AiKnowledgeService
{
    /**
     * Search approved knowledge base chunks.
     */
    public function search(string $query, ?int $companyId = null, int $limit = 3): array
    {
        $q = trim($query);
        if (empty($q)) {
            return [];
        }

        $chunksQuery = AiKnowledgeChunk::with(['document.source'])
            ->whereHas('document', function ($d) {
                $d->where('status', 'approved')
                  ->where(function ($e) {
                      $e->whereNull('expires_at')->orWhere('expires_at', '>', now());
                  });
            })
            ->where(function ($c) use ($companyId) {
                $c->whereNull('company_id');
                if ($companyId) {
                    $c->orWhere('company_id', $companyId);
                }
            });

        if (DB::getDriverName() === 'mysql') {
            $chunksQuery->whereRaw("MATCH(search_text) AGAINST(? IN BOOLEAN MODE)", [$q]);
        } else {
            $chunksQuery->where('search_text', 'like', "%{$q}%");
        }

        $chunks = $chunksQuery->limit($limit)->get();

        return $chunks->map(fn($c) => [
            'document_title' => $c->document->title,
            'version' => $c->document->version,
            'section' => $c->section_path,
            'content' => $c->chunk_text,
            'source_name' => $c->document->source?->name ?? 'AKRU Knowledge Base',
        ])->toArray();
    }
}
