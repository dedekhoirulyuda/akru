@extends('layouts.app')

@section('title', 'Faktur ' . $invoice->invoice_number . ' — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
            <a href="{{ route('sales.index') }}" class="hover:text-blue-600">Faktur Penjualan</a>
            <span>&rsaquo;</span>
            <span class="text-slate-900 font-medium">{{ $invoice->invoice_number }}</span>
        </div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-900">{{ $invoice->invoice_number }}</h1>
            @php
                $statusMap = [
                    'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700'],
                    'submitted' => ['label' => 'Menunggu Approval', 'class' => 'bg-amber-100 text-amber-800'],
                    'posted' => ['label' => 'Terposting (Belum Lunas)', 'class' => 'bg-blue-100 text-blue-800'],
                    'partially_paid' => ['label' => 'Lunas Sebagian', 'class' => 'bg-orange-100 text-orange-800'],
                    'paid' => ['label' => 'Lunas', 'class' => 'bg-emerald-100 text-emerald-800'],
                    'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-rose-100 text-rose-800'],
                ];
                $st = $statusMap[$invoice->status] ?? ['label' => $invoice->status, 'class' => 'bg-slate-100 text-slate-700'];
            @endphp
            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full {{ $st['class'] }}">
                {{ $st['label'] }}
            </span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('sales.print', $invoice) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 shadow-sm transition-colors">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak PDF
        </a>

        @if($invoice->status === 'draft')
        <form method="POST" action="{{ route('sales.post', $invoice) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-500 shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Post ke Buku Besar
            </button>
        </form>
        @endif

        @if(in_array($invoice->status, ['posted', 'partially_paid']))
        <a href="{{ route('receipts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Catat Pembayaran
        </a>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Invoice Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pb-6 border-b border-slate-100 text-sm">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pelanggan</p>
                <p class="font-bold text-slate-900 text-base mt-1">{{ $invoice->contact->name ?? '-' }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $invoice->contact->identity_number ? 'NPWP: ' . $invoice->contact->identity_number : 'Non-NPWP' }}</p>
                <p class="text-xs text-slate-500">{{ $invoice->contact->address ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal Faktur</p>
                <p class="font-medium text-slate-800 mt-1">{{ $invoice->invoice_date->format('d F Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Jatuh Tempo</p>
                <p class="font-medium text-slate-800 mt-1">{{ $invoice->due_date->format('d F Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Pembayaran</p>
                <p class="font-bold text-slate-900 text-base mt-1">
                    Sisa: Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}
                </p>
                <p class="text-xs text-emerald-600 font-medium">Terbayar: Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Line Items Table --}}
        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Deskripsi Item</th>
                        <th class="py-3 px-4 w-24 text-center">Qty</th>
                        <th class="py-3 px-4 w-36 text-right">Harga Satuan</th>
                        <th class="py-3 px-4 w-28 text-center">Diskon</th>
                        <th class="py-3 px-4 w-36 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($invoice->lines as $line)
                    <tr>
                        <td class="py-3.5 px-4">
                            <span class="font-medium text-slate-900">{{ $line->description }}</span>
                            @if($line->item)
                                <span class="block text-xs font-mono text-slate-400">SKU: {{ $line->item->sku }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center font-mono text-xs">{{ number_format($line->quantity, 0) }}</td>
                        <td class="py-3.5 px-4 text-right font-mono text-xs">Rp {{ number_format($line->unit_price, 0, ',', '.') }}</td>
                        <td class="py-3.5 px-4 text-center font-mono text-xs">{{ $line->discount_percent > 0 ? $line->discount_percent . '%' : '-' }}</td>
                        <td class="py-3.5 px-4 text-right font-mono text-xs font-medium text-slate-900">
                            Rp {{ number_format($line->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totals Breakdown --}}
        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end">
            <div class="w-72 space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal DPP:</span>
                    <span class="font-mono font-medium text-slate-900">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>PPN ({{ $invoice->taxCode->rate ?? 0 }}%):</span>
                    <span class="font-mono font-medium text-slate-900">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-slate-200">
                    <span>Total Tagihan:</span>
                    <span class="text-blue-600 font-mono">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- General Ledger Journal Audit View --}}
    @if($journal)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-sm">Jurnal Akuntansi (General Ledger)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Entri double-entry berimbang dibentuk secara otomatis oleh Posting Engine</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-semibold px-2.5 py-1 rounded bg-blue-100 text-blue-800">
                    {{ $journal->journal_number }}
                </span>
                <span class="text-xs text-slate-400 font-mono">
                    {{ \Carbon\Carbon::parse($journal->journal_date)->format('d/m/Y') }}
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                        <th class="py-2.5 px-6 w-32">Kode Akun</th>
                        <th class="py-2.5 px-6">Nama Akun & Keterangan</th>
                        <th class="py-2.5 px-6 w-36 text-right">Debit (Rp)</th>
                        <th class="py-2.5 px-6 w-36 text-right">Kredit (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($journalLines as $jl)
                    <tr>
                        <td class="py-2.5 px-6 font-mono text-xs font-semibold text-blue-600">{{ $jl->account_code }}</td>
                        <td class="py-2.5 px-6">
                            <span class="font-medium text-slate-900">{{ $jl->account_name }}</span>
                            @if($jl->description)
                                <span class="block text-xs text-slate-400">{{ $jl->description }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-6 text-right font-mono text-xs {{ $jl->debit > 0 ? 'font-semibold text-slate-900' : 'text-slate-300' }}">
                            {{ $jl->debit > 0 ? number_format($jl->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-2.5 px-6 text-right font-mono text-xs {{ $jl->credit > 0 ? 'font-semibold text-slate-900' : 'text-slate-300' }}">
                            {{ $jl->credit > 0 ? number_format($jl->credit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @endforeach
                    <tr class="bg-slate-50/80 font-bold border-t border-slate-200">
                        <td colspan="2" class="py-3 px-6 text-right text-xs uppercase text-slate-600">Total Jurnal Berimbang:</td>
                        <td class="py-3 px-6 text-right font-mono text-xs text-slate-900">
                            Rp {{ number_format($journal->total_debit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-6 text-right font-mono text-xs text-slate-900">
                            Rp {{ number_format($journal->total_credit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
