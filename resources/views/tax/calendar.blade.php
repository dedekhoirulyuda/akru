@extends('layouts.app')

@section('title', 'Kalender Kepatuhan Pajak — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kalender Kepatuhan Pajak</h1>
        <p class="text-sm text-slate-500 mt-0.5">Jadwal jatuh tempo penyetoran dan pelaporan SPT Masa & Tahunan Coretax DJP Indonesia</p>
    </div>

    <!-- Month Navigator -->
    <div class="flex items-center gap-2">
        <form method="GET" action="{{ route('tax.calendar') }}" class="flex items-center gap-2">
            <select name="month" onchange="this.form.submit()" class="text-xs font-semibold rounded-lg border-slate-300 py-1.5 focus:ring-blue-500">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
            <select name="year" onchange="this.form.submit()" class="text-xs font-semibold rounded-lg border-slate-300 py-1.5 focus:ring-blue-500">
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- KPI Tax Liabilities Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">PPN Kurang Bayar (Masa Ini)</div>
            <div class="text-xl font-extrabold text-blue-600 font-mono">
                Rp {{ number_format($ppnPayable, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Setor & Lapor akhir bulan depan</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Estimasi PPh 21 Terutang</div>
            <div class="text-xl font-extrabold text-purple-600 font-mono">
                Rp {{ number_format($pph21Total, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Setor tgl 10, Lapor tgl 20</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Estimasi PPh 23 (Jasa)</div>
            <div class="text-xl font-extrabold text-amber-600 font-mono">
                Rp {{ number_format($pph23Total, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">e-Bupot Unifikasi DJP</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Estimasi PPh Final 4(2)</div>
            <div class="text-xl font-extrabold text-emerald-600 font-mono">
                Rp {{ number_format($pph42Total, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Sewa & Jasa Konstruksi</div>
        </div>
    </div>

    <!-- Main Tax Compliance Schedule Cards -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-slate-900 text-base">Jadwal Kewajiban Pajak Masa Pajak: {{ $selectedDate->translatedFormat('F Y') }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pantau tenggat waktu agar entitas terhindar dari sanksi administrasi keterlambatan bunga & denda pasal 7 UU KUP</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 self-start sm:self-auto">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                Coretax Compliance Ready
            </span>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($schedules as $item)
            <div class="p-6 hover:bg-slate-50/60 transition-colors">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wide">
                                {{ $item['category'] }}
                            </span>
                            <span class="text-xs text-slate-400 font-mono">{{ $item['form'] }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">{{ $item['tax_name'] }}</h3>
                        <p class="text-xs text-slate-600 max-w-2xl">{{ $item['description'] }}</p>
                        <div class="text-xs text-slate-500 flex items-center gap-1">
                            <span class="font-semibold">Kanal Pelaporan:</span>
                            <span class="text-blue-600 font-medium">{{ $item['portal'] }}</span>
                        </div>
                    </div>

                    <!-- Deadlines & Countdowns -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 shrink-0 lg:w-96">
                        <!-- Deposit Deadline -->
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                            <div class="text-[11px] uppercase font-semibold text-slate-500 flex items-center justify-between mb-1">
                                <span>Batas Penyetoran</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $item['deposit_color'] === 'rose' ? 'bg-rose-100 text-rose-700' : ($item['deposit_color'] === 'amber' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ $item['deposit_status'] }}
                                </span>
                            </div>
                            <div class="text-sm font-bold text-slate-900 font-mono">
                                {{ $item['deposit_deadline']?->translatedFormat('d F Y') }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1">
                                Pembayaran Billing NTPN
                            </div>
                        </div>

                        <!-- Filing Deadline -->
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                            <div class="text-[11px] uppercase font-semibold text-slate-500 flex items-center justify-between mb-1">
                                <span>Batas Pelaporan</span>
                                @if($item['filing_deadline'])
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $item['filing_color'] === 'rose' ? 'bg-rose-100 text-rose-700' : ($item['filing_color'] === 'amber' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">
                                        {{ $item['filing_status'] }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-200 text-slate-600">
                                        Auto Lapor
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm font-bold text-slate-900 font-mono">
                                {{ $item['filing_deadline']?->translatedFormat('d F Y') ?? 'Otomatis Validasi NTPN' }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1">
                                {{ $item['filing_deadline'] ? 'Submit SPT Masa' : 'Tidak perlu submit SPT' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Regulatory Advisory Card -->
    <div class="bg-gradient-to-r from-blue-900 to-indigo-950 rounded-2xl p-6 text-white shadow-sm border border-blue-800/50">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-1">
                <h3 class="text-base font-bold flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pedoman Kepatuhan Pajak Sistem Coretax DJP
                </h3>
                <p class="text-xs text-blue-200 max-w-3xl">
                    Dalam era Coretax DJP, seluruh penyetoran menggunakan Akun Deposit Pajak terintegrasi. Keterlambatan penyetoran dikenakan sanksi bunga acuan per bulan sesuai KMK yang berlaku, dan denda keterlambatan SPT Masa PPN sebesar Rp 500.000 serta SPT Masa Lainnya Rp 100.000.
                </p>
            </div>
            <div class="shrink-0">
                <a href="{{ route('tax.coretax') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white text-blue-900 text-xs font-bold hover:bg-blue-50 shadow-xs transition-colors">
                    Ekspor Data Coretax XML
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
