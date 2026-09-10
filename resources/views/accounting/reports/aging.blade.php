@extends('layouts.app')

@section('title', 'Laporan Umur Piutang & Hutang (Aging Report) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Laporan Umur Piutang & Hutang (Aging)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Analisis jatuh tempo piutang pelanggan dan hutang pemasok berdasarkan bucket keterlambatan</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('reports.aging.excel', request()->query())" 
            :pdf-url="route('reports.aging.pdf', request()->query())" 
            label="Export Aging" />
    </div>
</div>
@endsection

@section('content')
<div x-data="{ activeTab: 'ar' }">
    <!-- Tabs Navigation -->
    <div class="flex items-center gap-3 border-b border-slate-200 mb-6">
        <button @click="activeTab = 'ar'" :class="activeTab === 'ar' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 text-sm border-b-2 transition-colors flex items-center gap-2">
            <span>Umur Piutang Pelanggan (AR Aging)</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-blue-100 text-blue-800">{{ $arAging->count() }}</span>
        </button>
        <button @click="activeTab = 'ap'" :class="activeTab === 'ap' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 text-sm border-b-2 transition-colors flex items-center gap-2">
            <span>Umur Hutang Pemasok (AP Aging)</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-amber-100 text-amber-800">{{ $apAging->count() }}</span>
        </button>
    </div>

    <!-- TAB 1: AR AGING -->
    <div x-show="activeTab === 'ar'" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-32">No. Faktur</th>
                        <th class="py-3 px-4">Nama Pelanggan</th>
                        <th class="py-3 px-4 w-28 text-center">Jatuh Tempo</th>
                        <th class="py-3 px-4 w-32 text-right">Total Sisa</th>
                        <th class="py-3 px-4 w-28 text-right bg-emerald-50/50">Current</th>
                        <th class="py-3 px-4 w-28 text-right bg-amber-50/50">1-30 Hari</th>
                        <th class="py-3 px-4 w-28 text-right bg-orange-50/50">31-60 Hari</th>
                        <th class="py-3 px-4 w-28 text-right bg-rose-50/50">61-90 Hari</th>
                        <th class="py-3 px-4 w-28 text-right bg-rose-100/50">> 90 Hari</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($arAging as $row)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono font-medium text-blue-600">{{ $row['number'] }}</td>
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $row['contact'] }}</td>
                        <td class="py-3 px-4 text-center font-mono text-xs text-slate-500">{{ $row['due_date']->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            Rp {{ number_format($row['remaining'], 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-emerald-700 bg-emerald-50/20">
                            {{ $row['bucket_current'] > 0 ? number_format($row['bucket_current'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-amber-700 bg-amber-50/20">
                            {{ $row['bucket_1_30'] > 0 ? number_format($row['bucket_1_30'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-orange-700 bg-orange-50/20">
                            {{ $row['bucket_31_60'] > 0 ? number_format($row['bucket_31_60'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-rose-700 bg-rose-50/20">
                            {{ $row['bucket_61_90'] > 0 ? number_format($row['bucket_61_90'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs font-bold text-rose-800 bg-rose-100/20">
                            {{ $row['bucket_over_90'] > 0 ? number_format($row['bucket_over_90'], 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            Tidak ada saldo piutang yang belum terbayar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($arAging->count() > 0)
                <tfoot>
                    <tr class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900 text-xs">
                        <td colspan="3" class="py-3 px-4 uppercase text-right">Total Piutang:</td>
                        <td class="py-3 px-4 text-right font-mono text-blue-800">
                            Rp {{ number_format($arAging->sum('remaining'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-800">
                            {{ number_format($arAging->sum('bucket_current'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-amber-800">
                            {{ number_format($arAging->sum('bucket_1_30'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-orange-800">
                            {{ number_format($arAging->sum('bucket_31_60'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-rose-800">
                            {{ number_format($arAging->sum('bucket_61_90'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-rose-900">
                            {{ number_format($arAging->sum('bucket_over_90'), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- TAB 2: AP AGING -->
    <div x-show="activeTab === 'ap'" style="display: none;" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-32">No. Tagihan</th>
                        <th class="py-3 px-4">Nama Pemasok</th>
                        <th class="py-3 px-4 w-28 text-center">Jatuh Tempo</th>
                        <th class="py-3 px-4 w-32 text-right">Total Sisa</th>
                        <th class="py-3 px-4 w-28 text-right bg-emerald-50/50">Current</th>
                        <th class="py-3 px-4 w-28 text-right bg-amber-50/50">1-30 Hari</th>
                        <th class="py-3 px-4 w-28 text-right bg-orange-50/50">31-60 Hari</th>
                        <th class="py-3 px-4 w-28 text-right bg-rose-50/50">61-90 Hari</th>
                        <th class="py-3 px-4 w-28 text-right bg-rose-100/50">> 90 Hari</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($apAging as $row)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono font-medium text-amber-600">{{ $row['number'] }}</td>
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $row['contact'] }}</td>
                        <td class="py-3 px-4 text-center font-mono text-xs text-slate-500">{{ $row['due_date']->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            Rp {{ number_format($row['remaining'], 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-emerald-700 bg-emerald-50/20">
                            {{ $row['bucket_current'] > 0 ? number_format($row['bucket_current'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-amber-700 bg-amber-50/20">
                            {{ $row['bucket_1_30'] > 0 ? number_format($row['bucket_1_30'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-orange-700 bg-orange-50/20">
                            {{ $row['bucket_31_60'] > 0 ? number_format($row['bucket_31_60'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-rose-700 bg-rose-50/20">
                            {{ $row['bucket_61_90'] > 0 ? number_format($row['bucket_61_90'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs font-bold text-rose-800 bg-rose-100/20">
                            {{ $row['bucket_over_90'] > 0 ? number_format($row['bucket_over_90'], 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            Tidak ada saldo hutang pemasok yang belum terbayar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($apAging->count() > 0)
                <tfoot>
                    <tr class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900 text-xs">
                        <td colspan="3" class="py-3 px-4 uppercase text-right">Total Hutang:</td>
                        <td class="py-3 px-4 text-right font-mono text-amber-800">
                            Rp {{ number_format($apAging->sum('remaining'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-800">
                            {{ number_format($apAging->sum('bucket_current'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-amber-800">
                            {{ number_format($apAging->sum('bucket_1_30'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-orange-800">
                            {{ number_format($apAging->sum('bucket_31_60'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-rose-800">
                            {{ number_format($apAging->sum('bucket_61_90'), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-rose-900">
                            {{ number_format($apAging->sum('bucket_over_90'), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
