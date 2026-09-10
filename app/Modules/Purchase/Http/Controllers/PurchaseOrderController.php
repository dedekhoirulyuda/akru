<?php

namespace App\Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use App\Modules\Purchase\Models\PurchaseOrder;
use App\Modules\Purchase\Models\PurchaseOrderLine;
use App\Modules\Purchase\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $orders = PurchaseOrder::with(['supplier', 'purchaseRequest'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('purchases.orders.index', compact('orders'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $suppliers = Contact::where('company_id', $companyId)->where('is_supplier', true)->get();
        $approvedPRs = PurchaseRequest::where('company_id', $companyId)->where('status', 'approved')->get();
        $items = Item::where('company_id', $companyId)->where('is_active', true)->get();

        return view('purchases.orders.create', compact('suppliers', 'approvedPRs', 'items'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'order_date' => 'required|date',
            'expected_arrival_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $po = DB::transaction(function () use ($request, $companyId) {
            $poNumber = 'PO-' . date('Ym') . '-' . str_pad(PurchaseOrder::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            foreach ($request->items as $row) {
                $subtotal += ($row['quantity'] * $row['unit_price']);
            }

            $order = PurchaseOrder::create([
                'company_id' => $companyId,
                'contact_id' => $request->contact_id,
                'purchase_request_id' => $request->purchase_request_id,
                'po_number' => $poNumber,
                'order_date' => $request->order_date,
                'expected_arrival_date' => $request->expected_arrival_date,
                'status' => 'sent',
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::find($row['item_id']);
                PurchaseOrderLine::create([
                    'purchase_order_id' => $order->id,
                    'item_id' => $item->id,
                    'description' => $item->name,
                    'ordered_quantity' => $row['quantity'],
                    'received_quantity' => 0,
                    'billed_quantity' => 0,
                    'unit_price' => $row['unit_price'],
                    'total' => $row['quantity'] * $row['unit_price'],
                ]);
            }

            if ($request->purchase_request_id) {
                PurchaseRequest::where('id', $request->purchase_request_id)->update(['status' => 'converted']);
            }

            return $order;
        });

        return redirect()->route('purchase-orders.index')->with('success', "Pesanan Pembelian ({$po->po_number}) berhasil diterbitkan.");
    }
}
