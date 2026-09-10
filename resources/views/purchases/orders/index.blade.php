@extends('layouts.app')

@section('title', 'Pesanan Pembelian (Purchase Orders) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Pesanan Pembelian (Purchase Orders)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Penerbitan PO resmi ke supplier mitra dan pelacakan status penerimaan barang</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('purchase-orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat PO Baru
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
                    <th class="py-3 px-6 w-36 whitespace-nowrap">No. PO</th>
                    <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal</th>
                    <th class="py-3 px-6">Pemasok / Supplier</th>
                    <th class="py-3 px-6 w-32 whitespace-nowrap">Asal PR</th>
                    <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Total Nilai</th>
                    <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($orders as $po)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600 whitespace-nowrap">
                        {{ $po->po_number }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">
                        {{ $po->order_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $po->supplier->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs font-mono text-slate-500 whitespace-nowrap">
                        {{ $po->purchaseRequest->request_number ?? 'Langsung' }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-medium text-slate-900 whitespace-nowrap">
                        Rp {{ number_format($po->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($po->status === 'received')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Diterima Penuh
                            </span>
                        @elseif($po->status === 'sent')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                Terkirim ke Supplier
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                {{ ucfirst($po->status) }}
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        Belum ada pesanan pembelian (Purchase Order) yang diterbitkan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
