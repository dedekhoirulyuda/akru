@extends('layouts.app')

@section('title', 'Laporan Analisis Margin & Profitabilitas — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Laporan Analisis Margin & Profitabilitas</h1>
        <p class="text-sm text-slate-500 mt-0.5">Evaluasi perolehan pendapatan, harga pokok penjualan (HPP), laba kotor, dan persentase margin per produk</p>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800 text-sm">Profitabilitas Per Produk & Jasa</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-32 whitespace-nowrap">SKU Produk</th>
                    <th class="py-3 px-6">Nama Produk / Item</th>
                    <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Qty Terjual</th>
                    <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Total Omzet</th>
                    <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Total HPP</th>
                    <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Laba Kotor (Gross)</th>
                    <th class="py-3 px-6 w-32 text-center whitespace-nowrap">Margin (%)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($itemProfitability as $row)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono text-xs text-blue-600 font-bold whitespace-nowrap">
                        {{ $row['sku'] }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $row['name'] }}
                    </td>
                    <td class="py-3.5 px-6 text-center font-mono whitespace-nowrap">
                        {{ number_format($row['qty'], 0) }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono whitespace-nowrap">
                        Rp {{ number_format($row['revenue'], 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono text-slate-500 whitespace-nowrap">
                        Rp {{ number_format($row['cogs'], 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-bold text-emerald-600 whitespace-nowrap">
                        Rp {{ number_format($row['gross_profit'], 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $row['margin_percent'] >= 30 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                            {{ $row['margin_percent'] }}%
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        Belum ada data transaksi penjualan terposting untuk dihitung analitiknya.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
