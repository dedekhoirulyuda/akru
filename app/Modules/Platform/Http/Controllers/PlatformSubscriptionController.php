<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Company;
use App\Modules\Platform\Models\SubscriptionInvoice;
use App\Modules\Subscription\Models\Plan;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlatformSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptionsQuery = Subscription::with(['company', 'plan']);

        if ($request->filled('status')) {
            $subscriptionsQuery->where('status', $request->status);
        }

        $subscriptions = $subscriptionsQuery->latest()->paginate(15, ['*'], 'sub_page');

        $invoices = SubscriptionInvoice::with(['company', 'plan'])
            ->latest()
            ->paginate(15, ['*'], 'inv_page');

        $companies = Company::orderBy('name')->get();
        $plans = Plan::where('is_active', true)->get();

        return view('platform.subscriptions.index', compact(
            'subscriptions',
            'invoices',
            'companies',
            'plans'
        ));
    }

    public function extend(Request $request, int $id): RedirectResponse
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'months' => 'required|integer|min:1|max:36',
            'status' => 'required|in:active,trial,grace',
        ]);

        $currentEnd = $subscription->ends_at ?: now();
        if ($currentEnd->isPast()) {
            $currentEnd = now();
        }

        $newEnd = $currentEnd->copy()->addMonths((int) $validated['months']);

        $subscription->update([
            'status' => $validated['status'],
            'ends_at' => $newEnd,
        ]);

        // Also ensure company status is active if set to active
        if ($validated['status'] === 'active' && $subscription->company) {
            $subscription->company->update(['status' => 'active']);
        }

        return back()->with('success', "Masa aktif langganan {$subscription->company->name} berhasil diperpanjang {$validated['months']} bulan hingga " . $newEnd->format('d/m/Y') . ".");
    }

    public function changePlan(Request $request, int $id): RedirectResponse
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $subscription->update([
            'plan_id' => $plan->id,
        ]);

        return back()->with('success', "Paket langganan {$subscription->company->name} berhasil diubah ke {$plan->name}.");
    }

    public function createInvoice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'plan_id' => 'required|exists:plans,id',
            'amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $invoiceNumber = 'INV-SUB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        SubscriptionInvoice::create([
            'invoice_number' => $invoiceNumber,
            'company_id' => $validated['company_id'],
            'plan_id' => $validated['plan_id'],
            'amount' => $validated['amount'],
            'billing_cycle' => $validated['billing_cycle'],
            'status' => 'unpaid',
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Invoice tagihan langganan {$invoiceNumber} berhasil diterbitkan.");
    }

    public function markInvoicePaid(Request $request, int $id): RedirectResponse
    {
        $invoice = SubscriptionInvoice::with(['company', 'plan'])->findOrFail($id);

        $validated = $request->validate([
            'payment_method' => 'required|string|in:manual_transfer,midtrans,xendit,bank_bca,bank_mandiri,other',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $invoice->update([
            'status' => 'paid',
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'paid_at' => now(),
        ]);

        // Auto extend company subscription
        $subscription = Subscription::where('company_id', $invoice->company_id)->latest()->first();

        $addPeriod = $invoice->billing_cycle === 'yearly' ? 12 : 1;
        $baseDate = ($subscription && $subscription->ends_at && $subscription->ends_at->isFuture())
            ? $subscription->ends_at
            : now();

        $newEnd = $baseDate->copy()->addMonths($addPeriod);

        if ($subscription) {
            $subscription->update([
                'plan_id' => $invoice->plan_id,
                'status' => 'active',
                'ends_at' => $newEnd,
                'billing_cycle' => $invoice->billing_cycle,
            ]);
        } else {
            Subscription::create([
                'company_id' => $invoice->company_id,
                'plan_id' => $invoice->plan_id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $newEnd,
                'billing_cycle' => $invoice->billing_cycle,
            ]);
        }

        // Activate company if suspended
        if ($invoice->company && $invoice->company->status !== 'active') {
            $invoice->company->update(['status' => 'active']);
        }

        return back()->with('success', "Invoice {$invoice->invoice_number} ditandai lunas. Langganan perusahaan otomatis diperpanjang hingga " . $newEnd->format('d/m/Y') . ".");
    }
}
