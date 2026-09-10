@extends('layouts.app')

@section('title', 'Surat Jalan & Pengiriman (Delivery Orders) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Surat Jalan & Pengiriman (Delivery Orders)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Dokumen pengeluaran fisik barang dari gudang logistik terpisah dari penerbitan faktur tagihan</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('deliveries.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Surat Jalan Baru
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
                    <th class="py-3 px-6 w-36 whitespace-nowrap">No. Surat Jalan</th>
                    <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal Kirim</th>
                    <th class="py-3 px-6">Pelanggan</th>
                    <th class="py-3 px-6 w-36 whitespace-nowrap">Gudang Asal</th>
                    <th class="py-3 px-6 w-36 whitespace-nowrap">Pengemudi / Nopol</th>
                    <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($deliveries as $do)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600 whitespace-nowrap">
                        {{ $do->delivery_number }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">
                        {{ $do->delivery_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $do->customer->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-700 whitespace-nowrap">
                        {{ $do->warehouse->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 whitespace-nowrap">
                        {{ $do->driver_name ?? '-' }} ({{ $do->vehicle_number ?? '-' }})
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200">
                            {{ ucfirst($do->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        Belum ada surat jalan (Delivery Order) yang diterbitkan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($deliveries->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $deliveries->links() }}
    </div>
    @endif
</div>
@endsection
