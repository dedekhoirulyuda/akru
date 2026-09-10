<?php

namespace App\Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\MasterData\Models\Contact;
use App\Modules\Purchase\Models\PaymentAllocation;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Purchase\Models\SupplierPayment;
use App\Services\Posting\PostingService;
use App\Services\SequenceEngine\SequenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function __construct(
        protected SequenceGenerator $sequenceGenerator,
        protected PostingService $postingService,
    ) {}

    public function index(Request $request)
    {
        $payments = SupplierPayment::with(['contact', 'bankAccount', 'allocations.invoice'])
            ->latest('payment_date')
            ->paginate(15);

        return view('purchase.payments.index', compact('payments'));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $payments = SupplierPayment::with(['contact', 'bankAccount'])->latest('payment_date')->get();

        $headers = [
            'No. Bukti Pembayaran',
            'Tanggal',
            'Pemasok / Vendor',
            'Rekening Kas/Bank Keluar',
            'Metode Pembayaran',
            'No. Referensi / Cek',
            'Jumlah Dibayar (Rp)',
            'Keterangan'
        ];

        $rows = [];
        foreach ($payments as $p) {
            $rows[] = [
                $p->payment_number,
                $p->payment_date ? $p->payment_date->format('d/m/Y') : '-',
                $p->contact->name ?? '-',
                $p->bankAccount->bank_name ?? '-',
                strtoupper($p->payment_method ?? 'TRANSFER'),
                $p->reference_number ?? '-',
                number_format($p->total_amount, 0, ',', '.'),
                $p->notes ?? '-',
            ];
        }

        $meta = [
            'title' => 'REGISTER PEMBAYARAN HUTANG PEMASOK (SUPPLIER PAYMENTS)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Semua Periode',
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Register_Pembayaran_Hutang_' . date('Ymd'), $headers, $rows, [22, 14, 28, 24, 18, 18, 22, 35], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $payments = SupplierPayment::with(['contact', 'bankAccount'])->latest('payment_date')->get();
        $totalPaid = $payments->sum('total_amount');

        return $exportService->exportPdf('purchase.payments.print-list', compact('payments', 'totalPaid'), 'Register_Pembayaran_Hutang_' . date('Ymd') . '.pdf');
    }

    public function create(Request $request)
    {
        $companyId = session('current_company_id');
        $suppliers = Contact::whereIn('type', ['supplier', 'both'])->where('is_active', true)->get();
        $bankAccounts = BankAccount::where('is_active', true)->get();
        $unpaidBills = PurchaseInvoice::where('status', 'posted')
            ->where('remaining_amount', '>', 0)
            ->with('contact')
            ->get();
        $defaultNumber = $this->sequenceGenerator->next('supplier_payment', $companyId);

        return view('purchase.payments.create', compact('suppliers', 'bankAccounts', 'unpaidBills', 'defaultNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_number' => 'required|string|max:50',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'total_amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|exists:purchase_invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        $companyId = session('current_company_id');
        $user = auth()->user();
        $bankAccount = BankAccount::with('account')->find($validated['bank_account_id']);
        $apAccount = Account::where('company_id', $companyId)->where('code', '2100')->first();

        DB::transaction(function () use ($companyId, $user, $validated, $bankAccount, $apAccount) {
            $payment = SupplierPayment::create([
                'company_id' => $companyId,
                'branch_id' => $user->current_branch_id,
                'contact_id' => $validated['contact_id'],
                'bank_account_id' => $validated['bank_account_id'],
                'payment_number' => $validated['payment_number'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'],
                'total_amount' => $validated['total_amount'],
                'status' => 'posted',
                'notes' => $validated['notes'],
                'created_by' => $user->id,
                'posted_at' => now(),
            ]);

            foreach ($validated['allocations'] as $alloc) {
                $amount = (float) $alloc['amount'];
                if ($amount <= 0) continue;

                PaymentAllocation::create([
                    'supplier_payment_id' => $payment->id,
                    'purchase_invoice_id' => $alloc['invoice_id'],
                    'allocated_amount' => $amount,
                ]);

                $bill = PurchaseInvoice::find($alloc['invoice_id']);
                $newPaid = (float) $bill->paid_amount + $amount;
                $newRemaining = max(0, (float) $bill->total_amount - $newPaid);
                $newStatus = $newRemaining <= 0.01 ? 'paid' : 'partially_paid';

                $bill->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'status' => $newStatus,
                ]);
            }

            // Post to General Ledger:
            // Debit: Hutang Usaha (2100) = Total Amount
            // Credit: Kas / Bank = Total Amount
            $lines = [
                [
                    'account_id' => $apAccount->id,
                    'debit' => (float) $validated['total_amount'],
                    'credit' => 0,
                    'description' => "Pembayaran Hutang Usaha {$payment->payment_number}",
                    'contact_id' => $validated['contact_id'],
                ],
                [
                    'account_id' => $bankAccount->account_id,
                    'debit' => 0,
                    'credit' => (float) $validated['total_amount'],
                    'description' => "Pengeluaran Kas/Bank {$payment->payment_number}",
                    'contact_id' => $validated['contact_id'],
                ],
            ];

            $this->postingService->post(
                sourceType: 'supplier_payment',
                sourceId: $payment->id,
                companyId: $companyId,
                lines: $lines,
                idempotencyKey: "POST_PAY_{$payment->id}",
                actorId: $user->id,
                description: "Pembayaran Tagihan Pemasok {$payment->payment_number} ({$bankAccount->bank_name})",
                journalDate: $validated['payment_date'],
                branchId: $user->current_branch_id,
            );
        });

        return redirect()->route('payments.index')->with('success', 'Pembayaran hutang berhasil dicatat dan diposting ke Buku Besar.');
    }
}
