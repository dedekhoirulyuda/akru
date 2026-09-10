<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Subscription\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlatformPlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::withCount('subscriptions')->get();

        $availableModules = [
            'core' => 'Sistem Inti & Multi-Cabang',
            'accounting' => 'Buku Besar & Jurnal Penyesuaian',
            'finance' => 'Kas & Bank Rekonsiliasi',
            'sales' => 'Penjualan & Piutang (Invoicing)',
            'purchase' => 'Pembelian & Hutang Dagang',
            'inventory' => 'Inventaris & Multi-Gudang',
            'tax' => 'Pajak & Coretax Export (PPN/PPh)',
            'ai' => 'Asisten Finansial AI (Gemini/OpenAI)',
            'converter' => 'Bank Statement PDF Converter',
            'audit' => 'Audit Trail Lengkap',
        ];

        return view('platform.plans.index', compact('plans', 'availableModules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|unique:plans,slug',
            'description' => 'nullable|string',
            'price_per_month' => 'required|numeric|min:0',
            'price_per_year' => 'nullable|numeric|min:0',
            'max_users' => 'required|integer|min:1',
            'max_branches' => 'required|integer|min:1',
            'max_transactions_per_month' => 'required|integer|min:1',
            'has_ai' => 'nullable|boolean',
            'max_ai_chats_per_day' => 'nullable|integer|min:1',
            'modules' => 'nullable|array',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['has_ai'] = $request->boolean('has_ai');
        $validated['is_active'] = true;

        Plan::create($validated);

        return redirect()->route('platform.plans.index')
            ->with('success', "Paket langganan '{$validated['name']}' berhasil dibuat.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:100|unique:plans,slug,{$plan->id}",
            'description' => 'nullable|string',
            'price_per_month' => 'required|numeric|min:0',
            'price_per_year' => 'nullable|numeric|min:0',
            'max_users' => 'required|integer|min:1',
            'max_branches' => 'required|integer|min:1',
            'max_transactions_per_month' => 'required|integer|min:1',
            'has_ai' => 'nullable|boolean',
            'max_ai_chats_per_day' => 'nullable|integer|min:1',
            'modules' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['has_ai'] = $request->boolean('has_ai');
        $validated['is_active'] = $request->boolean('is_active');

        $plan->update($validated);

        return redirect()->route('platform.plans.index')
            ->with('success', "Paket '{$plan->name}' berhasil diperbarui.");
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Paket '{$plan->name}' berhasil {$status}.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $plan = Plan::withCount('subscriptions')->findOrFail($id);

        if ($plan->subscriptions_count > 0) {
            return back()->with('error', "Tidak dapat menghapus paket '{$plan->name}' karena sedang digunakan oleh {$plan->subscriptions_count} perusahaan.");
        }

        $plan->delete();

        return redirect()->route('platform.plans.index')
            ->with('success', "Paket '{$plan->name}' berhasil dihapus.");
    }
}
