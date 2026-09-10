@extends('layouts.app')

@section('title', 'Transfer Antar-Gudang — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Transfer Antar-Gudang</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pemindahan fisik barang antar lokasi gudang dengan status in-transit dan konfirmasi penerimaan</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('stock-transfers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Kirim Transfer Baru
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
                    <th class="py-3 px-6 w-36 whitespace-nowrap">No. Transfer</th>
                    <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal Kirim</th>
                    <th class="py-3 px-6">Gudang Asal</th>
                    <th class="py-3 px-6">Gudang Tujuan</th>
                    <th class="py-3 px-6 w-32 whitespace-nowrap">Pengirim</th>
                    <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Status</th>
                    <th class="py-3 px-6 w-32 text-center whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($transfers as $trf)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600 whitespace-nowrap">
                        {{ $trf->transfer_number }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">
                        {{ $trf->transfer_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $trf->fromWarehouse->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $trf->toWarehouse->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 whitespace-nowrap">
                        {{ $trf->creator->name ?? 'User' }}
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($trf->status === 'received')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Diterima
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                In-Transit
                            </span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($trf->status === 'in_transit')
                        <form method="POST" action="{{ route('stock-transfers.receive', $trf->id) }}" onsubmit="return confirm('Konfirmasi bahwa barang transfer ini telah tiba dan diterima di gudang tujuan?')">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium text-xs border border-emerald-200 transition-colors">
                                Terima Barang ✓
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-slate-400">Tuntas</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        Belum ada mutasi transfer antar-gudang yang tercatat.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transfers->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $transfers->links() }}
    </div>
    @endif
</div>
@endsection
