<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\BudgetLine;
use App\Modules\MasterData\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $budgets = Budget::with(['lines.account'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(10);

        // Calculate actual vs budget for the active budget
        $activeBudget = $budgets->first();
        $comparison = [];

        if ($activeBudget) {
            foreach ($activeBudget->lines as $line) {
                // Sum actual debits - credits for expense accounts in this fiscal year
                $actual = JournalLine::whereHas('journalSet', function ($q) use ($companyId, $activeBudget) {
                    $q->where('company_id', $companyId)
                      ->where('status', 'posted')
                      ->whereYear('journal_date', $activeBudget->fiscal_year);
                })->where('account_id', $line->account_id)
                  ->sum(DB::raw('debit - credit'));

                $budgetAmt = (float) $line->annual_amount;
                $diff = $budgetAmt - $actual;
                $usagePct = $budgetAmt > 0 ? round(($actual / $budgetAmt) * 100, 1) : 0;

                $comparison[] = [
                    'account' => $line->account,
                    'budget' => $budgetAmt,
                    'actual' => $actual,
                    'difference' => $diff,
                    'usage_percent' => $usagePct,
                ];
            }
        }

        $accounts = Account::where('company_id', $companyId)->where('type', 'expense')->get();

        return view('finance.budgets.index', compact('budgets', 'activeBudget', 'comparison', 'accounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'name' => 'required|string|max:100',
            'fiscal_year' => 'required|integer|min:2020|max:2035',
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.annual_amount' => 'required|numeric|min:1',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $budget = Budget::create([
                'company_id' => $companyId,
                'name' => $request->name,
                'fiscal_year' => $request->fiscal_year,
                'status' => 'active',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $annual = (float) $row['annual_amount'];
                BudgetLine::create([
                    'budget_id' => $budget->id,
                    'account_id' => $row['account_id'],
                    'annual_amount' => $annual,
                    'monthly_amount' => round($annual / 12, 2),
                ]);
            }
        });

        return redirect()->route('budgets.index')->with('success', 'Pagu Anggaran (Budget) tahunan berhasil ditetapkan.');
    }
}
