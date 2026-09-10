<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function customers(Request $request)
    {
        $customers = Contact::whereIn('type', ['customer', 'both'])->orderBy('name')->get();

        return view('master-data.customers.index', compact('customers'));
    }

    public function suppliers(Request $request)
    {
        $suppliers = Contact::whereIn('type', ['supplier', 'both'])->orderBy('name')->get();

        return view('master-data.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:customer,supplier,both',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'identity_type' => 'nullable|in:NPWP,NIK',
            'identity_number' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'payment_terms_days' => 'nullable|integer',
        ]);

        Contact::create($validated);

        $route = $validated['type'] === 'supplier' ? 'suppliers.index' : 'customers.index';

        return redirect()->route($route)->with('success', 'Kontak berhasil disimpan.');
    }

    public function downloadCustomerTemplate(\App\Services\Import\DataImportService $importService)
    {
        return $importService->downloadCustomerTemplate();
    }

    public function importCustomers(Request $request, \App\Services\Import\DataImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|extensions:xlsx,xls,csv,txt,xml|max:20480',
        ]);

        $companyId = (int) (session('active_company_id') ?: session('current_company_id'));
        $result = $importService->importCustomers($request->file('file')->getRealPath(), $companyId);

        $msg = "Impor pelanggan selesai: {$result['imported']} pelanggan baru ditambahkan, {$result['updated']} diperbarui.";
        if (!empty($result['errors'])) {
            $msg .= " Catatan: " . count($result['errors']) . " baris diabaikan karena format tidak lengkap.";
        }

        return redirect()->route('customers.index')->with('success', $msg);
    }

    public function downloadSupplierTemplate(\App\Services\Import\DataImportService $importService)
    {
        return $importService->downloadSupplierTemplate();
    }

    public function importSuppliers(Request $request, \App\Services\Import\DataImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|extensions:xlsx,xls,csv,txt,xml|max:20480',
        ]);

        $companyId = (int) (session('active_company_id') ?: session('current_company_id'));
        $result = $importService->importSuppliers($request->file('file')->getRealPath(), $companyId);

        $msg = "Impor pemasok selesai: {$result['imported']} pemasok baru ditambahkan, {$result['updated']} diperbarui.";
        if (!empty($result['errors'])) {
            $msg .= " Catatan: " . count($result['errors']) . " baris diabaikan karena format tidak lengkap.";
        }

        return redirect()->route('suppliers.index')->with('success', $msg);
    }
}
