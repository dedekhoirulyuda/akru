<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\Identity\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CompanyController — handles company listing, creation, and active company switching.
 *
 * Blueprint §2.1: Multi-entity / multi-company context switching.
 */
class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $companies = $user ? $user->companies()->withCount('branches')->get() : collect();
        $activeCompanyId = session('active_company_id', session('current_company_id'));

        return view('settings.companies', compact('companies', 'activeCompanyId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:30', // NPWP 16 digit
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
        ]);

        DB::transaction(function () use ($validated, $user) {
            $company = Company::create([
                'name' => $validated['name'],
                'legal_name' => $validated['legal_name'] ?? $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                'tax_id' => $validated['tax_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'is_active' => true,
            ]);

            // Create default head office branch
            Branch::create([
                'company_id' => $company->id,
                'name' => 'Kantor Pusat',
                'code' => 'PST',
                'address' => $validated['address'] ?? null,
                'is_head_office' => true,
                'is_active' => true,
            ]);

            // Find or create Owner role
            $role = Role::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Owner'],
                ['description' => 'Pemilik Perusahaan (Full Access)']
            );

            // Attach user to company
            $user->companies()->attach($company->id, [
                'role_id' => $role->id,
                'is_active' => true,
            ]);

            // Set as active
            session([
                'active_company_id' => $company->id,
                'current_company_id' => $company->id,
                'active_company_name' => $company->name,
            ]);
        });

        return redirect()->route('companies.index')->with('success', 'Entitas perusahaan baru berhasil ditambahkan.');
    }

    public function switch(Request $request, Company $company): RedirectResponse
    {
        $user = $request->user();

        // Verify user has access to this company
        if (!$user->companies()->where('companies.id', $company->id)->exists()) {
            abort(403, 'Anda tidak memiliki otorisasi untuk mengakses entitas perusahaan ini.');
        }

        session([
            'active_company_id' => $company->id,
            'current_company_id' => $company->id,
            'active_company_name' => $company->name,
        ]);
        session()->forget('current_branch_id');

        return redirect()->route('dashboard.index')
            ->with('success', "Berhasil beralih ke entitas {$company->name}.");
    }
}
