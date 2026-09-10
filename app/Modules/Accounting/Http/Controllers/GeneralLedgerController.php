<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\MasterData\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GeneralLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = session('active_company_id');

        $accountId = $request->query('account_id');
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $selectedAccount = null;
        $openingBalance = 0;
        $ledgerEntries = collect();
        $totalDebit = 0;
        $totalCredit = 0;
        $closingBalance = 0;

        if ($accountId) {
            $selectedAccount = Account::where('company_id', $companyId)->find($accountId);

            if ($selectedAccount) {
                $isDebitNormal = in_array(strtolower($selectedAccount->type), ['asset', 'expense']);

                // Calculate opening balance before dateFrom
                $priorSummary = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
                    ->where('journal_sets.company_id', $companyId)
                    ->where('journal_sets.status', 'posted')
                    ->where('journal_lines.account_id', $selectedAccount->id)
                    ->where('journal_sets.journal_date', '<', $dateFrom)
                    ->select(
                        DB::raw('COALESCE(SUM(journal_lines.debit), 0) as prior_debit'),
                        DB::raw('COALESCE(SUM(journal_lines.credit), 0) as prior_credit')
                    )
                    ->first();

                $priorDebit = (float) ($priorSummary->prior_debit ?? 0);
                $priorCredit = (float) ($priorSummary->prior_credit ?? 0);

                $openingBalance = $isDebitNormal 
                    ? ($priorDebit - $priorCredit) 
                    : ($priorCredit - $priorDebit);

                // Fetch period transactions
                $rawEntries = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
                    ->where('journal_sets.company_id', $companyId)
                    ->where('journal_sets.status', 'posted')
                    ->where('journal_lines.account_id', $selectedAccount->id)
                    ->whereBetween('journal_sets.journal_date', [$dateFrom, $dateTo])
                    ->select(
                        'journal_lines.*',
                        'journal_sets.journal_number',
                        'journal_sets.journal_date',
                        'journal_sets.source_type',
                        'journal_sets.source_id'
                    )
                    ->orderBy('journal_sets.journal_date')
                    ->orderBy('journal_sets.id')
                    ->orderBy('journal_lines.id')
                    ->get();

                $runningBalance = $openingBalance;
                $ledgerEntries = $rawEntries->map(function ($entry) use (&$runningBalance, $isDebitNormal, &$totalDebit, &$totalCredit) {
                    $debit = (float) $entry->debit;
                    $credit = (float) $entry->credit;

                    $totalDebit += $debit;
                    $totalCredit += $credit;

                    if ($isDebitNormal) {
                        $runningBalance += ($debit - $credit);
                    } else {
                        $runningBalance += ($credit - $debit);
                    }

                    $entry->running_balance = $runningBalance;
                    return $entry;
                });

                $closingBalance = $runningBalance;
            }
        }

        $data = $this->computeLedger($companyId, $accountId, $dateFrom, $dateTo);

        return view('accounting.ledger.index', array_merge($data, [
            'accounts' => $accounts,
            'accountId' => $accountId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $accountId = $request->query('account_id');
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $data = $this->computeLedger($companyId, $accountId, $dateFrom, $dateTo);
        $account = $data['selectedAccount'];

        $accountLabel = $account ? ($account->code . ' - ' . $account->name) : 'Semua Akun';

        $headers = [
            'Tanggal',
            'No. Jurnal',
            'Sumber',
            'Keterangan / Uraian Transaksi',
            'Debit (Rp)',
            'Kredit (Rp)',
            'Saldo Berjalan (Rp)'
        ];

        $rows = [];
        // Opening balance row
        $rows[] = [
            date('d/m/Y', strtotime($dateFrom)),
            '-',
            'SALDO AWAL',
            'Saldo Awal Periode ' . date('d/m/Y', strtotime($dateFrom)),
            '0',
            '0',
            number_format($data['openingBalance'], 0, ',', '.'),
        ];

        foreach ($data['ledgerEntries'] as $entry) {
            $rows[] = [
                $entry->journal_date ? date('d/m/Y', strtotime($entry->journal_date)) : '-',
                $entry->journal_number ?? '-',
                $entry->source_type ?? '-',
                $entry->memo ?: ($entry->header_description ?: '-'),
                number_format($entry->debit, 0, ',', '.'),
                number_format($entry->credit, 0, ',', '.'),
                number_format($entry->running_balance, 0, ',', '.'),
            ];
        }

        // Closing balance row
        $rows[] = [
            date('d/m/Y', strtotime($dateTo)),
            '-',
            'SALDO AKHIR',
            'Saldo Akhir Periode ' . date('d/m/Y', strtotime($dateTo)),
            number_format($data['totalDebit'], 0, ',', '.'),
            number_format($data['totalCredit'], 0, ',', '.'),
            number_format($data['closingBalance'], 0, ',', '.'),
        ];

        $meta = [
            'title' => 'BUKU BESAR (GENERAL LEDGER) — ' . $accountLabel,
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => date('d/m/Y', strtotime($dateFrom)) . ' s/d ' . date('d/m/Y', strtotime($dateTo)),
            'date' => now()->format('d/m/Y H:i'),
        ];

        $filename = 'Buku_Besar_' . ($account ? $account->code : 'All') . '_' . str_replace('-', '', $dateFrom) . '_' . str_replace('-', '', $dateTo);

        return $exportService->exportXlsx($filename, $headers, $rows, [14, 20, 18, 42, 20, 20, 22], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $accountId = $request->query('account_id');
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $data = $this->computeLedger($companyId, $accountId, $dateFrom, $dateTo);
        $account = $data['selectedAccount'];

        $filename = 'Buku_Besar_' . ($account ? $account->code : 'All') . '_' . $dateFrom . '_' . $dateTo . '.pdf';

        return $exportService->exportPdf('accounting.ledger.print', array_merge($data, [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]), $filename);
    }

    protected function computeLedger($companyId, $accountId, $dateFrom, $dateTo): array
    {
        $selectedAccount = null;
        $openingBalance = 0;
        $ledgerEntries = collect();
        $totalDebit = 0;
        $totalCredit = 0;
        $closingBalance = 0;

        if ($accountId) {
            $selectedAccount = Account::where('company_id', $companyId)->find($accountId);

            if ($selectedAccount) {
                $isDebitNormal = in_array(strtolower($selectedAccount->type), ['asset', 'expense']);

                $priorSummary = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
                    ->where('journal_sets.company_id', $companyId)
                    ->where('journal_sets.status', 'posted')
                    ->where('journal_lines.account_id', $selectedAccount->id)
                    ->where('journal_sets.journal_date', '<', $dateFrom)
                    ->select(
                        DB::raw('COALESCE(SUM(journal_lines.debit), 0) as prior_debit'),
                        DB::raw('COALESCE(SUM(journal_lines.credit), 0) as prior_credit')
                    )
                    ->first();

                $priorDebit = (float) ($priorSummary->prior_debit ?? 0);
                $priorCredit = (float) ($priorSummary->prior_credit ?? 0);

                $openingBalance = $isDebitNormal 
                    ? ($priorDebit - $priorCredit) 
                    : ($priorCredit - $priorDebit);

                $rawEntries = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
                    ->where('journal_sets.company_id', $companyId)
                    ->where('journal_sets.status', 'posted')
                    ->where('journal_lines.account_id', $selectedAccount->id)
                    ->whereBetween('journal_sets.journal_date', [$dateFrom, $dateTo])
                    ->select(
                        'journal_lines.*',
                        'journal_sets.journal_number',
                        'journal_sets.journal_date',
                        'journal_sets.description as header_description',
                        'journal_sets.source_type',
                        'journal_sets.source_id'
                    )
                    ->orderBy('journal_sets.journal_date')
                    ->orderBy('journal_sets.id')
                    ->orderBy('journal_lines.id')
                    ->get();

                $runningBalance = $openingBalance;
                $ledgerEntries = $rawEntries->map(function ($entry) use (&$runningBalance, $isDebitNormal, &$totalDebit, &$totalCredit) {
                    $debit = (float) $entry->debit;
                    $credit = (float) $entry->credit;

                    $totalDebit += $debit;
                    $totalCredit += $credit;

                    if ($isDebitNormal) {
                        $runningBalance += ($debit - $credit);
                    } else {
                        $runningBalance += ($credit - $debit);
                    }

                    $entry->running_balance = $runningBalance;
                    return $entry;
                });

                $closingBalance = $runningBalance;
            }
        }

        return compact(
            'selectedAccount',
            'openingBalance',
            'ledgerEntries',
            'totalDebit',
            'totalCredit',
            'closingBalance'
        );
    }
}
