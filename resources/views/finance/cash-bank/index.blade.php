@extends('layouts.app')

@section('title', 'Kas & Bank — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kas & Bank</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pencatatan kas masuk (BKM), kas keluar (BKK), dan monitoring mutasi likuiditas</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excelRoute="route('cash-bank.export.excel')"
            :pdfRoute="route('cash-bank.export.pdf')"
        />
        <a href="{{ route('transfers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 shadow-sm transition-colors">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Transfer Antar Bank
        </a>
        <a href="{{ route('cash-bank.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Catat Mutasi Kas
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Bank Account Badges --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($bankAccounts as $bank)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-base">
                    {{ substr($bank->bank_name, 0, 1) }}
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm">{{ $bank->bank_name }}</h4>
                    <p class="text-xs font-mono text-slate-400">{{ $bank->account_number ?? 'Kas Fisik' }}</p>
                </div>
            </div>
            <a href="{{ route('reconciliation.index', ['bank_account_id' => $bank->id]) }}" class="text-xs text-blue-600 hover:underline font-medium">
                Rekonsiliasi &rarr;
            </a>
        </div>
        @endforeach
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-36">No. Transaksi</th>
                        <th class="py-3 px-6 w-28">Tanggal</th>
                        <th class="py-3 px-6 w-28 text-center">Tipe</th>
                        <th class="py-3 px-6">Rekening Kas/Bank</th>
                        <th class="py-3 px-6">Pihak Terkait / Keterangan</th>
                        <th class="py-3 px-6">Akun Lawan</th>
                        <th class="py-3 px-6 w-36 text-right">Jumlah (Rp)</th>
                        <th class="py-3 px-6 w-24 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($transactions as $trx)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-mono font-medium text-blue-600">{{ $trx->transaction_number }}</td>
                        <td class="py-3.5 px-6 font-mono text-xs text-slate-600">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('d/m/Y') }}</td>
                        <td class="py-3.5 px-6 text-center">
                            @if($trx->type === 'cash_in')
                                <span class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                                    Kas Masuk
                                </span>
                            @else
                                <span class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">
                                    Kas Keluar
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-6 font-medium text-slate-900">{{ $trx->bank_name }}</td>
                        <td class="py-3.5 px-6 text-xs text-slate-600">
                            <strong>{{ $trx->counterparty ?? '-' }}</strong>
                            @if($trx->notes)
                                <span class="block text-slate-400">{{ $trx->notes }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-6 text-xs font-mono text-slate-600">
                            {{ $trx->account_code }} - {{ $trx->account_name }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-bold text-sm {{ $trx->type === 'cash_in' ? 'text-emerald-600' : 'text-slate-900' }}">
                            {{ $trx->type === 'cash_in' ? '+' : '-' }} Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-center">
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                                Terposting
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-400">Belum ada mutasi kas atau bank tercatat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
