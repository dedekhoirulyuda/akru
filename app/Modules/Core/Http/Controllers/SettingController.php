<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * SettingController — manages company-wide settings and preferences.
 *
 * Blueprint §2.1: Key-value settings scoped by company_id.
 */
class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = session('active_company_id', session('current_company_id'));
        $company = Company::findOrFail($companyId);
        $settings = Setting::where('company_id', $companyId)->get()->pluck('value', 'key');

        return view('settings.index', compact('company', 'settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = session('active_company_id', session('current_company_id'));
        $company = Company::findOrFail($companyId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'settings' => 'nullable|array',
        ]);

        $company->update([
            'name' => $validated['name'],
            'legal_name' => $validated['legal_name'] ?? $validated['name'],
            'tax_id' => $validated['tax_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        if (!empty($validated['settings'])) {
            foreach ($validated['settings'] as $key => $val) {
                Setting::updateOrCreate(
                    ['company_id' => $companyId, 'key' => $key],
                    ['value' => $val]
                );
            }
        }

        session(['active_company_name' => $company->name]);

        return redirect()->route('settings.index')->with('success', 'Pengaturan profil perusahaan berhasil disimpan.');
    }
}
