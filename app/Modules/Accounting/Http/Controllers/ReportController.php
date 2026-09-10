<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\MasterData\Models\Account;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Sales\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Laporan Laba Rugi (Profit & Loss / Income Statement)
     */
    public function profitAndLoss(Request $request): View
    {
        $companyId = session('active_company_id');

        $dateFrom = $request->query('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        // Fetch all journal lines for revenue and expenses within period
        $lines = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereBetween('journal_sets.journal_date', [$dateFrom, $dateTo])
            ->whereIn('accounts.type', ['revenue', 'expense', 'cost_of_sales', 'other_revenue', 'other_expense'])
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();

        $revenues = collect();
        $costOfSales = collect();
        $operatingExpenses = collect();
        $otherRevenues = collect();
        $otherExpenses = collect();

        foreach ($lines as $line) {
            $debit = (float) $line->total_debit;
            $credit = (float) $line->total_credit;

            if ($line->type === 'revenue') {
                $line->net_amount = $credit - $debit; // Credit normal
                $revenues->push($line);
            } elseif ($line->type === 'cost_of_sales') {
                $line->net_amount = $debit - $credit; // Debit normal
                $costOfSales->push($line);
            } elseif ($line->type === 'expense') {
                $line->net_amount = $debit - $credit; // Debit normal
                $operatingExpenses->push($line);
            } elseif ($line->type === 'other_revenue') {
                $line->net_amount = $credit - $debit;
                $otherRevenues->push($line);
            } elseif ($line->type === 'other_expense') {
                $line->net_amount = $debit - $credit;
                $otherExpenses->push($line);
            }
        }

        $totalRevenue = $revenues->sum('net_amount');
        $totalCostOfSales = $costOfSales->sum('net_amount');
        $grossProfit = $totalRevenue - $totalCostOfSales;

        $totalOperatingExpense = $operatingExpenses->sum('net_amount');
        $operatingProfit = $grossProfit - $totalOperatingExpense;

        $totalOtherRevenue = $otherRevenues->sum('net_amount');
        $totalOtherExpense = $otherExpenses->sum('net_amount');
        $netOtherIncome = $totalOtherRevenue - $totalOtherExpense;

        $netProfitBeforeTax = $operatingProfit + $netOtherIncome;
        $estimatedTax = $netProfitBeforeTax > 0 ? ($netProfitBeforeTax * 0.22) : 0; // Tarif PPh Badan 22%
        $netProfitAfterTax = $netProfitBeforeTax - $estimatedTax;

        return view('accounting.reports.profit-loss', compact(
            'dateFrom',
            'dateTo',
            'revenues',
            'costOfSales',
            'operatingExpenses',
            'otherRevenues',
            'otherExpenses',
            'totalRevenue',
            'totalCostOfSales',
            'grossProfit',
            'totalOperatingExpense',
            'operatingProfit',
            'netOtherIncome',
            'netProfitBeforeTax',
            'estimatedTax',
            'netProfitAfterTax'
        ));
    }

    /**
     * Neraca (Balance Sheet)
     */
    public function balanceSheet(Request $request): View
    {
        $companyId = session('active_company_id');
        $asOfDate = $request->query('as_of_date', now()->toDateString());

        $lines = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->where('journal_sets.journal_date', '<=', $asOfDate)
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();

        $currentAssets = collect();
        $fixedAssets = collect();
        $currentLiabilities = collect();
        $longTermLiabilities = collect();
        $equities = collect();

        $netProfitAccumulated = 0;

        foreach ($lines as $line) {
            $debit = (float) $line->total_debit;
            $credit = (float) $line->total_credit;

            if (in_array($line->type, ['asset', 'current_asset', 'cash_and_bank'])) {
                $line->net_amount = $debit - $credit;
                $currentAssets->push($line);
            } elseif (in_array($line->type, ['fixed_asset', 'non_current_asset'])) {
                $line->net_amount = $debit - $credit;
                $fixedAssets->push($line);
            } elseif (in_array($line->type, ['liability', 'current_liability'])) {
                $line->net_amount = $credit - $debit;
                $currentLiabilities->push($line);
            } elseif (in_array($line->type, ['long_term_liability'])) {
                $line->net_amount = $credit - $debit;
                $longTermLiabilities->push($line);
            } elseif (in_array($line->type, ['equity'])) {
                $line->net_amount = $credit - $debit;
                $equities->push($line);
            } elseif (in_array($line->type, ['revenue', 'other_revenue'])) {
                $netProfitAccumulated += ($credit - $debit);
            } elseif (in_array($line->type, ['expense', 'cost_of_sales', 'other_expense'])) {
                $netProfitAccumulated -= ($debit - $credit);
            }
        }

        $totalCurrentAssets = $currentAssets->sum('net_amount');
        $totalFixedAssets = $fixedAssets->sum('net_amount');
        $totalAssets = $totalCurrentAssets + $totalFixedAssets;

        $totalCurrentLiabilities = $currentLiabilities->sum('net_amount');
        $totalLongTermLiabilities = $longTermLiabilities->sum('net_amount');
        $totalLiabilities = $totalCurrentLiabilities + $totalLongTermLiabilities;

        $totalEquityWithoutCurrentEarnings = $equities->sum('net_amount');
        $totalEquity = $totalEquityWithoutCurrentEarnings + $netProfitAccumulated;

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

        return view('accounting.reports.balance-sheet', compact(
            'asOfDate',
            'currentAssets',
            'fixedAssets',
            'currentLiabilities',
            'longTermLiabilities',
            'equities',
            'totalCurrentAssets',
            'totalFixedAssets',
            'totalAssets',
            'totalCurrentLiabilities',
            'totalLongTermLiabilities',
            'totalLiabilities',
            'totalEquityWithoutCurrentEarnings',
            'netProfitAccumulated',
            'totalEquity',
            'totalLiabilitiesAndEquity',
            'isBalanced'
        ));
    }

    /**
     * Laporan Arus Kas (Cash Flow Statement)
     */
    public function cashFlow(Request $request): View
    {
        $companyId = session('active_company_id');

        $dateFrom = $request->query('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        // Identify Cash & Bank account IDs
        $cashAccountIds = Account::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('code', 'like', '1-1%')
                  ->orWhere('type', 'cash_and_bank')
                  ->orWhere('name', 'like', '%Kas%')
                  ->orWhere('name', 'like', '%Bank%');
            })
            ->pluck('id');

        // Opening cash
        $opening = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereIn('journal_lines.account_id', $cashAccountIds)
            ->where('journal_sets.journal_date', '<', $dateFrom)
            ->select(
                DB::raw('COALESCE(SUM(journal_lines.debit) - SUM(journal_lines.credit), 0) as balance')
            )
            ->first();
        $openingCash = (float) ($opening->balance ?? 0);

        // Receipts in period (Debits to cash)
        $receipts = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereIn('journal_lines.account_id', $cashAccountIds)
            ->whereBetween('journal_sets.journal_date', [$dateFrom, $dateTo])
            ->where('journal_lines.debit', '>', 0)
            ->select(
                'journal_sets.source_type',
                DB::raw('SUM(journal_lines.debit) as amount')
            )
            ->groupBy('journal_sets.source_type')
            ->get();

        // Disbursements in period (Credits to cash)
        $disbursements = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereIn('journal_lines.account_id', $cashAccountIds)
            ->whereBetween('journal_sets.journal_date', [$dateFrom, $dateTo])
            ->where('journal_lines.credit', '>', 0)
            ->select(
                'journal_sets.source_type',
                DB::raw('SUM(journal_lines.credit) as amount')
            )
            ->groupBy('journal_sets.source_type')
            ->get();

        $totalIn = $receipts->sum('amount');
        $totalOut = $disbursements->sum('amount');
        $netCashFlow = $totalIn - $totalOut;
        $closingCash = $openingCash + $netCashFlow;

        return view('accounting.reports.cash-flow', compact(
            'dateFrom',
            'dateTo',
            'openingCash',
            'receipts',
            'disbursements',
            'totalIn',
            'totalOut',
            'netCashFlow',
            'closingCash'
        ));
    }

    /**
     * Laporan Umur Piutang (AR Aging) & Umur Hutang (AP Aging)
     */
    public function aging(Request $request): View
    {
        $companyId = session('active_company_id');
        $today = now();

        // 1. AR Aging (Sales Invoices with remaining amount > 0)
        $salesInvoices = SalesInvoice::with('contact')
            ->where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->get();

        $arAging = $salesInvoices->map(function ($inv) use ($today) {
            $daysOverdue = max(0, $today->diffInDays($inv->due_date, false) * -1);
            $amount = (float) $inv->remaining_amount;

            return [
                'number' => $inv->invoice_number,
                'contact' => $inv->contact->name ?? '-',
                'date' => $inv->invoice_date,
                'due_date' => $inv->due_date,
                'total' => (float) $inv->total_amount,
                'remaining' => $amount,
                'days' => $daysOverdue,
                'bucket_current' => $daysOverdue <= 0 ? $amount : 0,
                'bucket_1_30'    => ($daysOverdue >= 1 && $daysOverdue <= 30) ? $amount : 0,
                'bucket_31_60'   => ($daysOverdue >= 31 && $daysOverdue <= 60) ? $amount : 0,
                'bucket_61_90'   => ($daysOverdue >= 61 && $daysOverdue <= 90) ? $amount : 0,
                'bucket_over_90' => $daysOverdue > 90 ? $amount : 0,
            ];
        });

        // 2. AP Aging (Purchase Invoices with remaining amount > 0)
        $purchaseInvoices = PurchaseInvoice::with('contact')
            ->where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->get();

        $apAging = $purchaseInvoices->map(function ($inv) use ($today) {
            $daysOverdue = max(0, $today->diffInDays($inv->due_date, false) * -1);
            $amount = (float) $inv->remaining_amount;

            return [
                'number' => $inv->invoice_number,
                'contact' => $inv->contact->name ?? '-',
                'date' => $inv->invoice_date,
                'due_date' => $inv->due_date,
                'total' => (float) $inv->total_amount,
                'remaining' => $amount,
                'days' => $daysOverdue,
                'bucket_current' => $daysOverdue <= 0 ? $amount : 0,
                'bucket_1_30'    => ($daysOverdue >= 1 && $daysOverdue <= 30) ? $amount : 0,
                'bucket_31_60'   => ($daysOverdue >= 31 && $daysOverdue <= 60) ? $amount : 0,
                'bucket_61_90'   => ($daysOverdue >= 61 && $daysOverdue <= 90) ? $amount : 0,
                'bucket_over_90' => $daysOverdue > 90 ? $amount : 0,
            ];
        });

        return view('accounting.reports.aging', compact('arAging', 'apAging'));
    }

    /* ============================================================
     * EXPORT METHODS FOR FINANCIAL REPORTS
     * ============================================================ */

    public function exportProfitLossExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $dateFrom = $request->query('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        // We can reuse the view calculation or invoke logic directly
        $view = $this->profitAndLoss($request);
        $d = $view->getData();

        $headers = ['Keterangan Akun / Komponen Laba Rugi', 'Kode Akun', 'Jumlah (Rp)'];
        $rows = [];

        // Pendapatan
        $rows[] = ['1. PENDAPATAN USAHA (REVENUE)', '', ''];
        foreach ($d['revenues'] as $r) {
            $rows[] = ['   ' . $r->name, $r->code, number_format($r->net_amount, 0, ',', '.')];
        }
        $rows[] = ['TOTAL PENDAPATAN', '', number_format($d['totalRevenue'], 0, ',', '.')];
        $rows[] = ['', '', ''];

        // HPP
        $rows[] = ['2. BEBAN POKOK PENJUALAN (HPP)', '', ''];
        foreach ($d['costOfSales'] as $c) {
            $rows[] = ['   ' . $c->name, $c->code, number_format($c->net_amount, 0, ',', '.')];
        }
        $rows[] = ['TOTAL BEBAN POKOK PENJUALAN', '', number_format($d['totalCostOfSales'], 0, ',', '.')];
        $rows[] = ['LABA KOTOR (GROSS PROFIT)', '', number_format($d['grossProfit'], 0, ',', '.')];
        $rows[] = ['', '', ''];

        // Beban Operasional
        $rows[] = ['3. BEBAN OPERASIONAL & UMUM', '', ''];
        foreach ($d['operatingExpenses'] as $o) {
            $rows[] = ['   ' . $o->name, $o->code, number_format($o->net_amount, 0, ',', '.')];
        }
        $rows[] = ['TOTAL BEBAN OPERASIONAL', '', number_format($d['totalOperatingExpense'], 0, ',', '.')];
        $rows[] = ['LABA OPERASIONAL', '', number_format($d['operatingProfit'], 0, ',', '.')];
        $rows[] = ['', '', ''];

        // Laba Bersih
        $rows[] = ['LABA SEBELUM PAJAK', '', number_format($d['netProfitBeforeTax'], 0, ',', '.')];
        $rows[] = ['ESTIMASI PAJAK PPh BADAN (22%)', '', number_format($d['estimatedTax'], 0, ',', '.')];
        $rows[] = ['LABA BERSIH TAHUN BERJALAN', '', number_format($d['netProfitAfterTax'], 0, ',', '.')];

        $meta = [
            'title' => 'LAPORAN LABA RUGI (INCOME STATEMENT)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => date('d/m/Y', strtotime($dateFrom)) . ' s/d ' . date('d/m/Y', strtotime($dateTo)),
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Laba_Rugi_' . str_replace('-', '', $dateFrom) . '_' . str_replace('-', '', $dateTo), $headers, $rows, [45, 18, 24], $meta);
    }

    public function exportProfitLossPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $view = $this->profitAndLoss($request);
        $filename = 'Laporan_Laba_Rugi_' . $request->query('date_from', now()->startOfYear()->toDateString()) . '.pdf';

        return $exportService->exportPdf('accounting.reports.print-profit-loss', $view->getData(), $filename);
    }

    public function exportBalanceSheetExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $asOfDate = $request->query('as_of_date', now()->toDateString());
        $view = $this->balanceSheet($request);
        $d = $view->getData();

        $headers = ['Kategori & Nama Akun', 'Kode', 'Nilai (Rp)'];
        $rows = [];

        // Aset Lancar
        $rows[] = ['ASET (AKTIVA)', '', ''];
        $rows[] = ['1. Aset Lancar', '', ''];
        foreach ($d['currentAssets'] as $a) {
            $rows[] = ['   ' . $a->name, $a->code, number_format($a->net_amount, 0, ',', '.')];
        }
        $rows[] = ['TOTAL ASET LANCAR', '', number_format($d['totalCurrentAssets'], 0, ',', '.')];

        // Aset Tetap
        $rows[] = ['2. Aset Tetap', '', ''];
        foreach ($d['fixedAssets'] as $f) {
            $rows[] = ['   ' . $f->name, $f->code, number_format($f->net_amount, 0, ',', '.')];
        }
        $rows[] = ['TOTAL ASET TETAP', '', number_format($d['totalFixedAssets'], 0, ',', '.')];
        $rows[] = ['TOTAL SELURUH ASET', '', number_format($d['totalAssets'], 0, ',', '.')];
        $rows[] = ['', '', ''];

        // Kewajiban
        $rows[] = ['KEWAJIBAN & EKUITAS (PASIVA)', '', ''];
        $rows[] = ['1. Kewajiban Lancar (Hutang Lancar)', '', ''];
        foreach ($d['currentLiabilities'] as $l) {
            $rows[] = ['   ' . $l->name, $l->code, number_format($l->net_amount, 0, ',', '.')];
        }
        $rows[] = ['TOTAL KEWAJIBAN LANCAR', '', number_format($d['totalCurrentLiabilities'], 0, ',', '.')];

        // Ekuitas
        $rows[] = ['2. Ekuitas (Modal)', '', ''];
        foreach ($d['equities'] as $e) {
            $rows[] = ['   ' . $e->name, $e->code, number_format($e->net_amount, 0, ',', '.')];
        }
        $rows[] = ['   Laba Tahun Berjalan (Akumulasi)', '-', number_format($d['netProfitAccumulated'], 0, ',', '.')];
        $rows[] = ['TOTAL EKUITAS', '', number_format($d['totalEquity'], 0, ',', '.')];
        $rows[] = ['TOTAL KEWAJIBAN & EKUITAS', '', number_format($d['totalLiabilitiesAndEquity'], 0, ',', '.')];

        $meta = [
            'title' => 'NERACA KEUANGAN (BALANCE SHEET)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Per Tanggal ' . date('d/m/Y', strtotime($asOfDate)),
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Neraca_Keuangan_' . str_replace('-', '', $asOfDate), $headers, $rows, [45, 18, 24], $meta);
    }

    public function exportBalanceSheetPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $view = $this->balanceSheet($request);
        $filename = 'Neraca_Keuangan_' . $request->query('as_of_date', now()->toDateString()) . '.pdf';

        return $exportService->exportPdf('accounting.reports.print-balance-sheet', $view->getData(), $filename);
    }

    public function exportCashFlowExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $dateFrom = $request->query('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $view = $this->cashFlow($request);
        $d = $view->getData();

        $headers = ['Arus Kas / Aktivitas', 'Sumber / Deskripsi', 'Jumlah (Rp)'];
        $rows = [];

        $rows[] = ['SALDO KAS AWAL', date('d/m/Y', strtotime($dateFrom)), number_format($d['openingCash'], 0, ',', '.')];
        $rows[] = ['', '', ''];

        $rows[] = ['PENERIMAAN KAS (ARUS MASUK)', '', ''];
        foreach ($d['receipts'] as $r) {
            $rows[] = ['   Penerimaan Modul', $r->source_type ?? 'Lain-lain', number_format($r->amount, 0, ',', '.')];
        }
        $rows[] = ['TOTAL PENERIMAAN KAS', '', number_format($d['totalIn'], 0, ',', '.')];
        $rows[] = ['', '', ''];

        $rows[] = ['PENGELUARAN KAS (ARUS KELUAR)', '', ''];
        foreach ($d['disbursements'] as $db) {
            $rows[] = ['   Pengeluaran Modul', $db->source_type ?? 'Lain-lain', number_format($db->amount, 0, ',', '.')];
        }
        $rows[] = ['TOTAL PENGELUARAN KAS', '', number_format($d['totalOut'], 0, ',', '.')];
        $rows[] = ['', '', ''];

        $rows[] = ['KENAIKAN / (PENURUNAN) KAS BERSIH', '', number_format($d['netCashFlow'], 0, ',', '.')];
        $rows[] = ['SALDO KAS AKHIR', date('d/m/Y', strtotime($dateTo)), number_format($d['closingCash'], 0, ',', '.')];

        $meta = [
            'title' => 'LAPORAN ARUS KAS (CASH FLOW STATEMENT)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => date('d/m/Y', strtotime($dateFrom)) . ' s/d ' . date('d/m/Y', strtotime($dateTo)),
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Arus_Kas_' . str_replace('-', '', $dateFrom) . '_' . str_replace('-', '', $dateTo), $headers, $rows, [40, 24, 24], $meta);
    }

    public function exportCashFlowPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $view = $this->cashFlow($request);
        $filename = 'Laporan_Arus_Kas_' . $request->query('date_from', now()->startOfYear()->toDateString()) . '.pdf';

        return $exportService->exportPdf('accounting.reports.print-cash-flow', $view->getData(), $filename);
    }

    public function exportAgingExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $view = $this->aging($request);
        $d = $view->getData();

        $headers = [
            'Jenis',
            'No. Faktur',
            'Kontak / Mitra',
            'Tanggal',
            'Jatuh Tempo',
            'Hari Telat',
            'Total (Rp)',
            'Sisa Tagihan (Rp)',
            'Lancar (Rp)',
            '1-30 Hari',
            '31-60 Hari',
            '61-90 Hari',
            '>90 Hari'
        ];

        $rows = [];
        // Piutang
        foreach ($d['arAging'] as $ar) {
            $rows[] = [
                'PIUTANG',
                $ar['number'],
                $ar['contact'],
                $ar['date'] ? date('d/m/Y', strtotime($ar['date'])) : '-',
                $ar['due_date'] ? date('d/m/Y', strtotime($ar['due_date'])) : '-',
                $ar['days'],
                number_format($ar['total'], 0, ',', '.'),
                number_format($ar['remaining'], 0, ',', '.'),
                number_format($ar['bucket_current'], 0, ',', '.'),
                number_format($ar['bucket_1_30'], 0, ',', '.'),
                number_format($ar['bucket_31_60'], 0, ',', '.'),
                number_format($ar['bucket_61_90'], 0, ',', '.'),
                number_format($ar['bucket_over_90'], 0, ',', '.'),
            ];
        }

        // Hutang
        foreach ($d['apAging'] as $ap) {
            $rows[] = [
                'HUTANG',
                $ap['number'],
                $ap['contact'],
                $ap['date'] ? date('d/m/Y', strtotime($ap['date'])) : '-',
                $ap['due_date'] ? date('d/m/Y', strtotime($ap['due_date'])) : '-',
                $ap['days'],
                number_format($ap['total'], 0, ',', '.'),
                number_format($ap['remaining'], 0, ',', '.'),
                number_format($ap['bucket_current'], 0, ',', '.'),
                number_format($ap['bucket_1_30'], 0, ',', '.'),
                number_format($ap['bucket_31_60'], 0, ',', '.'),
                number_format($ap['bucket_61_90'], 0, ',', '.'),
                number_format($ap['bucket_over_90'], 0, ',', '.'),
            ];
        }

        $meta = [
            'title' => 'BUKU PEMBANTU & AGING PIUTANG DAN HUTANG',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Per Tanggal ' . now()->format('d/m/Y'),
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Aging_Piutang_Hutang_' . date('Ymd'), $headers, $rows, [12, 18, 25, 12, 12, 10, 16, 16, 15, 15, 15, 15, 15], $meta);
    }

    public function exportAgingPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $view = $this->aging($request);
        $filename = 'Laporan_Aging_Piutang_Hutang_' . date('Ymd') . '.pdf';

        return $exportService->exportPdf('accounting.reports.print-aging', $view->getData(), $filename);
    }
}
