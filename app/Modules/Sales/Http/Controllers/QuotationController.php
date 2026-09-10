<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use App\Modules\Sales\Models\Quotation;
use App\Modules\Sales\Models\QuotationLine;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Models\SalesOrderLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $quotations = Quotation::with(['customer'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('sales.quotations.index', compact('quotations'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $customers = Contact::where('company_id', $companyId)->where('is_customer', true)->get();
        $items = Item::where('company_id', $companyId)->where('is_active', true)->get();

        return view('sales.quotations.create', compact('customers', 'items'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $quotationNumber = 'QUO-' . date('Ym') . '-' . str_pad(Quotation::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($request->items as $row) {
                $lineTotal = $row['quantity'] * $row['unit_price'];
                $subtotal += $lineTotal;
            }

            $totalAmount = $subtotal + $taxAmount;

            $quotation = Quotation::create([
                'company_id' => $companyId,
                'contact_id' => $request->contact_id,
                'quotation_number' => $quotationNumber,
                'quotation_date' => $request->quotation_date,
                'valid_until' => $request->valid_until,
                'status' => 'sent',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::find($row['item_id']);
                $lineTotal = $row['quantity'] * $row['unit_price'];
                QuotationLine::create([
                    'quotation_id' => $quotation->id,
                    'item_id' => $item->id,
                    'description' => $item->name,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'total' => $lineTotal,
                ]);
            }
        });

        return redirect()->route('quotations.index')->with('success', 'Penawaran harga berhasil diterbitkan.');
    }

    public function convertToOrder($id)
    {
        $companyId = session('current_company_id');
        $quotation = Quotation::with('lines')->where('company_id', $companyId)->findOrFail($id);

        $so = DB::transaction(function () use ($quotation, $companyId) {
            $orderNumber = 'SO-' . date('Ym') . '-' . str_pad(SalesOrder::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $so = SalesOrder::create([
                'company_id' => $companyId,
                'contact_id' => $quotation->contact_id,
                'quotation_id' => $quotation->id,
                'order_number' => $orderNumber,
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => now()->addDays(7)->toDateString(),
                'status' => 'confirmed',
                'subtotal' => $quotation->subtotal,
                'tax_amount' => $quotation->tax_amount,
                'total_amount' => $quotation->total_amount,
                'notes' => "Dikonversi dari penawaran: {$quotation->quotation_number}",
                'created_by' => auth()->id(),
            ]);

            foreach ($quotation->lines as $line) {
                SalesOrderLine::create([
                    'sales_order_id' => $so->id,
                    'item_id' => $line->item_id,
                    'description' => $line->description,
                    'ordered_quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'total' => $line->total,
                ]);
            }

            $quotation->update(['status' => 'converted']);

            return $so;
        });

        return redirect()->route('sales-orders.index')->with('success', "Penawaran berhasil dikonversi menjadi Pesanan Penjualan ({$so->order_number}).");
    }
}
