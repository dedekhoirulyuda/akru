<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Sales\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashFlowForecastController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $today = Carbon::today();

        // 1. Current Cash & Bank Balance
        $currentCash = (float) JournalLine::whereHas('journalSet', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->where('status', 'posted');
        })->whereHas('account', function ($q) {
            $q->where('type', 'asset')->where(function ($sq) {
                $sq->where('code', 'like', '1-101%')->orWhere('code', 'like', '1-102%');
            });
        })->sum(DB::raw('debit - credit'));

        // Forecast intervals: 30 days, 60 days, 90 days
        $intervals = [
            '30_days' => [
                'label' => '1 - 30 Hari Ke Depan',
                'start' => $today->copy(),
                'end' => $today->copy()->addDays(30),
            ],
            '60_days' => [
                'label' => '31 - 60 Hari Ke Depan',
                'start' => $today->copy()->addDays(31),
                'end' => $today->copy()->addDays(60),
            ],
            '90_days' => [
                'label' => '61 - 90 Hari Ke Depan',
                'start' => $today->copy()->addDays(61),
                'end' => $today->copy()->addDays(90),
            ],
        ];

        $forecast = [];
        $runningBalance = $currentCash;

        foreach ($intervals as $key => $interval) {
            $expectedAR = (float) SalesInvoice::where('company_id', $companyId)
                ->whereIn('status', ['posted', 'partially_paid'])
                ->whereBetween('due_date', [$interval['start']->toDateString(), $interval['end']->toDateString()])
                ->sum('remaining_amount');

            $expectedAP = (float) PurchaseInvoice::where('company_id', $companyId)
                ->whereIn('status', ['posted', 'partially_paid'])
                ->whereBetween('due_date', [$interval['start']->toDateString(), $interval['end']->toDateString()])
                ->sum('remaining_amount');

            $netFlow = $expectedAR - $expectedAP;
            $runningBalance += $netFlow;

            $forecast[$key] = [
                'label' => $interval['label'],
                'date_range' => $interval['start']->format('d M') . ' - ' . $interval['end']->format('d M Y'),
                'expected_inflow' => $expectedAR,
                'expected_outflow' => $expectedAP,
                'net_flow' => $netFlow,
                'projected_balance' => $runningBalance,
            ];
        }

        return view('finance.forecast.index', compact('currentCash', 'forecast'));
    }
}
