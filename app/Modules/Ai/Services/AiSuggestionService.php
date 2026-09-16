<?php

namespace App\Modules\Ai\Services;

use App\Modules\Accounting\Models\JournalSet;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Ai\Models\AiSuggestion;
use App\Modules\Ai\Models\AiSuggestionReview;
use Illuminate\Support\Facades\DB;

class AiSuggestionService
{
    /**
     * Accept, edit, or reject a suggestion.
     */
    public function review(int $companyId, int $userId, int $suggestionId, string $decision, ?array $editedPayload = null, ?string $comment = null): AiSuggestionReview
    {
        $suggestion = AiSuggestion::where('company_id', $companyId)->findOrFail($suggestionId);

        if (!in_array($decision, ['accept', 'edit', 'reject'])) {
            throw new \InvalidArgumentException("Invalid decision: {$decision}");
        }

        $review = DB::transaction(function () use ($companyId, $userId, $suggestion, $decision, $editedPayload, $comment) {
            $suggestion->update([
                'status' => $decision === 'reject' ? 'rejected' : ($decision === 'edit' ? 'edited' : 'accepted'),
            ]);

            return AiSuggestionReview::create([
                'company_id' => $companyId,
                'suggestion_id' => $suggestion->id,
                'reviewer_id' => $userId,
                'decision' => $decision,
                'edited_payload_json' => $editedPayload,
                'comment' => $comment,
            ]);
        });

        return $review;
    }

    /**
     * Convert an accepted or edited suggestion into an official draft document.
     * Strictly creates DRAFT only - NEVER auto-posts!
     */
    public function convertToDraft(int $companyId, int $userId, int $suggestionId): array
    {
        $suggestion = AiSuggestion::where('company_id', $companyId)->findOrFail($suggestionId);
        $payload = $suggestion->payload_json;

        if ($suggestion->suggestion_type === 'journal_draft') {
            $draftJournal = DB::transaction(function () use ($companyId, $userId, $payload, $suggestion) {
                $journalSet = JournalSet::create([
                    'company_id' => $companyId,
                    'journal_number' => 'MEM-DRAFT-' . date('Ymd') . '-' . rand(1000, 9999),
                    'journal_date' => $payload['journal_date'] ?? date('Y-m-d'),
                    'description' => '[AI Draft] ' . ($payload['description'] ?? 'Jurnal Penyesuaian Usulan AI'),
                    'type' => 'memorial',
                    'status' => 'draft', // Human-in-the-loop: DRAFT ONLY!
                    'created_by' => $userId,
                ]);

                if (!empty($payload['lines'])) {
                    foreach ($payload['lines'] as $l) {
                        JournalLine::create([
                            'journal_set_id' => $journalSet->id,
                            'account_id' => $l['account_id'],
                            'debit' => $l['debit'] ?? 0,
                            'credit' => $l['credit'] ?? 0,
                            'description' => $l['description'] ?? null,
                        ]);
                    }
                }

                $suggestion->update(['status' => 'converted_to_draft']);

                return $journalSet;
            });

            return [
                'success' => true,
                'draft_type' => 'journal_set',
                'draft_id' => $draftJournal->id,
                'message' => 'Usulan berhasil dikonversi menjadi Jurnal Memorial berstatus DRAFT. Silakan tinjau dan posting di modul Akuntansi.',
                'deep_link' => route('journals.index'),
            ];
        }

        return [
            'success' => true,
            'message' => 'Usulan telah diterima dan tersimpan.',
        ];
    }
}
