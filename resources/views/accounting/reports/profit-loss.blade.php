@extends('layouts.app')

@section('title', 'Laporan Laba Rugi (Profit & Loss) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Laporan Laba Rugi (Income Statement)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Kinerja operasional pendapatan, beban pokok penjualan, dan laba bersih periode berjalan</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('reports.profit-loss.excel', request()->query())" 
            :pdf-url="route('reports.profit-loss.pdf', request()->query())" 
            label="Export Laba Rugi" />
    </div>
</div>
@endsection

@section('content')
<!-- Filter Box -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('reports.profit-loss') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
            Tampilkan Laporan
        </button>
    </form>
</div>

<!-- Statement Container -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 max-w-4xl mx-auto">
    <div class="text-center pb-6 border-b border-slate-200">
        <h2 class="text-xl font-bold text-slate-900">{{ session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</h2>
        <h3 class="text-base font-semibold text-slate-700 uppercase tracking-wide mt-1">Laporan Laba Rugi Komprehensif</h3>
        <p class="text-xs text-slate-500 mt-1">Periode: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</p>
    </div>

    <div class="divide-y divide-slate-100 text-sm mt-6">
        <!-- 1. PENDAPATAN USAHA -->
        <div class="py-4">
            <div class="font-bold text-slate-900 uppercase text-xs tracking-wider mb-2">Pendapatan Usaha (Revenue)</div>
            @forelse($revenues as $rev)
            <div class="flex justify-between py-1.5 px-4 text-slate-700">
                <span>{{ $rev->code }} — {{ $rev->name }}</span>
                <span class="font-mono">Rp {{ number_format($rev->net_amount, 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="py-1 px-4 text-slate-400 italic text-xs">Belum ada pendapatan usaha tercatat.</div>
            @endforelse
            <div class="flex justify-between py-2 px-4 font-semibold text-slate-900 bg-slate-50 rounded-lg mt-2">
                <span>Total Pendapatan Usaha</span>
                <span class="font-mono text-blue-700">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- 2. BEBAN POKOK PENJUALAN (HPP) -->
        <div class="py-4">
            <div class="font-bold text-slate-900 uppercase text-xs tracking-wider mb-2">Beban Pokok Penjualan (HPP / COGS)</div>
            @forelse($costOfSales as $cos)
            <div class="flex justify-between py-1.5 px-4 text-slate-700">
                <span>{{ $cos->code }} — {{ $cos->name }}</span>
                <span class="font-mono">Rp {{ number_format($cos->net_amount, 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="py-1 px-4 text-slate-400 italic text-xs">Belum ada beban pokok penjualan tercatat.</div>
            @endforelse
            <div class="flex justify-between py-2 px-4 font-semibold text-slate-900 bg-slate-50 rounded-lg mt-2">
                <span>Total Beban Pokok Penjualan</span>
                <span class="font-mono text-rose-700">(Rp {{ number_format($totalCostOfSales, 0, ',', '.') }})</span>
            </div>
        </div>

        <!-- LABA KOTOR -->
        <div class="py-4 bg-blue-50/50 px-4 rounded-xl my-2">
            <div class="flex justify-between font-bold text-base text-blue-900">
                <span>LABA KOTOR (GROSS PROFIT)</span>
                <span class="font-mono">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- 3. BEBAN OPERASIONAL -->
        <div class="py-4">
            <div class="font-bold text-slate-900 uppercase text-xs tracking-wider mb-2">Beban Operasional & Umum</div>
            @forelse($operatingExpenses as $exp)
            <div class="flex justify-between py-1.5 px-4 text-slate-700">
                <span>{{ $exp->code }} — {{ $exp->name }}</span>
                <span class="font-mono">Rp {{ number_format($exp->net_amount, 0, ',', '.') }}</span>
            </div>
            @empty
            <div class="py-1 px-4 text-slate-400 italic text-xs">Belum ada beban operasional tercatat.</div>
            @endforelse
            <div class="flex justify-between py-2 px-4 font-semibold text-slate-900 bg-slate-50 rounded-lg mt-2">
                <span>Total Beban Operasional</span>
                <span class="font-mono text-rose-700">(Rp {{ number_format($totalOperatingExpense, 0, ',', '.') }})</span>
            </div>
        </div>

        <!-- LABA OPERASIONAL -->
        <div class="py-3 px-4">
            <div class="flex justify-between font-bold text-slate-800">
                <span>LABA OPERASIONAL (EBIT)</span>
                <span class="font-mono">Rp {{ number_format($operatingProfit, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- 4. PENDAPATAN & BEBAN LAIN-LAIN -->
        @if($otherRevenues->count() > 0 || $otherExpenses->count() > 0)
        <div class="py-4">
            <div class="font-bold text-slate-900 uppercase text-xs tracking-wider mb-2">Pendapatan & Beban Lain-Lain</div>
            @foreach($otherRevenues as $or)
            <div class="flex justify-between py-1.5 px-4 text-slate-700">
                <span>{{ $or->code }} — {{ $or->name }}</span>
                <span class="font-mono">Rp {{ number_format($or->net_amount, 0, ',', '.') }}</span>
            </div>
            @endforeach
            @foreach($otherExpenses as $oe)
            <div class="flex justify-between py-1.5 px-4 text-slate-700">
                <span>{{ $oe->code }} — {{ $oe->name }}</span>
                <span class="font-mono">(Rp {{ number_format($oe->net_amount, 0, ',', '.') }})</span>
            </div>
            @endforeach
        </div>
        @endif

        <!-- LABA BERSIH SEBELUM PAJAK -->
        <div class="py-3 px-4">
            <div class="flex justify-between font-bold text-slate-800">
                <span>LABA BERSIH SEBELUM PAJAK</span>
                <span class="font-mono">Rp {{ number_format($netProfitBeforeTax, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- ESTIMASI PAJAK PPH BADAN (22%) -->
        <div class="py-2 px-4 text-slate-600">
            <div class="flex justify-between">
                <span>Estimasi Pajak Penghasilan Badan (PPh Badan 22%)</span>
                <span class="font-mono text-rose-700">(Rp {{ number_format($estimatedTax, 0, ',', '.') }})</span>
            </div>
        </div>

        <!-- LABA BERSIH TAHUN BERJALAN -->
        <div class="py-4 px-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl mt-4">
            <div class="flex justify-between items-center font-bold text-lg text-emerald-900">
                <span>LABA BERSIH SETELAH PAJAK (NET INCOME)</span>
                <span class="font-mono text-xl">Rp {{ number_format($netProfitAfterTax, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
