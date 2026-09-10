<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $bankAccounts = BankAccount::with('account')->get();
        $cashAccounts = Account::where('type', 'asset')
            ->whereIn('sub_type', ['current_asset'])
            ->orderBy('code')
            ->get();

        return view('master-data.bank-accounts.index', compact('bankAccounts', 'cashAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder_name' => 'nullable|string|max:255',
        ]);

        $validated['currency_code'] = 'IDR';
        $validated['is_active'] = true;

        BankAccount::create($validated);

        return redirect()->route('bank-accounts.index')->with('success', 'Rekening Kas & Bank berhasil ditambahkan.');
    }
}
