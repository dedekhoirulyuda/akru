@extends('layouts.app')

@section('title', 'Faktur Penjualan (Sales Invoices) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Faktur Penjualan (Sales Invoices)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Penerbitan faktur tagihan pelanggan, perhitungan PPN otomatis, dan posting ke Buku Besar</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('sales.export.excel')" 
            :pdf-url="route('sales.export.pdf')" 
            label="Export Faktur" />
        <a href="{{ route('receipts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 shadow-sm transition-colors">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Catat Penerimaan
        </a>
        <a href="{{ route('sales.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Faktur Baru
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
                    <th class="py-3 px-6 w-36">No. Faktur</th>
                    <th class="py-3 px-6 w-28">Tanggal</th>
                    <th class="py-3 px-6">Pelanggan</th>
                    <th class="py-3 px-6 w-28 text-center">Jatuh Tempo</th>
                    <th class="py-3 px-6 w-36 text-right">Total Tagihan</th>
                    <th class="py-3 px-6 w-36 text-right">Sisa Piutang</th>
                    <th class="py-3 px-6 w-28 text-center">Status</th>
                    <th class="py-3 px-6 w-20 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($invoices as $inv)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium">
                        <a href="{{ route('sales.show', $inv) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $inv->invoice_number }}
                        </a>
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono">
                        {{ $inv->invoice_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $inv->contact->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-center text-xs font-mono text-slate-500">
                        {{ $inv->due_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-medium text-slate-900">
                        Rp {{ number_format($inv->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-medium {{ $inv->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        @php
                            $statusMap = [
                                'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700'],
                                'submitted' => ['label' => 'Menunggu', 'class' => 'bg-amber-100 text-amber-800'],
                                'posted' => ['label' => 'Belum Lunas', 'class' => 'bg-blue-100 text-blue-800'],
                                'partially_paid' => ['label' => 'Sebagian', 'class' => 'bg-orange-100 text-orange-800'],
                                'paid' => ['label' => 'Lunas', 'class' => 'bg-emerald-100 text-emerald-800'],
                                'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-rose-100 text-rose-800'],
                            ];
                            $st = $statusMap[$inv->status] ?? ['label' => $inv->status, 'class' => 'bg-slate-100 text-slate-700'];
                        @endphp
                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full {{ $st['class'] }}">
                            {{ $st['label'] }}
                        </span>
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        <a href="{{ route('sales.show', $inv) }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-10 text-center text-slate-400">
                        Belum ada faktur penjualan. Klik "Buat Faktur Baru" untuk memulai transaksi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection
