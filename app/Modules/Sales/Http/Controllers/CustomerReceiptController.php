<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\MasterData\Models\Contact;
use App\Modules\Sales\Models\CustomerReceipt;
use App\Modules\Sales\Models\ReceiptAllocation;
use App\Modules\Sales\Models\SalesInvoice;
use App\Services\Posting\PostingService;
use App\Services\SequenceEngine\SequenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerReceiptController extends Controller
{
    public function __construct(
        protected SequenceGenerator $sequenceGenerator,
        protected PostingService $postingService,
    ) {}

    public function index(Request $request)
    {
        $receipts = CustomerReceipt::with(['contact', 'bankAccount', 'allocations.invoice'])
            ->latest('receipt_date')
            ->paginate(15);

        return view('sales.receipts.index', compact('receipts'));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $receipts = CustomerReceipt::with(['contact', 'bankAccount'])->latest('receipt_date')->get();

        $headers = [
            'No. Bukti Penerimaan',
            'Tanggal',
            'Pelanggan / Customer',
            'Rekening Kas/Bank Masuk',
            'Metode Pembayaran',
            'No. Referensi',
            'Jumlah Diterima (Rp)',
            'Keterangan'
        ];

        $rows = [];
        foreach ($receipts as $r) {
            $rows[] = [
                $r->receipt_number,
                $r->receipt_date ? $r->receipt_date->format('d/m/Y') : '-',
                $r->contact->name ?? '-',
                $r->bankAccount->bank_name ?? '-',
                strtoupper($r->payment_method ?? 'TRANSFER'),
                $r->reference_number ?? '-',
                number_format($r->total_amount, 0, ',', '.'),
                $r->notes ?? '-',
            ];
        }

        $meta = [
            'title' => 'REGISTER PENERIMAAN PEMBAYARAN PIUTANG (CUSTOMER RECEIPTS)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Semua Periode',
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Register_Penerimaan_Piutang_' . date('Ymd'), $headers, $rows, [22, 14, 28, 24, 18, 18, 22, 35], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $receipts = CustomerReceipt::with(['contact', 'bankAccount'])->latest('receipt_date')->get();
        $totalReceived = $receipts->sum('total_amount');

        return $exportService->exportPdf('sales.receipts.print-list', compact('receipts', 'totalReceived'), 'Register_Penerimaan_Piutang_' . date('Ymd') . '.pdf');
    }

    public function create(Request $request)
    {
        $companyId = session('current_company_id');
        $customers = Contact::whereIn('type', ['customer', 'both'])->where('is_active', true)->get();
        $bankAccounts = BankAccount::where('is_active', true)->get();
        $unpaidInvoices = SalesInvoice::where('status', 'posted')
            ->where('remaining_amount', '>', 0)
            ->with('contact')
            ->get();
        $defaultNumber = $this->sequenceGenerator->next('customer_receipt', $companyId);

        return view('sales.receipts.create', compact('customers', 'bankAccounts', 'unpaidInvoices', 'defaultNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'receipt_number' => 'required|string|max:50',
            'receipt_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'total_amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|exists:sales_invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        $companyId = session('current_company_id');
        $user = auth()->user();
        $bankAccount = BankAccount::with('account')->find($validated['bank_account_id']);
        $arAccount = Account::where('company_id', $companyId)->where('code', '1200')->first();

        DB::transaction(function () use ($companyId, $user, $validated, $bankAccount, $arAccount) {
            $receipt = CustomerReceipt::create([
                'company_id' => $companyId,
                'branch_id' => $user->current_branch_id,
                'contact_id' => $validated['contact_id'],
                'bank_account_id' => $validated['bank_account_id'],
                'receipt_number' => $validated['receipt_number'],
                'receipt_date' => $validated['receipt_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'],
                'total_amount' => $validated['total_amount'],
                'status' => 'posted',
                'notes' => $validated['notes'],
                'created_by' => $user->id,
                'posted_at' => now(),
            ]);

            $totalAllocated = 0;
            foreach ($validated['allocations'] as $alloc) {
                $amount = (float) $alloc['amount'];
                if ($amount <= 0) continue;

                ReceiptAllocation::create([
                    'customer_receipt_id' => $receipt->id,
                    'sales_invoice_id' => $alloc['invoice_id'],
                    'allocated_amount' => $amount,
                ]);

                $inv = SalesInvoice::find($alloc['invoice_id']);
                $newPaid = (float) $inv->paid_amount + $amount;
                $newRemaining = max(0, (float) $inv->total_amount - $newPaid);
                $newStatus = $newRemaining <= 0.01 ? 'paid' : 'partially_paid';

                $inv->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'status' => $newStatus,
                ]);

                $totalAllocated += $amount;
            }

            // Post to General Ledger:
            // Debit: Kas / Bank = Total Amount
            // Credit: Piutang Usaha = Total Amount
            $lines = [
                [
                    'account_id' => $bankAccount->account_id,
                    'debit' => (float) $validated['total_amount'],
                    'credit' => 0,
                    'description' => "Penerimaan Pembayaran {$receipt->receipt_number}",
                    'contact_id' => $validated['contact_id'],
                ],
                [
                    'account_id' => $arAccount->id,
                    'debit' => 0,
                    'credit' => (float) $validated['total_amount'],
                    'description' => "Pelunasan Piutang via {$receipt->receipt_number}",
                    'contact_id' => $validated['contact_id'],
                ],
            ];

            $this->postingService->post(
                sourceType: 'customer_receipt',
                sourceId: $receipt->id,
                companyId: $companyId,
                lines: $lines,
                idempotencyKey: "POST_RCP_{$receipt->id}",
                actorId: $user->id,
                description: "Penerimaan Pembayaran {$receipt->receipt_number} ({$bankAccount->bank_name})",
                journalDate: $validated['receipt_date'],
                branchId: $user->current_branch_id,
            );
        });

        return redirect()->route('receipts.index')->with('success', 'Penerimaan pembayaran berhasil dicatat dan diposting ke Buku Besar.');
    }
}
