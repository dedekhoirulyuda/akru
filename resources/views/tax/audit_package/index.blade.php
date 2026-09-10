@extends('layouts.app')

@section('title', 'Kertas Kerja Pemeriksaan Pajak (Tax Audit Package) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kertas Kerja Pemeriksaan Pajak (Tax Audit Package)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengumpulan berkas digital audit terpadu: Jurnal Umum, Faktur Penjualan/Pembelian, Bukti Potong, dan Rekonsiliasi dengan Checksum</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Ekspor Bundel Kertas Kerja (Audit Bundle)
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Filter Masa Pajak & Integrity Manifest -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form method="GET" action="{{ route('tax.audit-package') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Masa Pajak:</label>
                <input type="month" name="period" value="{{ $taxPeriod }}" class="text-sm rounded-lg border-slate-300 font-mono">
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold">Terapkan</button>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">Manifest SHA-256 Checksum:</span>
                <code class="px-2 py-1 bg-slate-100 text-slate-800 rounded font-mono text-xs border border-slate-200">{{ substr($packageChecksum, 0, 16) }}...</code>
            </div>
        </form>
    </div>

    <!-- Summary of Documents Bundled in this Audit Package -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Jurnal GL Terposting</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-2">{{ $journals }} Baris</p>
            <span class="text-[11px] text-emerald-600 font-medium">Lolos audit balanced</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Faktur Penjualan (PPN Out)</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-2">{{ $salesCount }} Dokumen</p>
            <span class="text-[11px] text-blue-600 font-medium">Tersedia lampiran faktur</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Faktur Pembelian (PPN In)</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-2">{{ $purchaseCount }} Dokumen</p>
            <span class="text-[11px] text-blue-600 font-medium">Tercatat di subledger</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Tax Entries</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-2">{{ $taxEntries->count() }} Data</p>
            <span class="text-[11px] text-purple-600 font-medium">Validasi Coretax ready</span>
        </div>
    </div>

    <!-- Table of Tax Subledger Entries in Package -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50">
            <h2 class="font-semibold text-slate-800 text-sm">Rincian Dokumen Pajak Siap Diperiksa (Tax Subledger Manifest)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal</th>
                        <th class="py-3 px-6 w-28 whitespace-nowrap">Jenis Pajak</th>
                        <th class="py-3 px-6 w-32 whitespace-nowrap">Arah Pajak</th>
                        <th class="py-3 px-6">Lawan Transaksi</th>
                        <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Dasar Pengenaan (DPP)</th>
                        <th class="py-3 px-6 w-32 text-right whitespace-nowrap">Nilai Pajak</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($taxEntries as $te)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-mono text-xs text-slate-600 whitespace-nowrap">
                            {{ $te->tax_date->format('d/m/Y') }}
                        </td>
                        <td class="py-3.5 px-6 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                {{ $te->tax_type }}
                            </span>
                        </td>
                        <td class="py-3.5 px-6 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $te->direction === 'output' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                {{ $te->direction === 'output' ? 'Keluaran' : 'Masukan' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-slate-900 font-medium">
                            {{ $te->counterparty_name ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono whitespace-nowrap text-slate-700">
                            Rp {{ number_format($te->base_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                            Rp {{ number_format($te->tax_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            Tidak ada entri pajak pada masa periode terpilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
