@extends('layouts.app')

@section('title', 'Penawaran Harga (Quotations) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Penawaran Harga (Quotations)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengelolaan penawaran harga resmi kepada prospek atau pelanggan sebelum penerbitan pesanan</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('quotations.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Penawaran Baru
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
                    <th class="py-3 px-6 w-36 whitespace-nowrap">No. Penawaran</th>
                    <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal</th>
                    <th class="py-3 px-6">Pelanggan</th>
                    <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Berlaku Hingga</th>
                    <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Total Nilai</th>
                    <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Status</th>
                    <th class="py-3 px-6 w-32 text-center whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($quotations as $q)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600 whitespace-nowrap">
                        {{ $q->quotation_number }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">
                        {{ $q->quotation_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $q->customer->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-center text-slate-600 font-mono whitespace-nowrap">
                        {{ $q->valid_until->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-medium text-slate-900 whitespace-nowrap">
                        Rp {{ number_format($q->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($q->status === 'converted')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Jadi Order (SO)
                            </span>
                        @elseif($q->status === 'sent')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                Terkirim
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                {{ ucfirst($q->status) }}
                            </span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($q->status !== 'converted')
                        <form method="POST" action="{{ route('quotations.convert', $q->id) }}" onsubmit="return confirm('Konversi penawaran ini menjadi Sales Order resmi?')">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium text-xs border border-blue-200 transition-colors">
                                Konversi ke SO →
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-slate-400">Telah Dikonversi</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        Belum ada penawaran harga yang diterbitkan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($quotations->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $quotations->links() }}
    </div>
    @endif
</div>
@endsection
