@extends('layouts.app')

@section('title', 'Pembayaran Pemasok (Supplier Payments) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Pembayaran Pemasok (Supplier Payments)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengeluaran kas & bank untuk pelunasan tagihan pembelian pemasok</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('payments.export.excel')" 
            :pdf-url="route('payments.export.pdf')" 
            label="Export Pembayaran" />
        <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Catat Pembayaran Baru
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-36">No. Bukti Bayar</th>
                    <th class="py-3 px-6 w-28">Tanggal</th>
                    <th class="py-3 px-6">Pemasok</th>
                    <th class="py-3 px-6">Rekening Sumber</th>
                    <th class="py-3 px-6">Tagihan Terkait</th>
                    <th class="py-3 px-6 w-36 text-right">Jumlah Dibayar</th>
                    <th class="py-3 px-6 w-24 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($payments as $pay)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600">{{ $pay->payment_number }}</td>
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-600">{{ $pay->payment_date->format('d/m/Y') }}</td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">{{ $pay->contact->name ?? '-' }}</td>
                    <td class="py-3.5 px-6 text-xs text-slate-600">
                        {{ $pay->bankAccount->bank_name ?? '-' }}
                        <span class="block text-slate-400 font-mono">{{ $pay->reference_number ? 'Ref: ' . $pay->reference_number : '' }}</span>
                    </td>
                    <td class="py-3.5 px-6 text-xs">
                        @foreach($pay->allocations as $alloc)
                            <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono mr-1 mb-1">
                                {{ $alloc->invoice->invoice_number ?? '-' }}
                            </span>
                        @endforeach
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900">
                        Rp {{ number_format($pay->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                            Terposting
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-10 text-center text-slate-400">Belum ada pembayaran hutang tercatat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
