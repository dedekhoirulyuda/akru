<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::with(['inventoryBalances', 'salesAccount'])->orderBy('name')->get();
        $accounts = Account::orderBy('code')->get();

        return view('master-data.products.index', compact('items', 'accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:goods,service',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'sales_account_id' => 'nullable|exists:accounts,id',
            'cogs_account_id' => 'nullable|exists:accounts,id',
            'inventory_account_id' => 'nullable|exists:accounts,id',
        ]);

        $validated['is_stockable'] = $validated['type'] === 'goods';
        $validated['is_active'] = true;

        Item::create($validated);

        return redirect()->route('products.index')->with('success', 'Produk / Jasa berhasil ditambahkan.');
    }

    public function downloadTemplate(\App\Services\Import\DataImportService $importService)
    {
        return $importService->downloadProductTemplate();
    }

    public function import(Request $request, \App\Services\Import\DataImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|extensions:xlsx,xls,csv,txt,xml|max:20480',
        ]);

        $companyId = (int) (session('active_company_id') ?: session('current_company_id'));
        $result = $importService->importProducts($request->file('file')->getRealPath(), $companyId);

        $msg = "Impor produk & jasa selesai: {$result['imported']} item baru ditambahkan, {$result['updated']} diperbarui.";
        if (!empty($result['errors'])) {
            $msg .= " Catatan: " . count($result['errors']) . " baris diabaikan karena format tidak lengkap.";
        }

        return redirect()->route('products.index')->with('success', $msg);
    }
}
