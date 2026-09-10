<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::orderBy('code')->get();

        $grouped = [
            'asset' => $accounts->where('type', 'asset'),
            'liability' => $accounts->where('type', 'liability'),
            'equity' => $accounts->where('type', 'equity'),
            'revenue' => $accounts->where('type', 'revenue'),
            'cogs' => $accounts->where('type', 'cogs'),
            'expense' => $accounts->where('type', 'expense'),
        ];

        return view('master-data.coa.index', compact('accounts', 'grouped'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,cogs,expense',
            'normal_balance' => 'required|in:debit,credit',
            'sub_type' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        Account::create($validated);

        return redirect()->route('coa.index')->with('success', 'Akun berhasil ditambahkan ke Bagan Akun.');
    }

    public function downloadTemplate(\App\Services\Import\DataImportService $importService)
    {
        return $importService->downloadCoaTemplate();
    }

    public function import(Request $request, \App\Services\Import\DataImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|extensions:xlsx,xls,csv,txt,xml|max:20480',
        ]);

        $companyId = (int) (session('active_company_id') ?: session('current_company_id'));
        $result = $importService->importCoa($request->file('file')->getRealPath(), $companyId);

        $msg = "Impor selesai: {$result['imported']} akun baru ditambahkan, {$result['updated']} akun diperbarui.";
        if (!empty($result['errors'])) {
            $msg .= " Catatan: " . count($result['errors']) . " baris diabaikan karena format tidak lengkap.";
        }

        return redirect()->route('coa.index')->with('success', $msg);
    }
}
