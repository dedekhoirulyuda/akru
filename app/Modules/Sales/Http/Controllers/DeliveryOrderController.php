<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Sales\Models\DeliveryOrder;
use App\Modules\Sales\Models\DeliveryOrderLine;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $deliveries = DeliveryOrder::with(['customer', 'warehouse', 'salesOrder'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('sales.deliveries.index', compact('deliveries'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $customers = Contact::where('company_id', $companyId)->where('is_customer', true)->get();
        $warehouses = Warehouse::where('company_id', $companyId)->where('is_active', true)->get();
        $salesOrders = SalesOrder::where('company_id', $companyId)->whereIn('status', ['confirmed', 'processing'])->get();
        $items = Item::where('company_id', $companyId)->where('is_active', true)->get();

        return view('sales.deliveries.create', compact('customers', 'warehouses', 'salesOrders', 'items'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'delivery_date' => 'required|date',
            'driver_name' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $deliveryNumber = 'DO-' . date('Ym') . '-' . str_pad(DeliveryOrder::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $do = DeliveryOrder::create([
                'company_id' => $companyId,
                'contact_id' => $request->contact_id,
                'warehouse_id' => $request->warehouse_id,
                'sales_order_id' => $request->sales_order_id,
                'delivery_number' => $deliveryNumber,
                'delivery_date' => $request->delivery_date,
                'driver_name' => $request->driver_name,
                'vehicle_number' => $request->vehicle_number,
                'status' => 'dispatched',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::find($row['item_id']);
                DeliveryOrderLine::create([
                    'delivery_order_id' => $do->id,
                    'item_id' => $item->id,
                    'quantity' => $row['quantity'],
                    'unit' => $item->unit ?? 'PCS',
                ]);

                // Record stock movement (outbound issue from warehouse)
                StockMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $request->warehouse_id,
                    'item_id' => $item->id,
                    'movement_date' => $request->delivery_date,
                    'movement_type' => 'issue',
                    'source_type' => 'delivery_order',
                    'source_id' => $do->id,
                    'quantity' => -$row['quantity'],
                    'cost_price' => $item->cost_price ?? 0,
                    'total_cost' => ($item->cost_price ?? 0) * $row['quantity'],
                    'notes' => "Pengiriman Surat Jalan {$deliveryNumber}",
                ]);
            }
        });

        return redirect()->route('deliveries.index')->with('success', 'Surat Jalan / Pengiriman Barang berhasil diterbitkan dan stok fisik telah disesuaikan.');
    }
}
