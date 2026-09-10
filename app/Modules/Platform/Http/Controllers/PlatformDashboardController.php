<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Platform\Models\SubscriptionInvoice;
use App\Modules\Subscription\Models\Plan;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    public function index(): View
    {
        // 1. Tenant Metrics
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $trialCompanies = Company::where('status', 'trial')->count();
        $suspendedCompanies = Company::where('status', 'suspended')->count();

        // 2. User Metrics
        $totalUsers = User::count();
        $superAdminCount = User::where('is_superadmin', true)->count();

        // 3. Subscription & Revenue Metrics
        $subscriptions = Subscription::with('plan')->get();
        
        $mrr = 0;
        foreach ($subscriptions as $sub) {
            if ($sub->isActive() && $sub->plan) {
                if ($sub->billing_cycle === 'yearly' && $sub->plan->price_per_year) {
                    $mrr += ($sub->plan->price_per_year / 12);
                } else {
                    $mrr += $sub->plan->price_per_month;
                }
            }
        }
        $arr = $mrr * 12;

        // Current Month Revenue (Paid Invoices this month)
        $currentMonth = now()->format('Y-m');
        $monthlyRevenue = SubscriptionInvoice::where('status', 'paid')
            ->where('paid_at', 'like', "{$currentMonth}%")
            ->sum('amount');

        // Unpaid Revenue (Outstanding / Pending Invoices)
        $unpaidRevenue = SubscriptionInvoice::where('status', 'unpaid')->sum('amount');
        $unpaidInvoiceCount = SubscriptionInvoice::where('status', 'unpaid')->count();

        // All-Time Revenue (Total Collected Lifetime)
        $allTimeRevenue = SubscriptionInvoice::where('status', 'paid')->sum('amount');

        // 4. Activity & Platform Transactions
        $totalTransactionsThisMonth = DB::table('journal_sets')
            ->where('journal_date', 'like', "{$currentMonth}%")
            ->count();

        // 5. Recent Companies & Invoices
        $recentCompanies = Company::with(['subscription.plan'])
            ->latest()
            ->take(5)
            ->get();

        $recentInvoices = SubscriptionInvoice::with(['company', 'plan'])
            ->latest()
            ->take(5)
            ->get();

        // 6. Detailed Analysis per Subscription Plan
        $plans = Plan::withCount('subscriptions')->get();
        $planDetails = [];
        $totalActiveSubscribers = 0;
        $totalTrialSubscribers = 0;
        $totalPlanMrr = 0;
        $totalPlanCollected = 0;
        $totalPlanUnpaid = 0;

        foreach ($plans as $plan) {
            $activeCount = Subscription::where('plan_id', $plan->id)
                ->where('status', 'active')
                ->count();
            
            $trialCount = Subscription::where('plan_id', $plan->id)
                ->where('status', 'trial')
                ->count();

            // Calculate MRR contribution of this plan
            $planSubs = Subscription::where('plan_id', $plan->id)->get();
            $planMrr = 0;
            foreach ($planSubs as $ps) {
                if ($ps->isActive()) {
                    if ($ps->billing_cycle === 'yearly' && $plan->price_per_year) {
                        $planMrr += ($plan->price_per_year / 12);
                    } else {
                        $planMrr += $plan->price_per_month;
                    }
                }
            }

            // Invoiced revenue for this plan
            $planCollected = SubscriptionInvoice::where('plan_id', $plan->id)
                ->where('status', 'paid')
                ->sum('amount');

            $planUnpaid = SubscriptionInvoice::where('plan_id', $plan->id)
                ->where('status', 'unpaid')
                ->sum('amount');

            $mrrShare = $mrr > 0 ? round(($planMrr / $mrr) * 100, 1) : 0;

            $totalActiveSubscribers += $activeCount;
            $totalTrialSubscribers += $trialCount;
            $totalPlanMrr += $planMrr;
            $totalPlanCollected += $planCollected;
            $totalPlanUnpaid += $planUnpaid;

            $planDetails[] = [
                'plan' => $plan,
                'active_count' => $activeCount,
                'trial_count' => $trialCount,
                'monthly_rate' => $plan->price_per_month,
                'yearly_rate' => $plan->price_per_year,
                'mrr' => $planMrr,
                'mrr_share' => $mrrShare,
                'collected' => $planCollected,
                'unpaid' => $planUnpaid,
            ];
        }

        // Totals summary across all plans
        $totalPlansSummary = [
            'total_plans' => $plans->count(),
            'active_subscribers' => $totalActiveSubscribers,
            'trial_subscribers' => $totalTrialSubscribers,
            'total_mrr' => $totalPlanMrr,
            'total_arr' => $totalPlanMrr * 12,
            'total_collected' => $totalPlanCollected,
            'total_unpaid' => $totalPlanUnpaid,
        ];

        return view('platform.dashboard', compact(
            'totalCompanies',
            'activeCompanies',
            'trialCompanies',
            'suspendedCompanies',
            'totalUsers',
            'superAdminCount',
            'mrr',
            'arr',
            'monthlyRevenue',
            'unpaidRevenue',
            'unpaidInvoiceCount',
            'allTimeRevenue',
            'totalTransactionsThisMonth',
            'recentCompanies',
            'recentInvoices',
            'plans',
            'planDetails',
            'totalPlansSummary'
        ));
    }
}
