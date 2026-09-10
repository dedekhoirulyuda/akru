<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\MasterData\Models\Item;
use App\Services\Posting\PostingService;
use App\Services\AuditEngine\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StockController extends Controller
{
    public function __construct(
        protected PostingService $postingService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $companyId = session('active_company_id');
        
        $selectedItemId = $request->query('item_id');
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        // 1. Current Balances
        $balances = InventoryBalance::with('item')
            ->where('company_id', $companyId)
            ->get();

        // 2. All items for filter
        $items = Item::where('company_id', $companyId)->where('is_inventory', true)->get();

        // 3. Stock Movements (Kartu Stok)
        $movementsQuery = StockMovement::with(['item', 'creator'])
            ->where('company_id', $companyId)
            ->whereBetween('movement_date', [$dateFrom, $dateTo]);

        if ($selectedItemId) {
            $movementsQuery->where('item_id', $selectedItemId);
        }

        $movements = $movementsQuery->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        $totalValuation = $balances->sum('total_value');
        $totalItemsCount = $balances->count();
        $lowStockCount = $balances->filter(fn($b) => $b->quantity <= ($b->item->min_stock ?? 5))->count();

        return view('inventory.index', compact(
            'balances',
            'items',
            'movements',
            'selectedItemId',
            'dateFrom',
            'dateTo',
            'totalValuation',
            'totalItemsCount',
            'lowStockCount'
        ));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $balances = InventoryBalance::with('item')
            ->where('company_id', $companyId)
            ->get();

        $headers = [
            'Kode SKU',
            'Nama Barang / Produk',
            'Kategori / Jenis',
            'Stok Fisik',
            'Satuan',
            'Harga Beli Terakhir (Rp)',
            'Total Nilai Persediaan (Rp)',
            'Status Stok'
        ];

        $rows = [];
        foreach ($balances as $b) {
            $item = $b->item;
            $status = $b->quantity <= 0 ? 'HABIS' : ($b->quantity <= ($item->min_stock ?? 5) ? 'MENIPIS' : 'AMAN');
            $rows[] = [
                $item->sku ?? '-',
                $item->name ?? '-',
                ucfirst($item->type ?? 'Barang'),
                number_format($b->quantity, 0, ',', '.'),
                $item->unit ?? 'Pcs',
                number_format($b->unit_cost ?? $item->purchase_price ?? 0, 0, ',', '.'),
                number_format($b->total_value, 0, ',', '.'),
                $status,
            ];
        }

        $meta = [
            'title' => 'LAPORAN STOK BARANG & VALUASI PERSEDIAAN GUDANG',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Posisi Stok Fisik Per ' . now()->format('d/m/Y H:i'),
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Laporan_Stok_Gudang_' . date('Ymd'), $headers, $rows, [18, 35, 18, 14, 12, 22, 24, 16], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $balances = InventoryBalance::with('item')
            ->where('company_id', $companyId)
            ->get();
        $totalValuation = $balances->sum('total_value');
        $totalQty = $balances->sum('quantity');

        return $exportService->exportPdf('inventory.print-list', compact('balances', 'totalValuation', 'totalQty'), 'Laporan_Stok_Gudang_' . date('Ymd') . '.pdf');
    }

    public function adjustment(Request $request): RedirectResponse
    {
        $companyId = session('active_company_id');
        $user = auth()->user();

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'adjustment_type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'required|numeric|min:0',
            'movement_date' => 'required|date',
            'notes' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $companyId, $user) {
            $item = Item::where('company_id', $companyId)->findOrFail($validated['item_id']);
            $qty = $validated['adjustment_type'] === 'in' 
                ? (float) $validated['quantity'] 
                : -(float) $validated['quantity'];
            $unitCost = (float) $validated['unit_cost'];
            $totalCost = abs($qty) * $unitCost;

            // Get or create balance
            $balance = InventoryBalance::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'warehouse_id' => 1, // Default main warehouse
                    'item_id' => $item->id,
                ],
                [
                    'quantity' => 0,
                    'average_cost' => $unitCost,
                    'total_value' => 0,
                ]
            );

            $newQty = $balance->quantity + $qty;
            if ($newQty < 0) {
                $newQty = 0;
            }
            $newTotalValue = $newQty * $unitCost;

            $balance->update([
                'quantity' => $newQty,
                'average_cost' => $unitCost,
                'total_value' => $newTotalValue,
            ]);

            // Record Movement
            StockMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => 1,
                'item_id' => $item->id,
                'movement_type' => 'adjustment',
                'movement_date' => $validated['movement_date'],
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'balance_after' => $newQty,
                'source_type' => 'manual_adjustment',
                'notes' => $validated['notes'],
                'created_by' => $user->id,
            ]);

            // Audit
            $this->auditLogger->log(
                action: 'stock.adjusted',
                auditableType: Item::class,
                auditableId: $item->id,
                oldValues: ['quantity' => $balance->getOriginal('quantity')],
                newValues: ['quantity' => $newQty, 'diff' => $qty, 'notes' => $validated['notes']],
                companyId: $companyId,
                userId: $user->id
            );
        });

        return redirect()->route('inventory.index')->with('success', 'Penyesuaian stok berhasil disimpan.');
    }
}
