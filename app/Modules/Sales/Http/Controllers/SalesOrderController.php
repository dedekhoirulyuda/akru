<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Models\SalesOrderLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $orders = SalesOrder::with(['customer', 'quotation'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('sales.orders.index', compact('orders'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $customers = Contact::where('company_id', $companyId)->where('is_customer', true)->get();
        $items = Item::where('company_id', $companyId)->where('is_active', true)->get();

        return view('sales.orders.create', compact('customers', 'items'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $customer = Contact::where('company_id', $companyId)->findOrFail($request->contact_id);

        $subtotal = 0;
        foreach ($request->items as $row) {
            $subtotal += ($row['quantity'] * $row['unit_price']);
        }
        $totalAmount = $subtotal;

        // Credit Control Check (Blueprint §2.3)
        $currentOutstanding = SalesInvoice::where('company_id', $companyId)
            ->where('contact_id', $customer->id)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('remaining_amount');

        $isCreditHold = false;
        if ($customer->credit_limit > 0 && ($currentOutstanding + $totalAmount) > $customer->credit_limit) {
            $isCreditHold = true;
        }

        $order = DB::transaction(function () use ($request, $companyId, $subtotal, $totalAmount, $isCreditHold) {
            $orderNumber = 'SO-' . date('Ym') . '-' . str_pad(SalesOrder::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $so = SalesOrder::create([
                'company_id' => $companyId,
                'contact_id' => $request->contact_id,
                'order_number' => $orderNumber,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'status' => $isCreditHold ? 'on_hold' : 'confirmed',
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'credit_checked' => true,
                'credit_hold' => $isCreditHold,
                'notes' => $isCreditHold ? ($request->notes . ' [TERTAHAN: Melebihi plafon kredit limit]') : $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::find($row['item_id']);
                SalesOrderLine::create([
                    'sales_order_id' => $so->id,
                    'item_id' => $item->id,
                    'description' => $item->name,
                    'ordered_quantity' => $row['quantity'],
                    'delivered_quantity' => 0,
                    'invoiced_quantity' => 0,
                    'unit_price' => $row['unit_price'],
                    'total' => $row['quantity'] * $row['unit_price'],
                ]);
            }

            return $so;
        });

        $message = $isCreditHold
            ? "Pesanan berhasil disimpan namun DITAHAN (Credit Hold) karena piutang melebihi limit kredit pelanggan."
            : "Pesanan Penjualan ({$order->order_number}) berhasil dibuat dan terkonfirmasi.";

        return redirect()->route('sales-orders.index')->with($isCreditHold ? 'warning' : 'success', $message);
    }
}
