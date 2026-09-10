<?php

namespace App\Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Subscription\Models\Plan;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        $companyId = session('active_company_id', session('current_company_id'));

        // Seed default starter & enterprise plans if they don't exist
        $this->ensureDefaultPlansExist();

        $subscription = Subscription::with('plan')
            ->where('company_id', $companyId)
            ->latest()
            ->first();

        $plans = Plan::orderBy('price_per_month')->get();

        // Calculate active usage
        $userCount = DB::table('company_users')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $branchCount = DB::table('branches')
            ->where('company_id', $companyId)
            ->count();

        $currentMonth = now()->format('Y-m');
        $trxCount = DB::table('journal_sets')
            ->where('company_id', $companyId)
            ->where('journal_date', 'like', "{$currentMonth}%")
            ->count();

        $company = \App\Modules\Core\Models\Company::find($companyId);
        $aiChatLimit = $company ? $company->getAiChatLimit() : null;
        $aiChatsToday = \App\Modules\Core\Models\AiChatUsage::getTodayUsage($companyId);

        $usage = [
            'users' => $userCount,
            'branches' => $branchCount,
            'transactions' => $trxCount,
            'ai_chats' => $aiChatsToday,
            'ai_limit' => $aiChatLimit,
        ];

        return view('subscription.index', compact('subscription', 'plans', 'usage'));
    }

    public function changePlan(Request $request): RedirectResponse
    {
        $companyId = session('active_company_id', session('current_company_id'));

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $isTrial = $plan->isFree() || $plan->slug === 'free-trial';
        $status = $isTrial ? 'trial' : 'active';
        $trialEndsAt = $isTrial ? now()->addDays(14) : null;

        $subscription = Subscription::where('company_id', $companyId)->latest()->first();

        if ($subscription) {
            $subscription->update([
                'plan_id' => $plan->id,
                'status' => $status,
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
            ]);
        } else {
            Subscription::create([
                'company_id' => $companyId,
                'plan_id' => $plan->id,
                'status' => $status,
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
            ]);
        }

        // Update company status if trial
        $company = \App\Modules\Core\Models\Company::find($companyId);
        if ($company && $isTrial) {
            $company->update(['status' => 'trial']);
        } elseif ($company && $company->status === 'trial') {
            $company->update(['status' => 'active']);
        }

        return redirect()->route('subscription.index')
            ->with('success', "Paket langganan berhasil diperbarui ke: {$plan->name}.");
    }

    private function ensureDefaultPlansExist(): void
    {
        if (!Plan::where('slug', 'free-trial')->exists()) {
            Plan::create([
                'name' => 'AKRU Free Trial (Uji Coba Gratis)',
                'slug' => 'free-trial',
                'description' => 'Akses uji coba fitur lengkap AKRU termasuk Asisten Finansial AI untuk mengevaluasi sistem bisnis Anda',
                'price_per_month' => 0,
                'price_per_year' => 0,
                'max_users' => 5,
                'max_branches' => 2,
                'max_transactions_per_month' => 500,
                'has_ai' => true,
                'max_ai_chats_per_day' => 10,
                'modules' => ['core', 'accounting', 'finance', 'sales', 'purchase', 'inventory', 'tax', 'ai', 'converter', 'audit'],
                'is_active' => true,
            ]);
        }

        if (!Plan::where('slug', 'starter')->exists()) {
            Plan::create([
                'name' => 'AKRU Starter (UKM Berkembang)',
                'slug' => 'starter',
                'description' => 'Solusi esensial pembukuan kas, faktur penjualan, dan pencatatan inventaris dasar',
                'price_per_month' => 99000,
                'max_users' => 3,
                'max_branches' => 1,
                'max_transactions_per_month' => 1000,
                'has_ai' => false,
            ]);
        }

        if (!Plan::where('slug', 'enterprise')->exists()) {
            Plan::create([
                'name' => 'AKRU Enterprise (Multi-Entitas & AI)',
                'slug' => 'enterprise',
                'description' => 'Skala korporasi tanpa batas cabang, integrasi Coretax ERP, konsolidasi grup & asisten AI',
                'price_per_month' => 799000,
                'max_users' => 50,
                'max_branches' => 20,
                'max_transactions_per_month' => 50000,
                'has_ai' => true,
            ]);
        }
    }
}
