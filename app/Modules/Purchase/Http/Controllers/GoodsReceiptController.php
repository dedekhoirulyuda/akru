<?php

namespace App\Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchase\Models\GoodsReceipt;
use App\Modules\Purchase\Models\GoodsReceiptLine;
use App\Modules\Purchase\Models\PurchaseOrder;
use App\Modules\Purchase\Models\PurchaseOrderLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $receipts = GoodsReceipt::with(['supplier', 'warehouse', 'purchaseOrder'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('purchases.receipts.index', compact('receipts'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $suppliers = Contact::where('company_id', $companyId)->where('is_supplier', true)->get();
        $warehouses = Warehouse::where('company_id', $companyId)->where('is_active', true)->get();
        $purchaseOrders = PurchaseOrder::where('company_id', $companyId)->whereIn('status', ['sent', 'partial_received'])->get();
        $items = Item::where('company_id', $companyId)->where('is_active', true)->get();

        return view('purchases.receipts.create', compact('suppliers', 'warehouses', 'purchaseOrders', 'items'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_date' => 'required|date',
            'supplier_delivery_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.accepted_quantity' => 'required|numeric|min:0.01',
            'items.*.rejected_quantity' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $receiptNumber = 'GR-' . date('Ym') . '-' . str_pad(GoodsReceipt::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $gr = GoodsReceipt::create([
                'company_id' => $companyId,
                'contact_id' => $request->contact_id,
                'warehouse_id' => $request->warehouse_id,
                'purchase_order_id' => $request->purchase_order_id,
                'receipt_number' => $receiptNumber,
                'supplier_delivery_number' => $request->supplier_delivery_number,
                'receipt_date' => $request->receipt_date,
                'status' => 'confirmed',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::find($row['item_id']);
                GoodsReceiptLine::create([
                    'goods_receipt_id' => $gr->id,
                    'item_id' => $item->id,
                    'accepted_quantity' => $row['accepted_quantity'],
                    'rejected_quantity' => $row['rejected_quantity'] ?? 0,
                    'unit' => $item->unit ?? 'PCS',
                ]);

                // Record inbound stock movement
                StockMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $request->warehouse_id,
                    'item_id' => $item->id,
                    'movement_date' => $request->receipt_date,
                    'movement_type' => 'receipt',
                    'source_type' => 'goods_receipt',
                    'source_id' => $gr->id,
                    'quantity' => $row['accepted_quantity'],
                    'cost_price' => $item->cost_price ?? 0,
                    'total_cost' => ($item->cost_price ?? 0) * $row['accepted_quantity'],
                    'notes' => "Penerimaan Barang Gudang {$receiptNumber}",
                ]);

                if ($request->purchase_order_id) {
                    PurchaseOrderLine::where('purchase_order_id', $request->purchase_order_id)
                        ->where('item_id', $item->id)
                        ->increment('received_quantity', $row['accepted_quantity']);
                }
            }

            if ($request->purchase_order_id) {
                PurchaseOrder::where('id', $request->purchase_order_id)->update(['status' => 'received']);
            }
        });

        return redirect()->route('goods-receipts.index')->with('success', 'Penerimaan barang fisik berhasil dicatat dan stok gudang telah bertambah.');
    }
}
