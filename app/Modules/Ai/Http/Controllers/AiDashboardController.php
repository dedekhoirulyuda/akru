<?php

namespace App\Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\Models\AiAnomalyFinding;
use App\Modules\Ai\Models\AiConversation;
use App\Modules\Ai\Models\AiProvider;
use App\Modules\Ai\Models\AiSuggestion;
use App\Modules\Ai\Services\AiPolicyService;
use App\Modules\Ai\Services\AiSemanticLayer;
use App\Modules\Core\Models\Company;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\Sales\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiDashboardController extends Controller
{
    public function __construct(
        protected AiPolicyService $policyService,
        protected AiSemanticLayer $semanticLayer
    ) {}

    public function index(Request $request): View
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;

        // 1. Quota Info
        $aiQuota = $this->policyService->checkQuota($companyId);
        $company = Company::find($companyId);
        $aiQuota['engine_name'] = ($company && !$company->isTrial()) ? 'Provider 2 (Gemini) / Provider 3 (OpenAI)' : 'Provider 1 (AKRU Native AI)';

        // 2. Active Anomalies
        $anomalies = AiAnomalyFinding::where('company_id', $companyId)
            ->where('status', '!=', 'resolved')
            ->orderBy('risk_score', 'desc')
            ->limit(10)
            ->get();

        // 3. Pending Suggestions (Human-in-the-loop)
        $suggestions = AiSuggestion::where('company_id', $companyId)
            ->where('status', 'awaiting_review')
            ->latest()
            ->limit(5)
            ->get();

        // 4. Recent Conversations
        $conversations = AiConversation::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->limit(10)
            ->get();

        // 5. Providers
        $providers = AiProvider::with('models')->where('is_active', true)->get();

        // 6. Summary metrics
        $bankBalance = BankAccount::where('company_id', $companyId)->where('is_active', true)->sum('current_balance');
        $salesCount = SalesInvoice::where('company_id', $companyId)->count();
        $totalRevenue = SalesInvoice::where('company_id', $companyId)->whereIn('status', ['posted', 'paid'])->sum('total_amount');

        return view('core.ai.index', compact(
            'aiQuota',
            'anomalies',
            'suggestions',
            'conversations',
            'providers',
            'bankBalance',
            'salesCount',
            'totalRevenue'
        ));
    }
}
