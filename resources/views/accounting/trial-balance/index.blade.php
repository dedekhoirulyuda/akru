@extends('layouts.app')

@section('title', 'Neraca Saldo (Trial Balance) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Neraca Saldo (Trial Balance)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Daftar ringkasan saldo debit dan kredit seluruh akun buku besar per periode penutupan</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('trial-balance.export.excel', request()->query())" 
            :pdf-url="route('trial-balance.export.pdf', request()->query())" 
            label="Export Neraca Saldo" />
    </div>
</div>
@endsection

@section('content')
<!-- Filter Box -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('trial-balance.index') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="text-sm rounded-lg border-slate-300">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
            Terapkan Filter
        </button>
    </form>
</div>

<!-- Balance Verification Status -->
<div class="p-4 rounded-xl border mb-6 flex items-center justify-between {{ $isBalanced ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900' }}">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full {{ $isBalanced ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} flex items-center justify-center font-bold">
            {{ $isBalanced ? '✓' : '!' }}
        </div>
        <div>
            <div class="font-bold text-sm">{{ $isBalanced ? 'Buku Besar Seimbang (Balanced Ledger)' : 'Peringatan: Buku Besar Tidak Seimbang!' }}</div>
            <div class="text-xs {{ $isBalanced ? 'text-emerald-700' : 'text-rose-700' }}">
                Total Saldo Akhir Debit: Rp {{ number_format($grandClosingDebit, 0, ',', '.') }} | Total Saldo Akhir Kredit: Rp {{ number_format($grandClosingCredit, 0, ',', '.') }}
            </div>
        </div>
    </div>
    <div class="text-xs font-mono font-semibold px-3 py-1 rounded-full {{ $isBalanced ? 'bg-emerald-200/60 text-emerald-800' : 'bg-rose-200 text-rose-800' }}">
        {{ $isBalanced ? '0.00 DIFFERENCE' : 'ADA SELISIH' }}
    </div>
</div>

<!-- Trial Balance Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 text-xs font-semibold uppercase tracking-wider">
                    <th rowspan="2" class="py-3 px-4 w-28 border-r border-slate-200">Kode Akun</th>
                    <th rowspan="2" class="py-3 px-4 border-r border-slate-200">Nama Akun Perkiraan</th>
                    <th colspan="2" class="py-2 px-4 text-center border-r border-slate-200 bg-slate-50">Saldo Awal</th>
                    <th colspan="2" class="py-2 px-4 text-center border-r border-slate-200 bg-slate-50">Mutasi Periode</th>
                    <th colspan="2" class="py-2 px-4 text-center bg-blue-50/50">Saldo Akhir</th>
                </tr>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs uppercase font-medium">
                    <th class="py-2 px-3 text-right w-28 border-r border-slate-200">Debit</th>
                    <th class="py-2 px-3 text-right w-28 border-r border-slate-200">Kredit</th>
                    <th class="py-2 px-3 text-right w-28 border-r border-slate-200">Debit</th>
                    <th class="py-2 px-3 text-right w-28 border-r border-slate-200">Kredit</th>
                    <th class="py-2 px-3 text-right w-32 border-r border-slate-200 text-blue-900 bg-blue-50/50">Debit</th>
                    <th class="py-2 px-3 text-right w-32 text-blue-900 bg-blue-50/50">Kredit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($rows as $row)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-2.5 px-4 font-mono font-medium text-blue-600 border-r border-slate-100">
                        <a href="{{ route('ledger.index', ['account_id' => $row['account']->id]) }}" class="hover:underline">
                            {{ $row['account']->code }}
                        </a>
                    </td>
                    <td class="py-2.5 px-4 font-medium text-slate-900 border-r border-slate-100">
                        {{ $row['account']->name }}
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono text-xs border-r border-slate-100">
                        {{ $row['opening_debit'] > 0 ? number_format($row['opening_debit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono text-xs border-r border-slate-100">
                        {{ $row['opening_credit'] > 0 ? number_format($row['opening_credit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono text-xs border-r border-slate-100">
                        {{ $row['period_debit'] > 0 ? number_format($row['period_debit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono text-xs border-r border-slate-100">
                        {{ $row['period_credit'] > 0 ? number_format($row['period_credit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono text-xs font-semibold text-slate-900 border-r border-slate-100 bg-blue-50/20">
                        {{ $row['closing_debit'] > 0 ? number_format($row['closing_debit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono text-xs font-semibold text-slate-900 bg-blue-50/20">
                        {{ $row['closing_credit'] > 0 ? number_format($row['closing_credit'], 0, ',', '.') : '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-slate-400">
                        Belum ada data transaksi yang tercatat.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-bold border-t-2 border-slate-300 text-slate-900 text-xs">
                    <td colspan="2" class="py-3 px-4 uppercase text-right">Total Keseluruhan:</td>
                    <td class="py-3 px-3 text-right font-mono border-r border-slate-200">
                        {{ number_format($grandOpeningDebit, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-3 text-right font-mono border-r border-slate-200">
                        {{ number_format($grandOpeningCredit, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-3 text-right font-mono border-r border-slate-200">
                        {{ number_format($grandPeriodDebit, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-3 text-right font-mono border-r border-slate-200">
                        {{ number_format($grandPeriodCredit, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-3 text-right font-mono border-r border-slate-200 text-blue-800 bg-blue-100/50">
                        {{ number_format($grandClosingDebit, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-3 text-right font-mono text-blue-800 bg-blue-100/50">
                        {{ number_format($grandClosingCredit, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
