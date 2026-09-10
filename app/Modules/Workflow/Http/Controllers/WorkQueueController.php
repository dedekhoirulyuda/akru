<?php

namespace App\Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Workflow\Models\ApprovalRequest;
use App\Services\ApprovalEngine\ApprovalEngine;
use App\Services\AuditEngine\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class WorkQueueController extends Controller
{
    public function __construct(
        protected ApprovalEngine $approvalEngine,
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $companyId = session('active_company_id');
        $user = auth()->user();

        $pendingRequests = ApprovalRequest::with(['requester', 'approver'])
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $historyRequests = ApprovalRequest::with(['requester', 'approver'])
            ->where('company_id', $companyId)
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return view('workflow.index', compact('pendingRequests', 'historyRequests', 'user'));
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        $userId = auth()->id();

        // Enforce Anti-Self Approval
        if ($approvalRequest->requester_id === $userId) {
            return back()->with('error', 'Pelanggaran Anti-Self Approval: Anda tidak diizinkan menyetujui dokumen yang Anda ajukan sendiri!');
        }

        $approvalRequest->update([
            'status' => 'approved',
            'approver_id' => $userId,
            'approved_at' => now(),
            'notes' => $request->input('notes', 'Disetujui.'),
        ]);

        $this->auditLogger->log(
            action: 'approved',
            entityType: ApprovalRequest::class,
            entityId: $approvalRequest->id,
            newValues: ['status' => 'approved', 'approver_id' => $userId],
            companyId: $approvalRequest->company_id,
            userId: $userId
        );

        return back()->with('success', "Permintaan persetujuan #{$approvalRequest->id} berhasil disetujui.");
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        $userId = auth()->id();

        $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        $approvalRequest->update([
            'status' => 'rejected',
            'approver_id' => $userId,
            'rejected_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        $this->auditLogger->log(
            action: 'rejected',
            entityType: ApprovalRequest::class,
            entityId: $approvalRequest->id,
            newValues: ['status' => 'rejected', 'approver_id' => $userId, 'reason' => $request->input('notes')],
            companyId: $approvalRequest->company_id,
            userId: $userId
        );

        return back()->with('success', "Permintaan persetujuan #{$approvalRequest->id} telah ditolak.");
    }
}
