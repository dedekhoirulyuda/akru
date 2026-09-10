@extends('layouts.app')

@section('title', 'Rekap SPT Masa PPN 1111 — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Rekapitulasi SPT Masa PPN 1111</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pemantauan PPN Keluaran, PPN Masukan yang dapat dikreditkan, dan komputasi Kurang / (Lebih) Bayar</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('tax.coretax', ['tax_period' => $taxPeriod, 'format' => 'xml']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Ekspor XML Coretax
        </a>
    </div>
</div>
@endsection

@section('content')
<!-- Filter Period -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('tax.ppn') }}" class="flex items-end gap-4">
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Masa Pajak (Bulan / Tahun)</label>
            <input type="month" name="tax_period" value="{{ $taxPeriod }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
            Tampilkan Masa Pajak
        </button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- PPN Keluaran Card -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase text-slate-400">PPN Keluaran (Penjualan)</span>
            <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-blue-50 text-blue-700 font-semibold">FORM 1111 A2</span>
        </div>
        <div class="text-2xl font-bold font-mono text-blue-700 mt-2">
            Rp {{ number_format($ppnKeluaran, 0, ',', '.') }}
        </div>
        <div class="text-xs text-slate-500 mt-1">DPP: Rp {{ number_format($dppKeluaran, 0, ',', '.') }} ({{ $outputEntries->count() }} Faktur)</div>
    </div>

    <!-- PPN Masukan Card -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase text-slate-400">PPN Masukan (Dapat Dikreditkan)</span>
            <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-emerald-50 text-emerald-700 font-semibold">FORM 1111 B2</span>
        </div>
        <div class="text-2xl font-bold font-mono text-emerald-700 mt-2">
            Rp {{ number_format($ppnMasukan, 0, ',', '.') }}
        </div>
        <div class="text-xs text-slate-500 mt-1">DPP: Rp {{ number_format($dppMasukan, 0, ',', '.') }} ({{ $inputEntries->count() }} Faktur)</div>
    </div>

    <!-- PPN Kurang/Lebih Bayar Card -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase text-slate-400">PPN Kurang / (Lebih) Bayar</span>
            <span class="px-2 py-0.5 rounded text-[11px] font-mono {{ $selisihPpn >= 0 ? 'bg-amber-50 text-amber-700' : 'bg-purple-50 text-purple-700' }} font-semibold">
                INDUK 1111
            </span>
        </div>
        <div class="text-2xl font-bold font-mono {{ $selisihPpn >= 0 ? 'text-amber-600' : 'text-purple-600' }} mt-2">
            Rp {{ number_format(abs($selisihPpn), 0, ',', '.') }}
        </div>
        <div class="text-xs font-semibold mt-1 {{ $selisihPpn >= 0 ? 'text-amber-700' : 'text-purple-700' }}">
            {{ $selisihPpn >= 0 ? 'PPN Kurang Bayar (Wajib disetor sebelum akhir bulan berikutnya)' : 'PPN Lebih Bayar (Dapat dikompensasikan ke masa berikutnya)' }}
        </div>
    </div>
</div>

<!-- Detailed Tables -->
<div x-data="{ activeTab: 'output' }" class="space-y-4">
    <div class="flex items-center gap-3 border-b border-slate-200">
        <button @click="activeTab = 'output'" :class="activeTab === 'output' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 text-sm border-b-2 transition-colors flex items-center gap-2">
            <span>Daftar Faktur Pajak Keluaran (Penjualan)</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-blue-100 text-blue-800">{{ $outputEntries->count() }}</span>
        </button>
        <button @click="activeTab = 'input'" :class="activeTab === 'input' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 text-sm border-b-2 transition-colors flex items-center gap-2">
            <span>Daftar Faktur Pajak Masukan (Pembelian)</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-emerald-100 text-emerald-800">{{ $inputEntries->count() }}</span>
        </button>
    </div>

    <!-- Tab Output -->
    <div x-show="activeTab === 'output'" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-32">Tanggal</th>
                        <th class="py-3 px-6 w-36">No. Faktur</th>
                        <th class="py-3 px-6">Nama Pembeli</th>
                        <th class="py-3 px-6 w-40">NPWP / NIK</th>
                        <th class="py-3 px-6 w-36 text-right">DPP (Rp)</th>
                        <th class="py-3 px-6 w-20 text-center">Tarif</th>
                        <th class="py-3 px-6 w-36 text-right">PPN Terutang (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($outputEntries as $out)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-mono text-xs text-slate-600">{{ $out->tax_date->format('d/m/Y') }}</td>
                        <td class="py-3.5 px-6 font-mono text-xs font-medium text-blue-600">{{ $out->invoice_number ?? '-' }}</td>
                        <td class="py-3.5 px-6 font-medium text-slate-900">{{ $out->counterparty_name ?? ($out->contact->name ?? '-') }}</td>
                        <td class="py-3.5 px-6 font-mono text-xs text-slate-500">{{ $out->counterparty_npwp ?? ($out->contact->npwp ?? '00.000.000.0-000.000') }}</td>
                        <td class="py-3.5 px-6 text-right font-mono text-slate-900 font-medium">
                            Rp {{ number_format($out->base_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-center font-mono text-xs text-slate-600">{{ $out->rate }}%</td>
                        <td class="py-3.5 px-6 text-right font-mono text-blue-700 font-bold">
                            Rp {{ number_format($out->tax_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            Tidak ada Faktur Pajak Keluaran untuk masa pajak {{ $taxPeriod }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab Input -->
    <div x-show="activeTab === 'input'" style="display: none;" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-32">Tanggal</th>
                        <th class="py-3 px-6 w-36">No. Faktur</th>
                        <th class="py-3 px-6">Nama Penjual</th>
                        <th class="py-3 px-6 w-40">NPWP / NIK</th>
                        <th class="py-3 px-6 w-36 text-right">DPP (Rp)</th>
                        <th class="py-3 px-6 w-20 text-center">Tarif</th>
                        <th class="py-3 px-6 w-36 text-right">PPN Masukan (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($inputEntries as $in)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-mono text-xs text-slate-600">{{ $in->tax_date->format('d/m/Y') }}</td>
                        <td class="py-3.5 px-6 font-mono text-xs font-medium text-emerald-600">{{ $in->invoice_number ?? '-' }}</td>
                        <td class="py-3.5 px-6 font-medium text-slate-900">{{ $in->counterparty_name ?? ($in->contact->name ?? '-') }}</td>
                        <td class="py-3.5 px-6 font-mono text-xs text-slate-500">{{ $in->counterparty_npwp ?? ($in->contact->npwp ?? '00.000.000.0-000.000') }}</td>
                        <td class="py-3.5 px-6 text-right font-mono text-slate-900 font-medium">
                            Rp {{ number_format($in->base_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-center font-mono text-xs text-slate-600">{{ $in->rate }}%</td>
                        <td class="py-3.5 px-6 text-right font-mono text-emerald-700 font-bold">
                            Rp {{ number_format($in->tax_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            Tidak ada Faktur Pajak Masukan untuk masa pajak {{ $taxPeriod }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
