<?php

namespace App\Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\Models\AiAnomalyFinding;
use App\Modules\Ai\Services\AiAnomalyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAnomalyController extends Controller
{
    public function __construct(
        protected AiAnomalyService $anomalyService
    ) {}

    /**
     * Trigger a scan on-demand.
     */
    public function scan(Request $request): JsonResponse
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;

        $run = $this->anomalyService->runScan($companyId, 'manual');

        $findings = AiAnomalyFinding::where('company_id', $companyId)
            ->where('status', '!=', 'resolved')
            ->orderBy('risk_score', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => "Pemindaian selesai. {$run->records_scanned} data diperiksa, {$run->findings_count} anomali terdeteksi.",
            'run_id' => $run->id,
            'findings' => $findings,
        ]);
    }

    /**
     * Mark an anomaly finding as resolved, false positive, or accepted risk.
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;

        $finding = AiAnomalyFinding::where('company_id', $companyId)->findOrFail($id);

        $status = $request->input('status', 'resolved');
        $code = $request->input('resolution_code', 'verified_clean');

        $finding->update([
            'status' => $status,
            'resolution_code' => $code,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status anomali berhasil diperbarui.',
            'finding' => $finding,
        ]);
    }
}
