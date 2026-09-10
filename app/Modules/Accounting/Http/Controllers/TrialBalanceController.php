<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\MasterData\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TrialBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = session('active_company_id');

        $dateFrom = $request->query('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // 1. Prior movements (before dateFrom)
        $priorMovements = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->where('journal_sets.journal_date', '<', $dateFrom)
            ->groupBy('journal_lines.account_id')
            ->select(
                'journal_lines.account_id',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit')
            )
            ->get()
            ->keyBy('account_id');

        // 2. Period movements (between dateFrom and dateTo)
        $periodMovements = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereBetween('journal_sets.journal_date', [$dateFrom, $dateTo])
            ->groupBy('journal_lines.account_id')
            ->select(
                'journal_lines.account_id',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit')
            )
            ->get()
            ->keyBy('account_id');

        $rows = [];
        $grandOpeningDebit = 0;
        $grandOpeningCredit = 0;
        $grandPeriodDebit = 0;
        $grandPeriodCredit = 0;
        $grandClosingDebit = 0;
        $grandClosingCredit = 0;

        foreach ($accounts as $acc) {
            $prior = $priorMovements->get($acc->id);
            $priorD = (float) ($prior->total_debit ?? 0);
            $priorC = (float) ($prior->total_credit ?? 0);

            $period = $periodMovements->get($acc->id);
            $periodD = (float) ($period->total_debit ?? 0);
            $periodC = (float) ($period->total_credit ?? 0);

            $netPrior = $priorD - $priorC;
            $openDebit = $netPrior > 0 ? $netPrior : 0;
            $openCredit = $netPrior < 0 ? abs($netPrior) : 0;

            $netClose = ($priorD + $periodD) - ($priorC + $periodC);
            $closeDebit = $netClose > 0 ? $netClose : 0;
            $closeCredit = $netClose < 0 ? abs($netClose) : 0;

            // Only show accounts that have non-zero opening, period, or closing balances
            if ($openDebit == 0 && $openCredit == 0 && $periodD == 0 && $periodC == 0 && $closeDebit == 0 && $closeCredit == 0) {
                continue;
            }

            $rows[] = [
                'account' => $acc,
                'opening_debit' => $openDebit,
                'opening_credit' => $openCredit,
                'period_debit' => $periodD,
                'period_credit' => $periodC,
                'closing_debit' => $closeDebit,
                'closing_credit' => $closeCredit,
            ];

            $grandOpeningDebit += $openDebit;
            $grandOpeningCredit += $openCredit;
            $grandPeriodDebit += $periodD;
            $grandPeriodCredit += $periodC;
            $grandClosingDebit += $closeDebit;
            $grandClosingCredit += $closeCredit;
        }

        $isBalanced = abs($grandClosingDebit - $grandClosingCredit) < 0.01;

        $data = $this->computeTrialBalance($companyId, $dateFrom, $dateTo);

        return view('accounting.trial-balance.index', array_merge($data, [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $dateFrom = $request->query('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $data = $this->computeTrialBalance($companyId, $dateFrom, $dateTo);

        $headers = [
            'Kode Akun',
            'Nama Akun Perkiraan',
            'Tipe Akun',
            'Saldo Awal Debit (Rp)',
            'Saldo Awal Kredit (Rp)',
            'Mutasi Debit (Rp)',
            'Mutasi Kredit (Rp)',
            'Saldo Akhir Debit (Rp)',
            'Saldo Akhir Kredit (Rp)'
        ];

        $rows = [];
        foreach ($data['rows'] as $r) {
            $acc = $r['account'];
            $rows[] = [
                $acc->code,
                $acc->name,
                ucfirst($acc->type),
                number_format($r['opening_debit'], 0, ',', '.'),
                number_format($r['opening_credit'], 0, ',', '.'),
                number_format($r['period_debit'], 0, ',', '.'),
                number_format($r['period_credit'], 0, ',', '.'),
                number_format($r['closing_debit'], 0, ',', '.'),
                number_format($r['closing_credit'], 0, ',', '.'),
            ];
        }

        // Summary Total Row
        $rows[] = [
            'TOTAL',
            'TOTAL SELURUH AKUN',
            $data['isBalanced'] ? 'BALANCE (SEIMBANG)' : 'TIDAK SEIMBANG',
            number_format($data['grandOpeningDebit'], 0, ',', '.'),
            number_format($data['grandOpeningCredit'], 0, ',', '.'),
            number_format($data['grandPeriodDebit'], 0, ',', '.'),
            number_format($data['grandPeriodCredit'], 0, ',', '.'),
            number_format($data['grandClosingDebit'], 0, ',', '.'),
            number_format($data['grandClosingCredit'], 0, ',', '.'),
        ];

        $meta = [
            'title' => 'NERACA SALDO (TRIAL BALANCE)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => date('d/m/Y', strtotime($dateFrom)) . ' s/d ' . date('d/m/Y', strtotime($dateTo)),
            'date' => now()->format('d/m/Y H:i'),
        ];

        $filename = 'Neraca_Saldo_' . str_replace('-', '', $dateFrom) . '_' . str_replace('-', '', $dateTo);

        return $exportService->exportXlsx($filename, $headers, $rows, [14, 32, 16, 20, 20, 20, 20, 22, 22], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $dateFrom = $request->query('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $data = $this->computeTrialBalance($companyId, $dateFrom, $dateTo);

        $filename = 'Neraca_Saldo_' . $dateFrom . '_' . $dateTo . '.pdf';

        return $exportService->exportPdf('accounting.trial-balance.print', array_merge($data, [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]), $filename);
    }

    protected function computeTrialBalance($companyId, $dateFrom, $dateTo): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $priorMovements = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->where('journal_sets.journal_date', '<', $dateFrom)
            ->groupBy('journal_lines.account_id')
            ->select(
                'journal_lines.account_id',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit')
            )
            ->get()
            ->keyBy('account_id');

        $periodMovements = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereBetween('journal_sets.journal_date', [$dateFrom, $dateTo])
            ->groupBy('journal_lines.account_id')
            ->select(
                'journal_lines.account_id',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit')
            )
            ->get()
            ->keyBy('account_id');

        $rows = [];
        $grandOpeningDebit = 0;
        $grandOpeningCredit = 0;
        $grandPeriodDebit = 0;
        $grandPeriodCredit = 0;
        $grandClosingDebit = 0;
        $grandClosingCredit = 0;

        foreach ($accounts as $acc) {
            $prior = $priorMovements->get($acc->id);
            $priorD = (float) ($prior->total_debit ?? 0);
            $priorC = (float) ($prior->total_credit ?? 0);

            $period = $periodMovements->get($acc->id);
            $periodD = (float) ($period->total_debit ?? 0);
            $periodC = (float) ($period->total_credit ?? 0);

            $netPrior = $priorD - $priorC;
            $openDebit = $netPrior > 0 ? $netPrior : 0;
            $openCredit = $netPrior < 0 ? abs($netPrior) : 0;

            $netClose = ($priorD + $periodD) - ($priorC + $periodC);
            $closeDebit = $netClose > 0 ? $netClose : 0;
            $closeCredit = $netClose < 0 ? abs($netClose) : 0;

            if ($openDebit == 0 && $openCredit == 0 && $periodD == 0 && $periodC == 0 && $closeDebit == 0 && $closeCredit == 0) {
                continue;
            }

            $rows[] = [
                'account' => $acc,
                'opening_debit' => $openDebit,
                'opening_credit' => $openCredit,
                'period_debit' => $periodD,
                'period_credit' => $periodC,
                'closing_debit' => $closeDebit,
                'closing_credit' => $closeCredit,
            ];

            $grandOpeningDebit += $openDebit;
            $grandOpeningCredit += $openCredit;
            $grandPeriodDebit += $periodD;
            $grandPeriodCredit += $periodC;
            $grandClosingDebit += $closeDebit;
            $grandClosingCredit += $closeCredit;
        }

        $isBalanced = abs($grandClosingDebit - $grandClosingCredit) < 0.01;

        return compact(
            'rows',
            'grandOpeningDebit',
            'grandOpeningCredit',
            'grandPeriodDebit',
            'grandPeriodCredit',
            'grandClosingDebit',
            'grandClosingCredit',
            'isBalanced'
        );
    }
}
