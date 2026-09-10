<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Models\JournalSet;
use App\Modules\MasterData\Models\Account;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Tax\Models\TaxEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $companyId = session('active_company_id');
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();
        $currentTaxPeriod = now()->format('Y-m');

        // 1. Omzet / Penjualan Bulan Ini
        $monthlyRevenue = (float) SalesInvoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->sum('total_amount');

        // 2. Total Piutang Usaha Belum Tertagih (AR Outstanding)
        $totalReceivables = (float) SalesInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('remaining_amount');

        // 3. Total Hutang Usaha Belum Dibayar (AP Outstanding)
        $totalPayables = (float) PurchaseInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('remaining_amount');

        // 4. Total Saldo Kas & Bank dari Buku Besar
        $cashAccountIds = Account::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('code', 'like', '1-1%')
                  ->orWhere('type', 'cash_and_bank')
                  ->orWhere('name', 'like', '%Kas%')
                  ->orWhere('name', 'like', '%Bank%');
            })
            ->pluck('id');

        $cashSummary = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereIn('journal_lines.account_id', $cashAccountIds)
            ->select(DB::raw('COALESCE(SUM(journal_lines.debit) - SUM(journal_lines.credit), 0) as balance'))
            ->first();
        $cashAndBankBalance = (float) ($cashSummary->balance ?? 0);

        // 5. Estimasi Laba Bersih Bulan Ini (P&L)
        $glMonthly = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereBetween('journal_sets.journal_date', [$startOfMonth, $endOfMonth])
            ->whereIn('accounts.type', ['revenue', 'expense', 'cost_of_sales', 'other_revenue', 'other_expense'])
            ->select(
                'accounts.type',
                DB::raw('SUM(journal_lines.debit) as debit'),
                DB::raw('SUM(journal_lines.credit) as credit')
            )
            ->groupBy('accounts.type')
            ->get();

        $netProfitMonth = 0;
        foreach ($glMonthly as $g) {
            $d = (float) $g->debit;
            $c = (float) $g->credit;
            if (in_array($g->type, ['revenue', 'other_revenue'])) {
                $netProfitMonth += ($c - $d);
            } else {
                $netProfitMonth -= ($d - $c);
            }
        }

        // 6. PPN Masa Bulan Berjalan
        $ppnKeluaran = (float) TaxEntry::where('company_id', $companyId)
            ->where('tax_period', $currentTaxPeriod)
            ->where('tax_type', 'PPN')
            ->where('direction', 'output')
            ->sum('tax_amount');

        $ppnMasukan = (float) TaxEntry::where('company_id', $companyId)
            ->where('tax_period', $currentTaxPeriod)
            ->where('tax_type', 'PPN')
            ->where('direction', 'input')
            ->where('is_creditable', true)
            ->sum('tax_amount');

        $selisihPpn = $ppnKeluaran - $ppnMasukan;

        // 7. Recent Transactions
        $recentSales = SalesInvoice::with('contact')
            ->where('company_id', $companyId)
            ->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $recentPurchases = PurchaseInvoice::with('contact')
            ->where('company_id', $companyId)
            ->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $recentJournals = JournalSet::where('company_id', $companyId)
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'monthlyRevenue',
            'totalReceivables',
            'totalPayables',
            'cashAndBankBalance',
            'netProfitMonth',
            'ppnKeluaran',
            'ppnMasukan',
            'selisihPpn',
            'recentSales',
            'recentPurchases',
            'recentJournals'
        ));
    }
}
