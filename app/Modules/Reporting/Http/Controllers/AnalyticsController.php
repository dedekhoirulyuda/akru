<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Item;
use App\Modules\Sales\Models\SalesInvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function salesPurchaseAnalytics(Request $request)
    {
        $companyId = session('current_company_id');

        // Profitability per Product
        $itemProfitability = SalesInvoiceLine::whereHas('invoice', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->whereIn('status', ['posted', 'partially_paid', 'paid']);
        })->select(
            'item_id',
            'description',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(total) as total_revenue')
        )->groupBy('item_id', 'description')
         ->get()
         ->map(function ($row) {
             $item = $row->item_id ? Item::find($row->item_id) : null;
             $costPrice = (float) ($item->buy_price ?? $item->cost_price ?? 0);
             $totalCogs = $row->total_qty * $costPrice;
             $grossProfit = (float) $row->total_revenue - $totalCogs;
             $marginPct = $row->total_revenue > 0 ? round(($grossProfit / $row->total_revenue) * 100, 1) : 0;

             return [
                 'name' => $row->description ?: ($item->name ?? 'Produk'),
                 'sku' => $item->sku ?? '-',
                 'qty' => (float) $row->total_qty,
                 'revenue' => (float) $row->total_revenue,
                 'cogs' => $totalCogs,
                 'gross_profit' => $grossProfit,
                 'margin_percent' => $marginPct,
             ];
         });

        return view('reports.analytics.index', compact('itemProfitability'));
    }
}
