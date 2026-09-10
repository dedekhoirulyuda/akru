<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Models\PettyCashFund;
use App\Modules\Finance\Models\PettyCashVoucher;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PettyCashController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $funds = PettyCashFund::with(['bankAccount', 'custodian', 'vouchers'])
            ->where('company_id', $companyId)
            ->get();

        $vouchers = PettyCashVoucher::with(['pettyCashFund', 'expenseAccount', 'creator'])
            ->whereHas('pettyCashFund', fn($q) => $q->where('company_id', $companyId))
            ->latest()
            ->paginate(15);

        $bankAccounts = BankAccount::where('company_id', $companyId)->where('is_active', true)->get();
        $expenseAccounts = Account::where('company_id', $companyId)->where('type', 'expense')->get();
        $users = User::where('company_id', $companyId)->get();

        return view('finance.petty_cash.index', compact('funds', 'vouchers', 'bankAccounts', 'expenseAccounts', 'users'));
    }

    public function storeFund(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'fund_name' => 'required|string|max:100',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'imprest_amount' => 'required|numeric|min:100000',
            'custodian_id' => 'nullable|exists:users,id',
        ]);

        PettyCashFund::create([
            'company_id' => $companyId,
            'fund_name' => $request->fund_name,
            'bank_account_id' => $request->bank_account_id,
            'imprest_amount' => $request->imprest_amount,
            'current_balance' => $request->imprest_amount, // Awal terbentuk terisi penuh
            'custodian_id' => $request->custodian_id ?? auth()->id(),
            'is_active' => true,
        ]);

        return redirect()->route('petty-cash.index')->with('success', 'Dana Kas Kecil (Petty Cash Imprest) berhasil didaftarkan.');
    }

    public function storeVoucher(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'petty_cash_fund_id' => 'required|exists:petty_cash_funds,id',
            'voucher_date' => 'required|date',
            'recipient_name' => 'required|string|max:100',
            'expense_account_id' => 'required|exists:accounts,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1000',
        ]);

        $fund = PettyCashFund::where('company_id', $companyId)->findOrFail($request->petty_cash_fund_id);

        if ($fund->current_balance < $request->amount) {
            return back()->with('error', 'Saldo kas kecil tidak mencukupi untuk pengeluaran ini. Silakan lakukan pengisian kembali (replenishment).');
        }

        DB::transaction(function () use ($request, $fund) {
            $voucherNumber = 'PCV-' . date('Ym') . '-' . str_pad(PettyCashVoucher::where('petty_cash_fund_id', $fund->id)->count() + 1, 4, '0', STR_PAD_LEFT);

            PettyCashVoucher::create([
                'petty_cash_fund_id' => $fund->id,
                'voucher_number' => $voucherNumber,
                'voucher_date' => $request->voucher_date,
                'type' => 'expense',
                'expense_account_id' => $request->expense_account_id,
                'recipient_name' => $request->recipient_name,
                'description' => $request->description,
                'amount' => $request->amount,
                'status' => 'approved',
                'created_by' => auth()->id(),
            ]);

            $fund->decrement('current_balance', $request->amount);
        });

        return redirect()->route('petty-cash.index')->with('success', 'Pengeluaran kas kecil berhasil dicatat.');
    }

    public function replenish(Request $request, $id)
    {
        $companyId = session('current_company_id');
        $fund = PettyCashFund::where('company_id', $companyId)->findOrFail($id);

        $neededAmount = $fund->imprest_amount - $fund->current_balance;

        if ($neededAmount <= 0) {
            return back()->with('info', 'Saldo kas kecil masih penuh sesuai plafon imprest.');
        }

        DB::transaction(function () use ($fund, $neededAmount) {
            $voucherNumber = 'PCR-' . date('Ym') . '-' . str_pad(PettyCashVoucher::where('petty_cash_fund_id', $fund->id)->count() + 1, 4, '0', STR_PAD_LEFT);

            PettyCashVoucher::create([
                'petty_cash_fund_id' => $fund->id,
                'voucher_number' => $voucherNumber,
                'voucher_date' => now()->toDateString(),
                'type' => 'replenishment',
                'description' => "Pengisian kembali kas kecil ke plafon normal",
                'amount' => $neededAmount,
                'status' => 'reimbursed',
                'created_by' => auth()->id(),
            ]);

            $fund->update(['current_balance' => $fund->imprest_amount]);
        });

        return redirect()->route('petty-cash.index')->with('success', "Kas kecil telah diisi kembali sebesar Rp " . number_format($neededAmount, 0, ',', '.') . " ke plafon semula.");
    }
}
