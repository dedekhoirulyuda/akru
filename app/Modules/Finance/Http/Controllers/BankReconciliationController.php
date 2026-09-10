<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\BankAccount;
use App\Services\Export\DataExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $bankAccounts = BankAccount::with('account')->where('is_active', true)->get();
        $selectedBankId = $request->query('bank_account_id', $bankAccounts->first()?->id);
        $selectedBank = $bankAccounts->firstWhere('id', $selectedBankId);

        // Fetch journal lines for this bank's GL account
        $movements = collect();
        $bookBalance = 0;

        if ($selectedBank && $selectedBank->account_id) {
            $movements = DB::table('journal_lines')
                ->join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
                ->where('journal_sets.company_id', $companyId)
                ->where('journal_lines.account_id', $selectedBank->account_id)
                ->where('journal_sets.status', 'posted')
                ->select(
                    'journal_lines.*',
                    'journal_sets.journal_number',
                    'journal_sets.journal_date',
                    'journal_sets.source_type'
                )
                ->orderBy('journal_sets.journal_date')
                ->get();

            $totalDebit = $movements->sum('debit');
            $totalCredit = $movements->sum('credit');
            $bookBalance = $totalDebit - $totalCredit;
        }

        return view('finance.reconciliation.index', compact('bankAccounts', 'selectedBank', 'movements', 'bookBalance'));
    }

    public function exportExcel(Request $request, DataExportService $exportService)
    {
        $companyId = session('current_company_id');
        $bankAccounts = BankAccount::with('account')->where('is_active', true)->get();
        $selectedBankId = $request->query('bank_account_id', $bankAccounts->first()?->id);
        $selectedBank = $bankAccounts->firstWhere('id', $selectedBankId);

        $movements = collect();

        if ($selectedBank && $selectedBank->account_id) {
            $movements = DB::table('journal_lines')
                ->join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
                ->where('journal_sets.company_id', $companyId)
                ->where('journal_lines.account_id', $selectedBank->account_id)
                ->where('journal_sets.status', 'posted')
                ->select(
                    'journal_lines.*',
                    'journal_sets.journal_number',
                    'journal_sets.journal_date',
                    'journal_sets.source_type'
                )
                ->orderBy('journal_sets.journal_date')
                ->get();
        }

        $headers = [
            'Tanggal',
            'No. Jurnal',
            'Sumber Transaksi',
            'Deskripsi / Catatan',
            'Debit / Masuk (Rp)',
            'Kredit / Keluar (Rp)',
            'Saldo Berjalan (Rp)'
        ];

        $rows = [];
        $runningBalance = 0;
        foreach ($movements as $m) {
            $runningBalance += ($m->debit - $m->credit);
            $rows[] = [
                $m->journal_date ? date('d/m/Y', strtotime($m->journal_date)) : '-',
                $m->journal_number,
                strtoupper(str_replace('_', ' ', $m->source_type ?? 'MANUAL')),
                $m->memo ?? '-',
                number_format($m->debit, 0, ',', '.'),
                number_format($m->credit, 0, ',', '.'),
                number_format($runningBalance, 0, ',', '.'),
            ];
        }

        $meta = [
            'title' => 'LAPORAN REKONSILIASI MUTASI BANK',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Rekening: ' . ($selectedBank ? $selectedBank->bank_name . ' (' . ($selectedBank->account_number ?? 'Kas') . ')' : '-'),
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Rekonsiliasi_Bank_' . ($selectedBank ? str_replace(' ', '_', $selectedBank->bank_name) . '_' : '') . date('Ymd'), $headers, $rows, [14, 20, 20, 36, 20, 20, 22], $meta);
    }

    public function exportPdf(Request $request, DataExportService $exportService)
    {
        $companyId = session('current_company_id');
        $bankAccounts = BankAccount::with('account')->where('is_active', true)->get();
        $selectedBankId = $request->query('bank_account_id', $bankAccounts->first()?->id);
        $selectedBank = $bankAccounts->firstWhere('id', $selectedBankId);

        $movements = collect();
        $bookBalance = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        if ($selectedBank && $selectedBank->account_id) {
            $movements = DB::table('journal_lines')
                ->join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
                ->where('journal_sets.company_id', $companyId)
                ->where('journal_lines.account_id', $selectedBank->account_id)
                ->where('journal_sets.status', 'posted')
                ->select(
                    'journal_lines.*',
                    'journal_sets.journal_number',
                    'journal_sets.journal_date',
                    'journal_sets.source_type'
                )
                ->orderBy('journal_sets.journal_date')
                ->get();

            $totalDebit = $movements->sum('debit');
            $totalCredit = $movements->sum('credit');
            $bookBalance = $totalDebit - $totalCredit;
        }

        return $exportService->exportPdf(
            'finance.reconciliation.print',
            compact('selectedBank', 'movements', 'bookBalance', 'totalDebit', 'totalCredit'),
            'Rekonsiliasi_Bank_' . ($selectedBank ? str_replace(' ', '_', $selectedBank->bank_name) . '_' : '') . date('Ymd') . '.pdf'
        );
    }
}
