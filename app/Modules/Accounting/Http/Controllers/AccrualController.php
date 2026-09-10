<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\AccrualSchedule;
use App\Modules\Accounting\Models\AccrualScheduleLine;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Models\JournalSet;
use App\Modules\MasterData\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccrualController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $schedules = AccrualSchedule::with(['prepaidAccount', 'targetAccount', 'lines'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        $assetAccounts = Account::where('company_id', $companyId)->where('type', 'asset')->get();
        $expenseAccounts = Account::where('company_id', $companyId)->where('type', 'expense')->get();

        return view('accounting.accruals.index', compact('schedules', 'assetAccounts', 'expenseAccounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:prepaid_expense,accrued_expense',
            'prepaid_account_id' => 'required|exists:accounts,id',
            'target_account_id' => 'required|exists:accounts,id|different:prepaid_account_id',
            'total_amount' => 'required|numeric|min:1000',
            'start_date' => 'required|date',
            'periods_count' => 'required|integer|min:1|max:60',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $periods = (int) $request->periods_count;
            $totalAmount = (float) $request->total_amount;
            $amountPerPeriod = round($totalAmount / $periods, 2);
            $startDate = Carbon::parse($request->start_date);
            $endDate = $startDate->copy()->addMonths($periods - 1)->endOfMonth();

            $scheduleNumber = 'ACC-' . date('Ym') . '-' . str_pad(AccrualSchedule::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $schedule = AccrualSchedule::create([
                'company_id' => $companyId,
                'schedule_number' => $scheduleNumber,
                'name' => $request->name,
                'type' => $request->type,
                'prepaid_account_id' => $request->prepaid_account_id,
                'target_account_id' => $request->target_account_id,
                'total_amount' => $totalAmount,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'periods_count' => $periods,
                'amount_per_period' => $amountPerPeriod,
                'status' => 'active',
            ]);

            for ($i = 1; $i <= $periods; $i++) {
                $lineDate = $startDate->copy()->addMonths($i - 1)->endOfMonth();
                AccrualScheduleLine::create([
                    'accrual_schedule_id' => $schedule->id,
                    'period_number' => $i,
                    'schedule_date' => $lineDate->toDateString(),
                    'amount' => $amountPerPeriod,
                    'status' => 'pending',
                ]);
            }
        });

        return redirect()->route('accruals.index')->with('success', 'Jadwal amortisasi beban dibayar di muka berhasil dibuat.');
    }

    public function processPeriod($id, $lineId)
    {
        $companyId = session('current_company_id');
        $schedule = AccrualSchedule::where('company_id', $companyId)->findOrFail($id);
        $line = AccrualScheduleLine::where('accrual_schedule_id', $schedule->id)->findOrFail($lineId);

        if ($line->status === 'posted') {
            return back()->with('error', 'Periode ini sudah diposting sebelumnya.');
        }

        DB::transaction(function () use ($schedule, $line, $companyId) {
            $journalNumber = 'JRN-' . date('Ym') . '-' . str_pad(JournalSet::where('company_id', $companyId)->count() + 1, 5, '0', STR_PAD_LEFT);

            $journal = JournalSet::create([
                'company_id' => $companyId,
                'journal_number' => $journalNumber,
                'journal_date' => $line->schedule_date,
                'source_type' => 'accrual_schedule',
                'source_id' => $schedule->id,
                'description' => "Amortisasi Beban: {$schedule->name} (Periode {$line->period_number}/{$schedule->periods_count})",
                'status' => 'posted',
                'total_debit' => $line->amount,
                'total_credit' => $line->amount,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            // Debit target expense account
            JournalLine::create([
                'journal_set_id' => $journal->id,
                'account_id' => $schedule->target_account_id,
                'description' => $schedule->name,
                'debit' => $line->amount,
                'credit' => 0,
                'line_order' => 1,
            ]);

            // Credit prepaid asset account
            JournalLine::create([
                'journal_set_id' => $journal->id,
                'account_id' => $schedule->prepaid_account_id,
                'description' => $schedule->name,
                'debit' => 0,
                'credit' => $line->amount,
                'line_order' => 2,
            ]);

            $line->update([
                'status' => 'posted',
                'journal_set_id' => $journal->id,
            ]);
        });

        return redirect()->route('accruals.index')->with('success', "Jurnal amortisasi periode {$line->period_number} berhasil diposting ke Buku Besar.");
    }
}
