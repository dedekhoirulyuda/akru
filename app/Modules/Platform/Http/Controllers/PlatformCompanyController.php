<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\Subscription\Models\Plan;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformCompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::with(['subscription.plan'])
            ->withCount(['branches', 'users']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('legal_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('npwp', 'like', "%{$search}%");
            });
        }

        $companies = $query->latest()->paginate(15)->withQueryString();
        $plans = Plan::where('is_active', true)->get();

        return view('platform.companies.index', compact('companies', 'plans'));
    }

    public function show(int $id): View
    {
        $company = Company::with([
            'subscription.plan',
            'branches',
            'users'
        ])->findOrFail($id);

        $plans = Plan::all();

        // Transaction stats
        $currentMonth = now()->format('Y-m');
        $monthlyTxCount = DB::table('journal_sets')
            ->where('company_id', $company->id)
            ->where('journal_date', 'like', "{$currentMonth}%")
            ->count();

        $totalTxCount = DB::table('journal_sets')
            ->where('company_id', $company->id)
            ->count();

        return view('platform.companies.show', compact('company', 'plans', 'monthlyTxCount', 'totalTxCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'entity_type' => 'required|string|in:PT,CV,Perorangan,Yayasan,Koperasi',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:active,trial,suspended',
        ]);

        DB::transaction(function () use ($validated) {
            $company = Company::create([
                'name' => $validated['name'],
                'legal_name' => $validated['legal_name'] ?? $validated['name'],
                'entity_type' => $validated['entity_type'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'city' => $validated['city'] ?? null,
                'status' => $validated['status'],
            ]);

            // Create default branch
            Branch::create([
                'company_id' => $company->id,
                'name' => 'Kantor Pusat',
                'code' => 'HQ',
                'is_head_office' => true,
                'is_active' => true,
            ]);

            // Create subscription
            Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $validated['plan_id'],
                'status' => $validated['status'],
                'starts_at' => now(),
                'ends_at' => $validated['status'] === 'trial' ? now()->addDays(14) : now()->addMonth(),
                'trial_ends_at' => $validated['status'] === 'trial' ? now()->addDays(14) : null,
            ]);
        });

        return redirect()->route('platform.companies.index')
            ->with('success', "Perusahaan '{$validated['name']}' berhasil didaftarkan di platform.");
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,trial,suspended',
            'suspended_reason' => 'nullable|string|max:500',
        ]);

        $company->update([
            'status' => $validated['status'],
            'suspended_reason' => $validated['status'] === 'suspended' ? ($validated['suspended_reason'] ?? 'Ditangguhkan oleh Platform Owner') : null,
        ]);

        // Synchronize subscription status
        if ($company->subscription) {
            $company->subscription->update([
                'status' => $validated['status'],
            ]);
        }

        $label = match($validated['status']) {
            'active' => 'diaktifkan kembali',
            'suspended' => 'ditangguhkan (suspend)',
            'trial' => 'diubah ke status trial',
            default => 'diperbarui',
        };

        return back()->with('success', "Status perusahaan {$company->name} berhasil {$label}.");
    }

    public function updateQuota(Request $request, int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'max_users_override' => 'nullable|integer|min:1',
            'max_branches_override' => 'nullable|integer|min:1',
            'max_transactions_override' => 'nullable|integer|min:1',
        ]);

        $company->update($validated);

        return back()->with('success', "Kuota khusus untuk perusahaan {$company->name} berhasil disimpan.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);
        $name = $company->name;
        $company->delete();

        return redirect()->route('platform.companies.index')
            ->with('success', "Perusahaan {$name} berhasil diarsipkan.");
    }
}
