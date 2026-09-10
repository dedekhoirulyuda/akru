<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\TaxCode;
use Illuminate\Http\Request;

class TaxCodeController extends Controller
{
    public function index(Request $request)
    {
        $taxCodes = TaxCode::with(['salesAccount', 'purchaseAccount'])->get();
        $accounts = Account::orderBy('code')->get();

        return view('master-data.tax-codes.index', compact('taxCodes', 'accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'tax_type' => 'required|in:PPN,PPH21,PPH22,PPH23,PPH4_2,PPH25',
            'rate' => 'required|numeric|min:0|max:100',
            'sales_account_id' => 'nullable|exists:accounts,id',
            'purchase_account_id' => 'nullable|exists:accounts,id',
        ]);

        $validated['is_active'] = true;

        TaxCode::create($validated);

        return redirect()->route('tax-codes.index')->with('success', 'Kode Pajak berhasil ditambahkan.');
    }
}
