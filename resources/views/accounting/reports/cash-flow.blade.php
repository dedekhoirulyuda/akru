@extends('layouts.app')

@section('title', 'Laporan Arus Kas (Cash Flow) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Laporan Arus Kas (Cash Flow)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Analisis arus kas masuk dan keluar dari seluruh rekening kas dan bank entitas</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('reports.cash-flow.excel', request()->query())" 
            :pdf-url="route('reports.cash-flow.pdf', request()->query())" 
            label="Export Arus Kas" />
    </div>
</div>
@endsection

@section('content')
<!-- Filter Box -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('reports.cash-flow') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
            Tampilkan Arus Kas
        </button>
    </form>
</div>

<!-- Cash Flow Statement Card -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 max-w-4xl mx-auto">
    <div class="text-center pb-6 border-b border-slate-200">
        <h2 class="text-xl font-bold text-slate-900">{{ session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</h2>
        <h3 class="text-base font-semibold text-slate-700 uppercase tracking-wide mt-1">Laporan Arus Kas</h3>
        <p class="text-xs text-slate-500 mt-1">Periode: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</p>
    </div>

    <!-- Saldo Awal Kas -->
    <div class="py-4 border-b border-slate-200 flex justify-between font-bold text-slate-900 text-base">
        <span>SALDO AWAL KAS & BANK</span>
        <span class="font-mono">Rp {{ number_format($openingCash, 0, ',', '.') }}</span>
    </div>

    <div class="divide-y divide-slate-100 text-sm mt-4">
        <!-- ARUS KAS MASUK -->
        <div class="py-4">
            <div class="font-bold text-emerald-800 uppercase text-xs tracking-wider mb-2">Arus Kas Masuk (Penerimaan)</div>
            @forelse($receipts as $r)
            <div class="flex justify-between py-1.5 px-4 text-slate-700">
                <span class="capitalize">Penerimaan dari {{ str_replace('_', ' ', $r->source_type) }}</span>
                <span class="font-mono text-emerald-600 font-medium">+ Rp {{ number_format($r->amount, 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="py-1 px-4 text-slate-400 italic text-xs">Tidak ada arus kas masuk pada periode ini.</div>
            @endforelse
            <div class="flex justify-between py-2 px-4 font-semibold text-slate-900 bg-emerald-50/50 rounded-lg mt-2">
                <span>Total Kas Masuk</span>
                <span class="font-mono text-emerald-700">+ Rp {{ number_format($totalIn, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- ARUS KAS KELUAR -->
        <div class="py-4">
            <div class="font-bold text-rose-800 uppercase text-xs tracking-wider mb-2">Arus Kas Keluar (Pengeluaran)</div>
            @forelse($disbursements as $d)
            <div class="flex justify-between py-1.5 px-4 text-slate-700">
                <span class="capitalize">Pengeluaran untuk {{ str_replace('_', ' ', $d->source_type) }}</span>
                <span class="font-mono text-rose-600 font-medium">- Rp {{ number_format($d->amount, 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="py-1 px-4 text-slate-400 italic text-xs">Tidak ada arus kas keluar pada periode ini.</div>
            @endforelse
            <div class="flex justify-between py-2 px-4 font-semibold text-slate-900 bg-rose-50/50 rounded-lg mt-2">
                <span>Total Kas Keluar</span>
                <span class="font-mono text-rose-700">- Rp {{ number_format($totalOut, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- KENAIKAN / PENURUNAN KAS BERSIH -->
        <div class="py-4 px-4 bg-slate-50 rounded-xl my-2 flex justify-between font-bold text-slate-900">
            <span>KENAIKAN (PENURUNAN) KAS BERSIH</span>
            <span class="font-mono {{ $netCashFlow >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                Rp {{ number_format($netCashFlow, 0, ',', '.') }}
            </span>
        </div>

        <!-- SALDO AKHIR KAS -->
        <div class="py-4 px-4 bg-blue-50 border-2 border-blue-300 rounded-xl mt-4 flex justify-between items-center font-bold text-lg text-blue-900">
            <span>SALDO AKHIR KAS & BANK</span>
            <span class="font-mono text-xl">Rp {{ number_format($closingCash, 0, ',', '.') }}</span>
        </div>
    </div>
</div>
@endsection
