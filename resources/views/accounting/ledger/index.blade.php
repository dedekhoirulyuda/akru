@extends('layouts.app')

@section('title', 'Buku Besar (General Ledger) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Buku Besar (General Ledger)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Rincian mutasi debit/kredit dan saldo berjalan per akun perkiraan untuk audit trail</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('ledger.export.excel', request()->query())" 
            :pdf-url="route('ledger.export.pdf', request()->query())" 
            label="Export Buku Besar" />
    </div>
</div>
@endsection

@section('content')
<!-- Filter Box -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('ledger.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Pilih Akun Perkiraan (COA)</label>
            <select name="account_id" required class="w-full text-sm rounded-lg border-slate-300">
                <option value="">-- Pilih Akun untuk Menampilkan Buku Besar --</option>
                @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ $accountId == $acc->id ? 'selected' : '' }}>
                    {{ $acc->code }} — {{ $acc->name }} ({{ strtoupper($acc->type) }})
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full text-sm rounded-lg border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Sampai Tanggal</label>
            <div class="flex items-center gap-2">
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full text-sm rounded-lg border-slate-300">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold shadow-sm">
                    Tampilkan
                </button>
            </div>
        </div>
    </form>
</div>

@if($selectedAccount)
<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase text-slate-400">Saldo Awal (per {{ date('d/m/Y', strtotime($dateFrom)) }})</div>
        <div class="text-lg font-bold font-mono text-slate-800 mt-1">
            Rp {{ number_format($openingBalance, 0, ',', '.') }}
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase text-slate-400">Total Debit Periode</div>
        <div class="text-lg font-bold font-mono text-blue-600 mt-1">
            Rp {{ number_format($totalDebit, 0, ',', '.') }}
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase text-slate-400">Total Kredit Periode</div>
        <div class="text-lg font-bold font-mono text-emerald-600 mt-1">
            Rp {{ number_format($totalCredit, 0, ',', '.') }}
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase text-slate-400">Saldo Akhir (per {{ date('d/m/Y', strtotime($dateTo)) }})</div>
        <div class="text-lg font-bold font-mono text-slate-900 mt-1">
            Rp {{ number_format($closingBalance, 0, ',', '.') }}
        </div>
    </div>
</div>

<!-- Ledger Transactions Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <div>
            <h2 class="font-bold text-slate-900 text-base font-mono">{{ $selectedAccount->code }} — {{ $selectedAccount->name }}</h2>
            <p class="text-xs text-slate-500">Klasifikasi: {{ strtoupper($selectedAccount->type) }} | Saldo Normal: {{ in_array($selectedAccount->type, ['asset', 'expense']) ? 'DEBIT' : 'KREDIT' }}</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-28">Tanggal</th>
                    <th class="py-3 px-6 w-36">No. Jurnal</th>
                    <th class="py-3 px-6 w-32">Sumber</th>
                    <th class="py-3 px-6">Uraian / Keterangan</th>
                    <th class="py-3 px-6 w-36 text-right">Debit (Rp)</th>
                    <th class="py-3 px-6 w-36 text-right">Kredit (Rp)</th>
                    <th class="py-3 px-6 w-40 text-right">Saldo Berjalan (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                <!-- Opening Balance Row -->
                <tr class="bg-slate-50/40 italic font-medium text-slate-600">
                    <td class="py-3 px-6 font-mono text-xs">{{ date('d/m/Y', strtotime($dateFrom)) }}</td>
                    <td class="py-3 px-6 font-mono text-xs">-</td>
                    <td class="py-3 px-6 text-xs">-</td>
                    <td class="py-3 px-6">Saldo Awal Periode</td>
                    <td class="py-3 px-6 text-right font-mono">-</td>
                    <td class="py-3 px-6 text-right font-mono">-</td>
                    <td class="py-3 px-6 text-right font-mono font-bold text-slate-900">
                        Rp {{ number_format($openingBalance, 0, ',', '.') }}
                    </td>
                </tr>

                @forelse($ledgerEntries as $entry)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3 px-6 font-mono text-xs text-slate-600">
                        {{ date('d/m/Y', strtotime($entry->journal_date)) }}
                    </td>
                    <td class="py-3 px-6 font-mono font-medium text-blue-600">
                        <a href="{{ route('journals.show', $entry->journal_set_id) }}" class="hover:underline">
                            {{ $entry->journal_number }}
                        </a>
                    </td>
                    <td class="py-3 px-6 text-xs">
                        <span class="px-2 py-0.5 rounded font-mono bg-slate-100 text-slate-600 text-[11px]">
                            {{ $entry->source_type }}
                        </span>
                    </td>
                    <td class="py-3 px-6 text-slate-800">
                        {{ $entry->description ?? '-' }}
                    </td>
                    <td class="py-3 px-6 text-right font-mono text-slate-900 font-medium">
                        {{ $entry->debit > 0 ? number_format($entry->debit, 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-3 px-6 text-right font-mono text-slate-900 font-medium">
                        {{ $entry->credit > 0 ? number_format($entry->credit, 0, ',', '.') : '-' }}
                    </td>
                    <td class="py-3 px-6 text-right font-mono font-bold text-slate-900">
                        Rp {{ number_format($entry->running_balance, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">
                        Tidak ada transaksi pada periode yang dipilih.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-slate-50 font-bold border-t-2 border-slate-300 text-slate-900">
                    <td colspan="4" class="py-3 px-6 text-right uppercase text-xs">Total Mutasi & Saldo Akhir:</td>
                    <td class="py-3 px-6 text-right font-mono text-blue-700">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                    <td class="py-3 px-6 text-right font-mono text-emerald-700">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
                    <td class="py-3 px-6 text-right font-mono text-base text-slate-900">Rp {{ number_format($closingBalance, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@else
<div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
    <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <h3 class="text-base font-bold text-slate-900">Silakan Pilih Akun Perkiraan</h3>
    <p class="text-sm text-slate-500 max-w-md mx-auto mt-1">Pilih akun dari daftar dropdown di atas untuk melihat buku besar dan rekam jejak mutasi audit akun tersebut.</p>
</div>
@endif
@endsection
