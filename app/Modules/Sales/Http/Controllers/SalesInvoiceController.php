<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use App\Modules\MasterData\Models\TaxCode;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Models\SalesInvoiceLine;
use App\Services\Posting\PostingService;
use App\Services\SequenceEngine\SequenceGenerator;
use App\Services\TaxEngine\TaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function __construct(
        protected SequenceGenerator $sequenceGenerator,
        protected PostingService $postingService,
        protected TaxCalculator $taxCalculator,
    ) {}

    public function index(Request $request)
    {
        $invoices = SalesInvoice::with(['contact', 'taxCode'])
            ->latest('invoice_date')
            ->paginate(15);

        return view('sales.invoices.index', compact('invoices'));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $invoices = SalesInvoice::with(['contact', 'taxCode'])->latest('invoice_date')->get();

        $headers = [
            'No. Faktur',
            'Tanggal Faktur',
            'Jatuh Tempo',
            'Pelanggan / Customer',
            'Subtotal (Rp)',
            'PPN (Rp)',
            'Total Tagihan (Rp)',
            'Terbayar (Rp)',
            'Sisa Tagihan (Rp)',
            'Status Pelunasan'
        ];

        $rows = [];
        foreach ($invoices as $inv) {
            $statusLabel = $inv->remaining_amount <= 0 ? 'LUNAS' : ($inv->paid_amount > 0 ? 'SEBAGIAN' : 'BELUM DIBAYAR');
            $rows[] = [
                $inv->invoice_number,
                $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '-',
                $inv->due_date ? $inv->due_date->format('d/m/Y') : '-',
                $inv->contact?->name ?? '-',
                number_format($inv->subtotal, 0, ',', '.'),
                number_format($inv->tax_amount, 0, ',', '.'),
                number_format($inv->total_amount, 0, ',', '.'),
                number_format($inv->paid_amount, 0, ',', '.'),
                number_format($inv->remaining_amount, 0, ',', '.'),
                $statusLabel,
            ];
        }

        $meta = [
            'title' => 'REGISTER FAKTUR PENJUALAN (SALES INVOICES)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Semua Periode Transaksi',
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Register_Faktur_Penjualan_' . date('Ymd'), $headers, $rows, [18, 14, 14, 28, 18, 16, 20, 18, 18, 16], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $invoices = SalesInvoice::with(['contact', 'taxCode'])->latest('invoice_date')->get();
        $totalAmount = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $totalRemaining = $invoices->sum('remaining_amount');

        return $exportService->exportPdf('sales.invoices.print-list', compact('invoices', 'totalAmount', 'totalPaid', 'totalRemaining'), 'Register_Faktur_Penjualan_' . date('Ymd') . '.pdf');
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $customers = Contact::whereIn('type', ['customer', 'both'])->where('is_active', true)->get();
        $items = Item::where('is_active', true)->get();
        $taxCodes = TaxCode::where('is_active', true)->get();
        $defaultNumber = $this->sequenceGenerator->next('sales_invoice', $companyId);

        return view('sales.invoices.create', compact('customers', 'items', 'taxCodes', 'defaultNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'invoice_number' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'tax_code_id' => 'nullable|exists:tax_codes,id',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'action' => 'required|in:draft,post',
        ]);

        $companyId = session('current_company_id');
        $user = auth()->user();

        // Calculate totals
        $subtotal = 0;
        $processedLines = [];
        $taxCode = $validated['tax_code_id'] ? TaxCode::find($validated['tax_code_id']) : null;
        $taxRate = $taxCode ? (float) $taxCode->rate : 0.0;

        foreach ($validated['lines'] as $l) {
            $qty = (float) $l['quantity'];
            $price = (float) $l['unit_price'];
            $discPct = (float) ($l['discount_percent'] ?? 0);
            $lineSubtotal = round($qty * $price, 2);
            $discAmount = round(($lineSubtotal * $discPct) / 100, 2);
            $netLine = $lineSubtotal - $discAmount;
            $lineTax = round(($netLine * $taxRate) / 100, 2);

            $subtotal += $netLine;
            $processedLines[] = array_merge($l, [
                'discount_percent' => $discPct,
                'discount_amount' => $discAmount,
                'subtotal' => $netLine,
                'tax_amount' => $lineTax,
                'total' => $netLine + $lineTax,
            ]);
        }

        $taxAmount = round(($subtotal * $taxRate) / 100, 2);
        $totalAmount = $subtotal + $taxAmount;

        $invoice = DB::transaction(function () use (
            $companyId, $user, $validated, $subtotal, $taxAmount, $totalAmount, $processedLines
        ) {
            $inv = SalesInvoice::create([
                'company_id' => $companyId,
                'branch_id' => $user->current_branch_id,
                'contact_id' => $validated['contact_id'],
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'tax_code_id' => $validated['tax_code_id'],
                'notes' => $validated['notes'],
                'created_by' => $user->id,
            ]);

            foreach ($processedLines as $pl) {
                $item = !empty($pl['item_id']) ? Item::find($pl['item_id']) : null;
                SalesInvoiceLine::create([
                    'sales_invoice_id' => $inv->id,
                    'item_id' => $pl['item_id'],
                    'description' => $pl['description'],
                    'quantity' => $pl['quantity'],
                    'unit_price' => $pl['unit_price'],
                    'discount_percent' => $pl['discount_percent'],
                    'discount_amount' => $pl['discount_amount'],
                    'subtotal' => $pl['subtotal'],
                    'tax_amount' => $pl['tax_amount'],
                    'total' => $pl['total'],
                    'sales_account_id' => $item?->sales_account_id,
                    'cogs_account_id' => $item?->cogs_account_id,
                    'inventory_account_id' => $item?->inventory_account_id,
                ]);
            }

            return $inv;
        });

        if ($validated['action'] === 'post') {
            $this->postInvoiceToGL($invoice);
            return redirect()->route('sales.show', $invoice)->with('success', 'Faktur Penjualan berhasil dibuat dan diposting ke Buku Besar.');
        }

        return redirect()->route('sales.show', $invoice)->with('success', 'Draft Faktur Penjualan berhasil disimpan.');
    }

    public function show(SalesInvoice $invoice)
    {
        $invoice->load(['contact', 'taxCode', 'lines.item', 'allocations.receipt']);
        $journal = DB::table('journal_sets')
            ->where('source_type', 'sales_invoice')
            ->where('source_id', $invoice->id)
            ->first();

        $journalLines = $journal ? DB::table('journal_lines')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_set_id', $journal->id)
            ->select('journal_lines.*', 'accounts.code as account_code', 'accounts.name as account_name')
            ->get() : collect();

        return view('sales.invoices.show', compact('invoice', 'journal', 'journalLines'));
    }

    public function post(SalesInvoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Hanya faktur berstatus draft yang dapat diposting.');
        }

        $this->postInvoiceToGL($invoice);

        return back()->with('success', 'Faktur Penjualan berhasil disetujui dan diposting ke Buku Besar.');
    }

    public function print(SalesInvoice $invoice)
    {
        $invoice->load(['contact', 'taxCode', 'lines.item']);
        return view('sales.invoices.print', compact('invoice'));
    }

    /**
     * Post sales invoice to General Ledger & Tax Subledger.
     */
    protected function postInvoiceToGL(SalesInvoice $invoice): void
    {
        $companyId = $invoice->company_id;
        $arAccount = Account::where('company_id', $companyId)->where('code', '1200')->first();
        $salesAccount = Account::where('company_id', $companyId)->where('code', '4100')->first();
        $taxCode = $invoice->taxCode;
        $ppnAccount = $taxCode?->salesAccount ?? Account::where('company_id', $companyId)->where('code', '2200')->first();

        // 1. Double entry journal lines:
        // Debit: Piutang Usaha (1200) = Total Amount
        // Credit: Pendapatan Penjualan (4100) = Subtotal
        // Credit: Hutang PPN Keluaran (2200) = Tax Amount (if > 0)
        $journalLines = [
            [
                'account_id' => $arAccount->id,
                'debit' => (float) $invoice->total_amount,
                'credit' => 0,
                'description' => "Piutang Faktur Penjualan {$invoice->invoice_number}",
                'contact_id' => $invoice->contact_id,
            ],
            [
                'account_id' => $salesAccount->id,
                'debit' => 0,
                'credit' => (float) $invoice->subtotal,
                'description' => "Penjualan {$invoice->invoice_number}",
                'contact_id' => $invoice->contact_id,
            ],
        ];

        if ((float) $invoice->tax_amount > 0 && $ppnAccount) {
            $journalLines[] = [
                'account_id' => $ppnAccount->id,
                'debit' => 0,
                'credit' => (float) $invoice->tax_amount,
                'description' => "PPN Keluaran {$invoice->invoice_number}",
                'contact_id' => $invoice->contact_id,
            ];
        }

        // 2. Execute posting
        $this->postingService->post(
            sourceType: 'sales_invoice',
            sourceId: $invoice->id,
            companyId: $companyId,
            lines: $journalLines,
            idempotencyKey: "POST_INV_{$invoice->id}",
            actorId: auth()->id() ?? $invoice->created_by,
            description: "Faktur Penjualan {$invoice->invoice_number} - {$invoice->contact->name}",
            journalDate: $invoice->invoice_date->toDateString(),
            branchId: $invoice->branch_id,
        );

        // 3. Record Tax Entry if tax applies
        if ((float) $invoice->tax_amount > 0 && $taxCode) {
            $this->taxCalculator->recordEntry(
                companyId: $companyId,
                taxCodeId: $taxCode->id,
                sourceType: 'sales_invoice',
                sourceId: $invoice->id,
                baseAmount: (float) $invoice->subtotal,
                taxAmount: (float) $invoice->tax_amount,
                taxDate: $invoice->invoice_date->toDateString(),
                direction: 'output',
                contactId: $invoice->contact_id,
                invoiceNumber: $invoice->invoice_number,
                counterpartyNpwp: $invoice->contact->identity_number,
                counterpartyName: $invoice->contact->name,
                accountId: $ppnAccount?->id,
            );
        }

        // 4. Update invoice status
        $invoice->update([
            'status' => 'posted',
            'posted_at' => now(),
            'approved_by' => auth()->id() ?? $invoice->created_by,
        ]);
    }
}
