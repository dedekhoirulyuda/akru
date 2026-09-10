@extends('layouts.app')

@section('title', 'Jurnal Umum (Journal Explorer) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Jurnal Umum (Journal Explorer)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Penjelajah seluruh mutasi jurnal sistem (penjualan, pembelian, kas/bank, memorial) yang telah terposting</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('journals.export.excel', request()->query())" 
            :pdf-url="route('journals.export.pdf', request()->query())" 
            label="Export Jurnal" />
        <a href="{{ route('manual-journals.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Jurnal Penyesuaian (Manual)
        </a>
    </div>
</div>
@endsection

@section('content')
<!-- Filter Box -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('journals.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full text-sm rounded-lg border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full text-sm rounded-lg border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sumber Modul</label>
            <select name="source_type" class="w-full text-sm rounded-lg border-slate-300">
                <option value="">-- Semua Sumber --</option>
                <option value="sales_invoice" {{ $sourceType === 'sales_invoice' ? 'selected' : '' }}>Faktur Penjualan</option>
                <option value="customer_receipt" {{ $sourceType === 'customer_receipt' ? 'selected' : '' }}>Penerimaan Piutang</option>
                <option value="purchase_invoice" {{ $sourceType === 'purchase_invoice' ? 'selected' : '' }}>Faktur Pembelian</option>
                <option value="supplier_payment" {{ $sourceType === 'supplier_payment' ? 'selected' : '' }}>Pembayaran Hutang</option>
                <option value="cash_transaction" {{ $sourceType === 'cash_transaction' ? 'selected' : '' }}>Kas & Bank</option>
                <option value="bank_transfer" {{ $sourceType === 'bank_transfer' ? 'selected' : '' }}>Transfer Antar Kas</option>
                <option value="manual" {{ $sourceType === 'manual' ? 'selected' : '' }}>Jurnal Penyesuaian</option>
                <option value="journal_reversal" {{ $sourceType === 'journal_reversal' ? 'selected' : '' }}>Jurnal Pembalik</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Status Posting</label>
            <select name="status" class="w-full text-sm rounded-lg border-slate-300">
                <option value="">-- Semua Status --</option>
                <option value="posted" {{ $status === 'posted' ? 'selected' : '' }}>Posted (Aktif)</option>
                <option value="reversed" {{ $status === 'reversed' ? 'selected' : '' }}>Reversed (Dibalik)</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">Filter</button>
            <a href="{{ route('journals.index') }}" class="px-3 py-2 border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm">Reset</a>
        </div>
    </form>
</div>

<!-- Balance Verification Banner -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-blue-50/50 border border-blue-200/60 p-4 rounded-xl flex items-center justify-between">
        <div>
            <div class="text-xs font-semibold text-blue-700 uppercase">Total Debit Terposting</div>
            <div class="text-xl font-bold font-mono text-blue-900 mt-0.5">Rp {{ number_format($totalDebit, 0, ',', '.') }}</div>
        </div>
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold font-mono">D</div>
    </div>
    <div class="bg-emerald-50/50 border border-emerald-200/60 p-4 rounded-xl flex items-center justify-between">
        <div>
            <div class="text-xs font-semibold text-emerald-700 uppercase">Total Kredit Terposting</div>
            <div class="text-xl font-bold font-mono text-emerald-900 mt-0.5">Rp {{ number_format($totalCredit, 0, ',', '.') }}</div>
        </div>
        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold font-mono">K</div>
    </div>
</div>

<!-- Journals List -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-36">No. Jurnal</th>
                    <th class="py-3 px-6 w-28">Tanggal</th>
                    <th class="py-3 px-6 w-40">Sumber Modul</th>
                    <th class="py-3 px-6">Uraian / Deskripsi</th>
                    <th class="py-3 px-6 w-36 text-right">Debit</th>
                    <th class="py-3 px-6 w-36 text-right">Kredit</th>
                    <th class="py-3 px-6 w-28 text-center">Status</th>
                    <th class="py-3 px-6 w-20 text-center">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($journals as $j)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium">
                        <a href="{{ route('journals.show', $j) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $j->journal_number }}
                        </a>
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono">
                        {{ $j->journal_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 text-xs">
                        <span class="px-2 py-0.5 rounded-full font-mono bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $j->source_type ?? 'manual' }}
                        </span>
                    </td>
                    <td class="py-3.5 px-6 text-slate-900 max-w-xs truncate">
                        {{ $j->description ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-medium text-slate-900">
                        Rp {{ number_format($j->total_debit, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-medium text-slate-900">
                        Rp {{ number_format($j->total_credit, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        @if($j->status === 'posted')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Posted</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Reversed</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        <a href="{{ route('journals.show', $j) }}" class="text-slate-500 hover:text-blue-600">
                            <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-slate-400">
                        Tidak ada entri jurnal ditemukan pada periode yang dipilih.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($journals->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $journals->links() }}
    </div>
    @endif
</div>
@endsection
