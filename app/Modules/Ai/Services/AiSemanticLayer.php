<?php

namespace App\Modules\Ai\Services;

use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Models\JournalSet;
use App\Modules\Finance\Models\CashTransaction;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Sales\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;

class AiSemanticLayer
{
    /**
     * Compute standardized metrics for a given company and optional period scope.
     */
    public function getMetrics(int $companyId, array $periodScope = []): array
    {
        $startDate = $periodScope['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $periodScope['end_date'] ?? now()->toDateString();

        // 1. Cash & Bank Balance (computed dynamically from posted general ledger)
        $cashAccounts = BankAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();
        $accountIds = $cashAccounts->pluck('account_id')->filter()->toArray();

        $journalBalances = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereIn('journal_lines.account_id', $accountIds)
            ->groupBy('journal_lines.account_id')
            ->select(
                'journal_lines.account_id',
                DB::raw('SUM(journal_lines.debit - journal_lines.credit) as balance')
            )
            ->pluck('balance', 'account_id');

        $totalCashBalance = 0;
        $accountsDetail = [];
        foreach ($cashAccounts as $a) {
            $bal = (float) ($journalBalances[$a->account_id] ?? 0);
            $totalCashBalance += $bal;
            $accountsDetail[] = [
                'id' => $a->id,
                'name' => $a->bank_name,
                'number' => $a->account_number,
                'balance' => $bal,
            ];
        }
        $cashBalance = $totalCashBalance;

        // 2. Sales / Revenue (Posted invoices in period)
        $salesInvoices = SalesInvoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->get();

        $revenue = (float) $salesInvoices->sum('total_amount');
        $salesTax = (float) $salesInvoices->sum('tax_amount');
        $salesCount = $salesInvoices->count();

        // 3. Purchases / Expenses in period
        $purchaseInvoices = PurchaseInvoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->get();

        $purchases = (float) $purchaseInvoices->sum('total_amount');

        // 4. Accounts Receivable (AR)
        $unpaidSales = SalesInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->get();

        $totalAr = 0;
        $overdueAr = 0;
        $today = now()->toDateString();

        foreach ($unpaidSales as $inv) {
            $due = (float) ($inv->total_amount - ($inv->paid_amount ?? 0));
            $totalAr += $due;
            if ($inv->due_date && $inv->due_date < $today) {
                $overdueAr += $due;
            }
        }

        // 5. Accounts Payable (AP)
        $unpaidPurchases = PurchaseInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->get();

        $totalAp = 0;
        $overdueAp = 0;
        foreach ($unpaidPurchases as $bill) {
            $due = (float) ($bill->total_amount - ($bill->paid_amount ?? 0));
            $totalAp += $due;
            if ($bill->due_date && $bill->due_date < $today) {
                $overdueAp += $due;
            }
        }

        // 6. Gross Profit & Net Estimates
        $cogs = $purchases; // simplified or from posted journal_lines
        $grossProfit = $revenue - $cogs;
        $grossMarginPct = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0.0;
        $netProfit = $grossProfit; // before other operating expenses

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'cash_and_bank' => [
                'total_balance' => $cashBalance,
                'accounts_count' => count($accountsDetail),
                'accounts' => $accountsDetail,
            ],
            'profitability' => [
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
                'gross_margin_pct' => $grossMarginPct,
                'net_profit' => $netProfit,
                'sales_tax_collected' => $salesTax,
                'sales_count' => $salesCount,
            ],
            'receivables' => [
                'total_ar' => $totalAr,
                'overdue_ar' => $overdueAr,
                'unpaid_invoices_count' => $unpaidSales->count(),
            ],
            'payables' => [
                'total_ap' => $totalAp,
                'overdue_ap' => $overdueAp,
                'unpaid_bills_count' => $unpaidPurchases->count(),
            ],
            'liquidity' => [
                'net_working_capital' => ($cashBalance + $totalAr) - $totalAp,
                'quick_ratio' => $totalAp > 0 ? round(($cashBalance + $totalAr) / $totalAp, 2) : null,
            ],
        ];
    }
}
