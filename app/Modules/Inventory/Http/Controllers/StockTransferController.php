<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Models\StockTransferLine;
use App\Modules\MasterData\Models\Item;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $warehouses = Warehouse::where('company_id', $companyId)->where('is_active', true)->get();
        $items = Item::where('company_id', $companyId)->where('is_active', true)->get();

        return view('inventory.transfers.create', compact('warehouses', 'items'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'transfer_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $transferNumber = 'TRF-' . date('Ym') . '-' . str_pad(StockTransfer::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $transfer = StockTransfer::create([
                'company_id' => $companyId,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'transfer_number' => $transferNumber,
                'transfer_date' => $request->transfer_date,
                'status' => 'in_transit',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
                'dispatched_by' => auth()->id(),
                'dispatched_at' => now(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::find($row['item_id']);
                StockTransferLine::create([
                    'stock_transfer_id' => $transfer->id,
                    'item_id' => $item->id,
                    'quantity_sent' => $row['quantity'],
                    'quantity_received' => 0,
                    'unit' => $item->unit ?? 'PCS',
                ]);

                // Reduce stock from source warehouse
                StockMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $request->from_warehouse_id,
                    'item_id' => $item->id,
                    'movement_date' => $request->transfer_date,
                    'movement_type' => 'transfer_out',
                    'source_type' => 'stock_transfer',
                    'source_id' => $transfer->id,
                    'quantity' => -$row['quantity'],
                    'cost_price' => $item->cost_price ?? 0,
                    'total_cost' => ($item->cost_price ?? 0) * $row['quantity'],
                    'notes' => "Transfer keluar ke gudang tujuan [{$transferNumber}]",
                ]);
            }
        });

        return redirect()->route('stock-transfers.index')->with('success', 'Transfer barang telah dikirim dan berstatus In-Transit.');
    }

    public function receive($id)
    {
        $companyId = session('current_company_id');
        $transfer = StockTransfer::with('lines.item')->where('company_id', $companyId)->findOrFail($id);

        if ($transfer->status === 'received') {
            return back()->with('error', 'Transfer ini sudah diterima sebelumnya.');
        }

        DB::transaction(function () use ($transfer, $companyId) {
            foreach ($transfer->lines as $line) {
                $line->update(['quantity_received' => $line->quantity_sent]);

                // Increase stock in destination warehouse
                StockMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'item_id' => $line->item_id,
                    'movement_date' => now()->toDateString(),
                    'movement_type' => 'transfer_in',
                    'source_type' => 'stock_transfer',
                    'source_id' => $transfer->id,
                    'quantity' => $line->quantity_sent,
                    'cost_price' => $line->item->cost_price ?? 0,
                    'total_cost' => ($line->item->cost_price ?? 0) * $line->quantity_sent,
                    'notes' => "Penerimaan transfer dari gudang asal [{$transfer->transfer_number}]",
                ]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);
        });

        return redirect()->route('stock-transfers.index')->with('success', "Transfer {$transfer->transfer_number} telah diterima di gudang tujuan.");
    }
}
