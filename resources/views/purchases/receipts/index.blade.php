@extends('layouts.app')

@section('title', 'Penerimaan Barang Gudang (Goods Receipts) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Penerimaan Barang Gudang (Goods Receipts)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pencatatan fisik barang masuk dari supplier dan penambahan stok gudang logistik</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('goods-receipts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Catat Penerimaan Baru
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-36 whitespace-nowrap">No. Penerimaan</th>
                    <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal</th>
                    <th class="py-3 px-6">Supplier</th>
                    <th class="py-3 px-6 w-36 whitespace-nowrap">Gudang Masuk</th>
                    <th class="py-3 px-6 w-32 whitespace-nowrap">Referensi PO</th>
                    <th class="py-3 px-6 w-36 whitespace-nowrap">Surat Jalan Vendor</th>
                    <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($receipts as $gr)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600 whitespace-nowrap">
                        {{ $gr->receipt_number }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">
                        {{ $gr->receipt_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $gr->supplier->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-700 whitespace-nowrap">
                        {{ $gr->warehouse->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs font-mono text-slate-500 whitespace-nowrap">
                        {{ $gr->purchaseOrder->po_number ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 whitespace-nowrap">
                        {{ $gr->supplier_delivery_number ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ ucfirst($gr->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        Belum ada penerimaan barang gudang yang dicatat.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($receipts->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $receipts->links() }}
    </div>
    @endif
</div>
@endsection
