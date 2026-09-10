@extends('layouts.app')

@section('title', 'Permintaan Pembelian (Purchase Requests) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Permintaan Pembelian (Purchase Requests)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengajuan kebutuhan barang/jasa internal divisi sebelum penerbitan pesanan pengadaan</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('purchase-requests.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajukan Permintaan Baru
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
                    <th class="py-3 px-6 w-36 whitespace-nowrap">No. Pengajuan</th>
                    <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal</th>
                    <th class="py-3 px-6">Tujuan / Keperluan</th>
                    <th class="py-3 px-6 w-36 whitespace-nowrap">Diajukan Oleh</th>
                    <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Status</th>
                    <th class="py-3 px-6 w-32 text-center whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($requests as $pr)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600 whitespace-nowrap">
                        {{ $pr->request_number }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">
                        {{ $pr->request_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 text-slate-900 font-medium">
                        {{ $pr->purpose }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 whitespace-nowrap">
                        {{ $pr->requester->name ?? 'User' }}
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($pr->status === 'approved')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Disetujui
                            </span>
                        @elseif($pr->status === 'converted')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                Diterbitkan PO
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                Menunggu Approval
                            </span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($pr->status === 'submitted')
                        <form method="POST" action="{{ route('purchase-requests.approve', $pr->id) }}" onsubmit="return confirm('Setujui pengajuan permintaan pembelian ini?')">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium text-xs border border-emerald-200 transition-colors">
                                Setujui (Approve)
                            </button>
                        </form>
                        @elseif($pr->status === 'approved')
                        <a href="{{ route('purchase-orders.create') }}" class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium text-xs border border-blue-200 transition-colors">
                            Buat PO →
                        </a>
                        @else
                        <span class="text-xs text-slate-400">Selesai</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        Belum ada permintaan pembelian internal yang diajukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $requests->links() }}
    </div>
    @endif
</div>
@endsection
