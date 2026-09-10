<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Models\StockOpnameLine;
use App\Modules\MasterData\Models\Item;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $opnames = StockOpname::with(['warehouse', 'creator', 'approver'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('inventory.opnames.index', compact('opnames'));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('current_company_id');
        $opnames = StockOpname::with(['warehouse', 'creator', 'approver'])
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        $headers = [
            'No. Stock Opname',
            'Tanggal Opname',
            'Gudang Lokasi',
            'Dibuat Oleh',
            'Disetujui Oleh',
            'Status',
            'Catatan'
        ];

        $rows = [];
        foreach ($opnames as $opn) {
            $rows[] = [
                $opn->opname_number,
                $opn->opname_date ? date('d/m/Y', strtotime($opn->opname_date)) : '-',
                $opn->warehouse->name ?? '-',
                $opn->creator->name ?? '-',
                $opn->approver->name ?? '-',
                strtoupper(str_replace('_', ' ', $opn->status)),
                $opn->notes ?? '-',
            ];
        }

        $meta = [
            'title' => 'DAFTAR STOCK OPNAME FISIK GUDANG',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Semua Periode',
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Stock_Opname_' . date('Ymd'), $headers, $rows, [22, 16, 24, 20, 20, 18, 30], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('current_company_id');
        $opnames = StockOpname::with(['warehouse', 'creator', 'approver'])
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        return $exportService->exportPdf('inventory.opnames.print-list', compact('opnames'), 'Stock_Opname_' . date('Ymd') . '.pdf');
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $warehouses = Warehouse::where('company_id', $companyId)->where('is_active', true)->get();
        $items = Item::where('company_id', $companyId)->where('is_active', true)->get();

        return view('inventory.opnames.create', compact('warehouses', 'items'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.physical_quantity' => 'required|numeric|min:0',
        ]);

        $opname = DB::transaction(function () use ($request, $companyId) {
            $opnameNumber = 'OPN-' . date('Ym') . '-' . str_pad(StockOpname::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $op = StockOpname::create([
                'company_id' => $companyId,
                'warehouse_id' => $request->warehouse_id,
                'opname_number' => $opnameNumber,
                'opname_date' => $request->opname_date,
                'status' => 'pending_approval',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::find($row['item_id']);

                // Calculate current system quantity from stock movements in this warehouse
                $systemQty = (float) StockMovement::where('company_id', $companyId)
                    ->where('warehouse_id', $request->warehouse_id)
                    ->where('item_id', $item->id)
                    ->sum('quantity');

                $physicalQty = (float) $row['physical_quantity'];
                $diffQty = $physicalQty - $systemQty;
                $costPrice = (float) ($item->cost_price ?? 0);
                $diffAmount = $diffQty * $costPrice;

                StockOpnameLine::create([
                    'stock_opname_id' => $op->id,
                    'item_id' => $item->id,
                    'system_quantity' => $systemQty,
                    'physical_quantity' => $physicalQty,
                    'difference_quantity' => $diffQty,
                    'cost_price' => $costPrice,
                    'total_difference_amount' => $diffAmount,
                    'reason' => $row['reason'] ?? null,
                ]);
            }

            return $op;
        });

        return redirect()->route('stock-opnames.index')->with('success', "Stock Opname {$opname->opname_number} berhasil dibuat dan menunggu persetujuan (approval).");
    }

    public function approve($id)
    {
        $companyId = session('current_company_id');
        $opname = StockOpname::with('lines.item')->where('company_id', $companyId)->findOrFail($id);

        if ($opname->status === 'approved') {
            return back()->with('error', 'Stock Opname ini sudah disetujui sebelumnya.');
        }

        DB::transaction(function () use ($opname, $companyId) {
            foreach ($opname->lines as $line) {
                if ($line->difference_quantity != 0) {
                    // Create adjustment movement
                    StockMovement::create([
                        'company_id' => $companyId,
                        'warehouse_id' => $opname->warehouse_id,
                        'item_id' => $line->item_id,
                        'movement_date' => $opname->opname_date,
                        'movement_type' => 'adjustment',
                        'source_type' => 'stock_opname',
                        'source_id' => $opname->id,
                        'quantity' => $line->difference_quantity,
                        'cost_price' => $line->cost_price,
                        'total_cost' => $line->total_difference_amount,
                        'notes' => "Penyesuaian hasil opname [{$opname->opname_number}]",
                    ]);
                }
            }

            $opname->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()->route('stock-opnames.index')->with('success', "Hasil Stock Opname {$opname->opname_number} disetujui dan saldo stok fisik telah sinkron.");
    }
}
