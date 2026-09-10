<?php

namespace App\Services\ApprovalEngine;

use App\Services\AuditEngine\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * ApprovalEngine — manages multi-step approval workflows and Segregation of Duties (SoD).
 *
 * Blueprint §2.11 & §4.7:
 * - Evaluates approval policy based on company, document type, and amount.
 * - Anti-Self Approval: Creator cannot approve their own submission if policy requires SoD.
 */
class ApprovalEngine
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Check if a document requires approval and initiate a request if needed.
     *
     * @return bool True if approval request created, false if auto-approved / not required.
     */
    public function checkAndInitiate(
        string $documentType,
        int $documentId,
        float $amount,
        int $requesterId,
        int $companyId,
    ): bool {
        $policy = DB::table('approval_policies')
            ->where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->first();

        if (!$policy) {
            return false; // No approval required
        }

        DB::table('approval_requests')->insert([
            'company_id' => $companyId,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'requester_id' => $requesterId,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditLogger->log(
            action: 'approval_requested',
            entityType: $documentType,
            entityId: $documentId,
            newValues: ['amount' => $amount],
            companyId: $companyId,
            userId: $requesterId,
        );

        return true;
    }

    /**
     * Approve a document request.
     */
    public function approve(int $requestId, int $approverId, ?string $notes = null): void
    {
        $request = DB::table('approval_requests')->where('id', $requestId)->first();
        if (!$request) {
            throw new \RuntimeException("Permintaan approval tidak ditemukan.");
        }

        // Anti-Self Approval check
        if ($request->requester_id === $approverId) {
            throw new \RuntimeException("Pelanggaran Pemisahan Tugas (SoD): Anda tidak boleh menyetujui transaksi yang Anda buat sendiri.");
        }

        DB::table('approval_requests')->where('id', $requestId)->update([
            'approver_id' => $approverId,
            'status' => 'approved',
            'notes' => $notes,
            'approved_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditLogger->log(
            action: 'approved',
            entityType: $request->document_type,
            entityId: $request->document_id,
            newValues: ['notes' => $notes],
            companyId: $request->company_id,
            userId: $approverId,
        );
    }

    /**
     * Reject a document request.
     */
    public function reject(int $requestId, int $approverId, string $notes): void
    {
        $request = DB::table('approval_requests')->where('id', $requestId)->first();
        if (!$request) {
            throw new \RuntimeException("Permintaan approval tidak ditemukan.");
        }

        DB::table('approval_requests')->where('id', $requestId)->update([
            'approver_id' => $approverId,
            'status' => 'rejected',
            'notes' => $notes,
            'rejected_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditLogger->log(
            action: 'rejected',
            entityType: $request->document_type,
            entityId: $request->document_id,
            newValues: ['notes' => $notes],
            companyId: $request->company_id,
            userId: $approverId,
        );
    }
}
