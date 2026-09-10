@extends('layouts.app')

@section('title', 'Laporan Neraca (Balance Sheet) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Laporan Neraca (Balance Sheet)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Posisi keuangan entitas meliputi aset, liabilitas, dan ekuitas per tanggal tertentu</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('reports.balance-sheet.excel', request()->query())" 
            :pdf-url="route('reports.balance-sheet.pdf', request()->query())" 
            label="Export Neraca" />
    </div>
</div>
@endsection

@section('content')
<!-- Filter Box -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('reports.balance-sheet') }}" class="flex items-end gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Posisi Neraca Per Tanggal</label>
            <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
            Tampilkan Neraca
        </button>
    </form>
</div>

<!-- Statement Container -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 max-w-5xl mx-auto">
    <div class="text-center pb-6 border-b border-slate-200">
        <h2 class="text-xl font-bold text-slate-900">{{ session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</h2>
        <h3 class="text-base font-semibold text-slate-700 uppercase tracking-wide mt-1">Laporan Posisi Keuangan (Neraca)</h3>
        <p class="text-xs text-slate-500 mt-1">Per Tanggal: {{ date('d F Y', strtotime($asOfDate)) }}</p>
    </div>

    <!-- Balance Check Alert -->
    <div class="my-6 p-3 rounded-lg flex items-center justify-between text-xs font-semibold {{ $isBalanced ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
        <div>{{ $isBalanced ? '✓ Neraca Seimbang: Total Aset = Total Liabilitas + Ekuitas' : '⚠ Peringatan: Total Aset tidak sama dengan Total Liabilitas + Ekuitas' }}</div>
        <div class="font-mono">Selisih: Rp {{ number_format(abs($totalAssets - $totalLiabilitiesAndEquity), 0, ',', '.') }}</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
        <!-- LEFT COLUMN: ASET -->
        <div class="space-y-6">
            <div class="bg-slate-50 p-3 rounded-lg font-bold text-slate-900 uppercase text-xs tracking-wider border-b border-slate-200">
                ASET (AKTIVA)
            </div>

            <!-- Aset Lancar -->
            <div>
                <h4 class="font-semibold text-slate-800 text-xs uppercase text-slate-500 mb-2">Aset Lancar (Current Assets)</h4>
                <div class="space-y-1">
                    @forelse($currentAssets as $ca)
                    <div class="flex justify-between py-1 px-2 text-slate-700">
                        <span>{{ $ca->code }} — {{ $ca->name }}</span>
                        <span class="font-mono">Rp {{ number_format($ca->net_amount, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div class="py-1 px-2 text-slate-400 italic text-xs">Tidak ada akun aset lancar.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-2 px-2 font-semibold text-slate-900 border-t border-slate-200 mt-2">
                    <span>Subtotal Aset Lancar</span>
                    <span class="font-mono">Rp {{ number_format($totalCurrentAssets, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Aset Tetap -->
            <div>
                <h4 class="font-semibold text-slate-800 text-xs uppercase text-slate-500 mb-2">Aset Tidak Lancar / Tetap</h4>
                <div class="space-y-1">
                    @forelse($fixedAssets as $fa)
                    <div class="flex justify-between py-1 px-2 text-slate-700">
                        <span>{{ $fa->code }} — {{ $fa->name }}</span>
                        <span class="font-mono">Rp {{ number_format($fa->net_amount, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div class="py-1 px-2 text-slate-400 italic text-xs">Tidak ada akun aset tetap.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-2 px-2 font-semibold text-slate-900 border-t border-slate-200 mt-2">
                    <span>Subtotal Aset Tetap</span>
                    <span class="font-mono">Rp {{ number_format($totalFixedAssets, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- TOTAL ASET -->
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl flex justify-between font-bold text-base text-blue-900">
                <span>TOTAL ASET</span>
                <span class="font-mono">Rp {{ number_format($totalAssets, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- RIGHT COLUMN: LIABILITAS & EKUITAS -->
        <div class="space-y-6">
            <div class="bg-slate-50 p-3 rounded-lg font-bold text-slate-900 uppercase text-xs tracking-wider border-b border-slate-200">
                LIABILITAS & EKUITAS (PASIVA)
            </div>

            <!-- Liabilitas Lancar -->
            <div>
                <h4 class="font-semibold text-slate-800 text-xs uppercase text-slate-500 mb-2">Liabilitas Jangka Pendek</h4>
                <div class="space-y-1">
                    @forelse($currentLiabilities as $cl)
                    <div class="flex justify-between py-1 px-2 text-slate-700">
                        <span>{{ $cl->code }} — {{ $cl->name }}</span>
                        <span class="font-mono">Rp {{ number_format($cl->net_amount, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div class="py-1 px-2 text-slate-400 italic text-xs">Tidak ada liabilitas lancar.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-2 px-2 font-semibold text-slate-900 border-t border-slate-200 mt-2">
                    <span>Subtotal Liabilitas Lancar</span>
                    <span class="font-mono">Rp {{ number_format($totalCurrentLiabilities, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Liabilitas Jangka Panjang -->
            @if($longTermLiabilities->count() > 0)
            <div>
                <h4 class="font-semibold text-slate-800 text-xs uppercase text-slate-500 mb-2">Liabilitas Jangka Panjang</h4>
                <div class="space-y-1">
                    @foreach($longTermLiabilities as $ll)
                    <div class="flex justify-between py-1 px-2 text-slate-700">
                        <span>{{ $ll->code }} — {{ $ll->name }}</span>
                        <span class="font-mono">Rp {{ number_format($ll->net_amount, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="flex justify-between py-2 px-2 font-semibold text-slate-900 border-t border-slate-200 mt-2">
                    <span>Subtotal Liabilitas Jangka Panjang</span>
                    <span class="font-mono">Rp {{ number_format($totalLongTermLiabilities, 0, ',', '.') }}</span>
                </div>
            </div>
            @endif

            <!-- Ekuitas -->
            <div>
                <h4 class="font-semibold text-slate-800 text-xs uppercase text-slate-500 mb-2">Ekuitas (Modal)</h4>
                <div class="space-y-1">
                    @foreach($equities as $eq)
                    <div class="flex justify-between py-1 px-2 text-slate-700">
                        <span>{{ $eq->code }} — {{ $eq->name }}</span>
                        <span class="font-mono">Rp {{ number_format($eq->net_amount, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between py-1 px-2 text-slate-700 font-medium">
                        <span>Laba Bersih Tahun Berjalan (Net Income)</span>
                        <span class="font-mono">Rp {{ number_format($netProfitAccumulated, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="flex justify-between py-2 px-2 font-semibold text-slate-900 border-t border-slate-200 mt-2">
                    <span>Total Ekuitas</span>
                    <span class="font-mono">Rp {{ number_format($totalEquity, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- TOTAL LIABILITAS & EKUITAS -->
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex justify-between font-bold text-base text-emerald-900">
                <span>TOTAL LIABILITAS & EKUITAS</span>
                <span class="font-mono">Rp {{ number_format($totalLiabilitiesAndEquity, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
