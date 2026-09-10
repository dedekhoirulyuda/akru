<?php

namespace App\Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Item;
use App\Modules\Purchase\Models\PurchaseRequest;
use App\Modules\Purchase\Models\PurchaseRequestLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $requests = PurchaseRequest::with(['requester', 'approver'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);

        return view('purchases.requests.index', compact('requests'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $items = Item::where('company_id', $companyId)->where('is_active', true)->get();

        return view('purchases.requests.create', compact('items'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'request_date' => 'required|date',
            'required_date' => 'nullable|date',
            'purpose' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $requestNumber = 'PR-' . date('Ym') . '-' . str_pad(PurchaseRequest::where('company_id', $companyId)->count() + 1, 4, '0', STR_PAD_LEFT);

            $pr = PurchaseRequest::create([
                'company_id' => $companyId,
                'request_number' => $requestNumber,
                'request_date' => $request->request_date,
                'required_date' => $request->required_date,
                'purpose' => $request->purpose,
                'status' => 'submitted',
                'requested_by' => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::find($row['item_id']);
                PurchaseRequestLine::create([
                    'purchase_request_id' => $pr->id,
                    'item_id' => $item->id,
                    'item_description' => $item->name,
                    'quantity' => $row['quantity'],
                    'unit' => $item->unit ?? 'PCS',
                    'estimated_cost' => ($row['quantity'] * ($item->cost_price ?? 0)),
                ]);
            }
        });

        return redirect()->route('purchase-requests.index')->with('success', 'Permintaan Pembelian (PR) berhasil diajukan untuk disetujui.');
    }

    public function approve($id)
    {
        $companyId = session('current_company_id');
        $pr = PurchaseRequest::where('company_id', $companyId)->findOrFail($id);

        $pr->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('purchase-requests.index')->with('success', "Permintaan Pembelian {$pr->request_number} telah disetujui.");
    }
}
