@extends('layouts.app')

@section('title', 'Proyeksi Arus Kas (Cash-Flow Forecast) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Proyeksi Arus Kas (Cash-Flow Forecast)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Prediksi posisi likuiditas kas 30, 60, dan 90 hari ke depan berdasarkan jadwal jatuh tempo piutang & kewajiban</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold">
            Saldo Kas Riil Saat Ini: Rp {{ number_format($currentCash, 0, ',', '.') }}
        </span>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Top Summary Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($forecast as $key => $f)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $f['label'] }}</span>
                    <span class="text-[11px] font-mono text-slate-400">{{ $f['date_range'] }}</span>
                </div>
                <div class="mt-3 space-y-2 text-xs">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Estimasi Kas Masuk (AR):</span>
                        <span class="font-mono font-bold text-emerald-600">+ Rp {{ number_format($f['expected_inflow'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Estimasi Kas Keluar (AP):</span>
                        <span class="font-mono font-bold text-rose-600">- Rp {{ number_format($f['expected_outflow'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-100 font-semibold text-slate-800">
                        <span>Arus Kas Bersih:</span>
                        <span class="font-mono {{ $f['net_flow'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $f['net_flow'] >= 0 ? '+' : '' }} Rp {{ number_format($f['net_flow'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50 p-2.5 rounded-lg">
                <span class="text-xs font-semibold text-slate-700">Estimasi Saldo Kas Akhir:</span>
                <span class="font-mono font-bold text-blue-600 text-sm">
                    Rp {{ number_format($f['projected_balance'], 0, ',', '.') }}
                </span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Detailed Projection Insights Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50">
            <h2 class="font-semibold text-slate-800 text-sm">Rincian Skedul Likuiditas Kas</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4 text-sm text-slate-600 leading-relaxed">
                <div class="p-4 rounded-xl bg-blue-50/60 border border-blue-100 text-blue-900 text-xs flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <strong class="font-semibold text-blue-800">Catatan Perhitungan Proyeksi Arus Kas:</strong>
                        <p class="mt-1">
                            Perhitungan arus kas masuk didasarkan pada total faktur penjualan aktif yang memiliki sisa piutang sesuai tanggal jatuh tempo pembayaran pelanggan.
                            Arus kas keluar didasarkan pada tagihan pembelian pemasok yang belum lunas. Angka proyeksi ini diperbarui secara otomatis setiap kali ada transaksi penjualan, pembelian, atau pembayaran baru.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
