<?php

namespace App\Services\Posting;

use App\Exceptions\PostingException;
use App\Services\AuditEngine\AuditLogger;
use App\Services\SequenceEngine\SequenceGenerator;
use Illuminate\Support\Facades\DB;

/**
 * PostingService — the SINGLE GATE for creating journal entries.
 *
 * Blueprint §2.7: "Satu pintu untuk menghasilkan balanced journal set
 * dari semua modul." Business modules MUST NOT write to journal_lines directly.
 *
 * Blueprint §1.9:
 * - Total debit = total credit before posting
 * - Posting is idempotent
 * - Posted journals are immutable
 */
class PostingService
{
    public function __construct(
        protected SequenceGenerator $sequenceGenerator,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Post a journal set from a source document.
     *
     * @param  string  $sourceType   e.g., 'sales_invoice', 'purchase_invoice', 'manual'
     * @param  int     $sourceId     ID of the source document
     * @param  int     $companyId    Company context
     * @param  array   $lines        Array of journal line data [{account_id, debit, credit, description, contact_id, item_id}]
     * @param  string  $idempotencyKey  Prevents duplicate posting on retry
     * @param  int     $actorId      User performing the action
     * @param  ?string $description
     * @param  ?string $journalDate
     * @param  ?int    $branchId
     *
     * @return object  The created or existing journal_set record
     *
     * @throws PostingException
     */
    public function post(
        string $sourceType,
        int $sourceId,
        int $companyId,
        array $lines,
        string $idempotencyKey,
        int $actorId,
        ?string $description = null,
        ?string $journalDate = null,
        ?int $branchId = null,
    ): object {
        $journalDate = $journalDate ?? now()->toDateString();

        // 1. Idempotency check: if already posted with this key, return it
        if (!empty($idempotencyKey)) {
            $existing = DB::table('journal_sets')
                ->where('company_id', $companyId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // 2. Validate balanced entries
        if (!$this->validateBalance($lines)) {
            $totals = $this->calculateTotals($lines);
            throw new PostingException(
                "Jurnal tidak seimbang! Total Debit (Rp " . number_format($totals['debit'], 2) .
                ") tidak sama dengan Total Kredit (Rp " . number_format($totals['credit'], 2) . ")."
            );
        }

        if (empty($lines)) {
            throw new PostingException("Baris jurnal tidak boleh kosong.");
        }

        // 3. Fiscal period check (must be open)
        $period = DB::table('fiscal_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $journalDate)
            ->where('end_date', '>=', $journalDate)
            ->first();

        if ($period && $period->is_closed) {
            throw new PostingException("Periode akuntansi {$period->name} telah ditutup. Tidak dapat memposting transaksi.");
        }

        // 4. Generate unique journal number
        $journalNumber = $this->sequenceGenerator->next('journal', $companyId, $branchId, substr($journalDate, 0, 7));
        $totals = $this->calculateTotals($lines);

        return DB::transaction(function () use (
            $companyId, $branchId, $journalNumber, $journalDate, $period,
            $sourceType, $sourceId, $description, $totals, $lines,
            $actorId, $idempotencyKey
        ) {
            // Create journal_set header
            $journalSetId = DB::table('journal_sets')->insertGetId([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'journal_number' => $journalNumber,
                'journal_date' => $journalDate,
                'period_id' => $period?->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description ?? "Posting otomatis dari {$sourceType} #{$sourceId}",
                'status' => 'posted',
                'total_debit' => $totals['debit'],
                'total_credit' => $totals['credit'],
                'posted_by' => $actorId,
                'posted_at' => now(),
                'idempotency_key' => $idempotencyKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create journal_lines
            $insertLines = [];
            foreach ($lines as $index => $line) {
                $insertLines[] = [
                    'journal_set_id' => $journalSetId,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? $description,
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                    'line_order' => $index + 1,
                    'contact_id' => $line['contact_id'] ?? null,
                    'item_id' => $line['item_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('journal_lines')->insert($insertLines);

            // Audit log
            $this->auditLogger->log(
                action: 'posted',
                entityType: 'journal_set',
                entityId: $journalSetId,
                newValues: ['journal_number' => $journalNumber, 'total' => $totals['debit']],
                companyId: $companyId,
                userId: $actorId,
            );

            return DB::table('journal_sets')->where('id', $journalSetId)->first();
        });
    }

    /**
     * Reverse a posted journal set.
     */
    public function reverse(
        int $journalSetId,
        string $reason,
        int $actorId,
    ): object {
        $original = DB::table('journal_sets')->where('id', $journalSetId)->first();
        if (!$original) {
            throw new PostingException("Jurnal dengan ID {$journalSetId} tidak ditemukan.");
        }

        if ($original->status === 'reversed') {
            throw new PostingException("Jurnal {$original->journal_number} sudah dibatalkan sebelumnya.");
        }

        $lines = DB::table('journal_lines')->where('journal_set_id', $journalSetId)->get();

        // Swap debits and credits for reversal lines
        $reversalLines = [];
        foreach ($lines as $line) {
            $reversalLines[] = [
                'account_id' => $line->account_id,
                'description' => "Reversal {$original->journal_number}: " . ($line->description ?? ''),
                'debit' => $line->credit,
                'credit' => $line->debit,
                'contact_id' => $line->contact_id,
                'item_id' => $line->item_id,
            ];
        }

        return DB::transaction(function () use ($original, $reversalLines, $reason, $actorId) {
            // Post reversal journal set
            $reversalJournal = $this->post(
                sourceType: 'journal_reversal',
                sourceId: $original->id,
                companyId: $original->company_id,
                lines: $reversalLines,
                idempotencyKey: "reversal_{$original->id}_" . time(),
                actorId: $actorId,
                description: "Pembalik untuk {$original->journal_number}. Alasan: {$reason}",
                branchId: $original->branch_id,
            );

            // Update original journal status
            DB::table('journal_sets')
                ->where('id', $original->id)
                ->update([
                    'status' => 'reversed',
                    'reversed_by' => $actorId,
                    'reversed_at' => now(),
                    'reversal_reason' => $reason,
                    'updated_at' => now(),
                ]);

            $this->auditLogger->log(
                action: 'reversed',
                entityType: 'journal_set',
                entityId: $original->id,
                newValues: ['reason' => $reason, 'reversal_journal_id' => $reversalJournal->id],
                companyId: $original->company_id,
                userId: $actorId,
            );

            return $reversalJournal;
        });
    }

    /**
     * Validate that total debits equal total credits.
     */
    public function validateBalance(array $lines): bool
    {
        $totals = $this->calculateTotals($lines);

        return abs($totals['debit'] - $totals['credit']) < 0.001;
    }

    protected function calculateTotals(array $lines): array
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        return [
            'debit' => round($totalDebit, 2),
            'credit' => round($totalCredit, 2),
        ];
    }
}
