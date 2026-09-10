@extends('layouts.app')

@section('title', 'Coretax-Ready Data Export — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">DJP Coretax-Ready Data Center</h1>
        <p class="text-sm text-slate-500 mt-0.5">Ekspor dataset perpajakan terstandarisasi skema DJP Coretax (Faktur Pajak & Bukti Potong Unifikasi)</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('tax.coretax', ['tax_period' => $taxPeriod, 'format' => 'xml']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Unduh Berkas XML Coretax
        </a>
    </div>
</div>
@endsection

@section('content')
<!-- Filter Period -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('tax.coretax') }}" class="flex items-end gap-4">
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Masa Pajak Coretax</label>
            <input type="month" name="tax_period" value="{{ $taxPeriod }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
            Tampilkan Dataset
        </button>
    </form>
</div>

<!-- Standard Coretax Compliance Banner -->
<div class="p-4 rounded-xl border border-blue-200 bg-blue-50/60 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <div>
            <div class="font-bold text-sm text-blue-900">Validasi Standar DJP Coretax Indonesia</div>
            <div class="text-xs text-blue-700">Format data kompatibel dengan NIK/NPWP 16-Digit (PMK 112/2022) & Skema XML Portal Wajib Pajak Coretax</div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span class="px-3 py-1 rounded-full text-xs font-mono font-semibold bg-blue-200/70 text-blue-800">
            SCHEMA v2026.1 READY
        </span>
    </div>
</div>

<!-- Dataset Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800 text-sm">Pratinjau Dataset Transaksi Perpajakan ({{ $entries->count() }} Dokumen)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-4 w-12 text-center">#</th>
                    <th class="py-3 px-4 w-28">Tanggal</th>
                    <th class="py-3 px-4 w-32">Jenis Pajak</th>
                    <th class="py-3 px-4 w-24 text-center">Arah</th>
                    <th class="py-3 px-4 w-36">No. Faktur / Dokumen</th>
                    <th class="py-3 px-4">Lawan Transaksi</th>
                    <th class="py-3 px-4 w-44">NPWP 16-Digit / NIK</th>
                    <th class="py-3 px-4 w-32 text-right">DPP (Rp)</th>
                    <th class="py-3 px-4 w-32 text-right">Pajak (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($entries as $index => $e)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3 px-4 text-center text-xs text-slate-400 font-mono">{{ $index + 1 }}</td>
                    <td class="py-3 px-4 font-mono text-xs text-slate-600">{{ $e->tax_date->format('d/m/Y') }}</td>
                    <td class="py-3 px-4 font-mono text-xs font-bold text-slate-800">{{ $e->tax_type }}</td>
                    <td class="py-3 px-4 text-center">
                        @if($e->direction === 'output')
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-blue-50 text-blue-700 font-medium">Keluaran</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-emerald-50 text-emerald-700 font-medium">Masukan</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 font-mono text-xs text-slate-700">{{ $e->invoice_number ?? '-' }}</td>
                    <td class="py-3 px-4 font-medium text-slate-900">{{ $e->counterparty_name ?? ($e->contact->name ?? 'UMUM') }}</td>
                    <td class="py-3 px-4 font-mono text-xs text-slate-600">{{ $e->counterparty_npwp ?? ($e->contact->npwp ?? '0000000000000000') }}</td>
                    <td class="py-3 px-4 text-right font-mono text-slate-900 font-medium">
                        Rp {{ number_format($e->base_amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-4 text-right font-mono text-blue-700 font-bold">
                        Rp {{ number_format($e->tax_amount, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-12 text-center text-slate-400">
                        Tidak ada catatan pajak untuk masa {{ $taxPeriod }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($entries->count() > 0)
            <tfoot>
                <tr class="bg-slate-50 font-bold border-t-2 border-slate-300 text-slate-900 text-xs">
                    <td colspan="7" class="py-3 px-4 uppercase text-right">Total DPP & Pajak:</td>
                    <td class="py-3 px-4 text-right font-mono text-slate-900">
                        Rp {{ number_format($entries->sum('base_amount'), 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-4 text-right font-mono text-blue-800">
                        Rp {{ number_format($entries->sum('tax_amount'), 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
