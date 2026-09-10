@extends('layouts.app')

@section('title', 'Stock Opname Fisik — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Stock Opname Fisik</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pemeriksaan fisik stok di gudang, rekonsiliasi selisih sistem vs riil, dan approval penyesuaian otomatis</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excelRoute="route('stock-opnames.export.excel')"
            :pdfRoute="route('stock-opnames.export.pdf')"
        />
        <a href="{{ route('stock-opnames.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Mulai Stock Opname Baru
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
                    <th class="py-3 px-6 w-36 whitespace-nowrap">No. Opname</th>
                    <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal</th>
                    <th class="py-3 px-6">Gudang Lokasi</th>
                    <th class="py-3 px-6 w-36 whitespace-nowrap">Dibuat Oleh</th>
                    <th class="py-3 px-6 w-32 text-center whitespace-nowrap">Status</th>
                    <th class="py-3 px-6 w-36 text-center whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($opnames as $opn)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600 whitespace-nowrap">
                        {{ $opn->opname_number }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">
                        {{ $opn->opname_date->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $opn->warehouse->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600 whitespace-nowrap">
                        {{ $opn->creator->name ?? 'User' }}
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($opn->status === 'approved')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Disetujui & Disesuaikan
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                Menunggu Approval
                            </span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                        @if($opn->status !== 'approved')
                        <form method="POST" action="{{ route('stock-opnames.approve', $opn->id) }}" onsubmit="return confirm('Setujui hasil hitung opname ini dan sesuaikan stok sistem?')">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium text-xs border border-emerald-200 transition-colors">
                                Setujui & Posting ✓
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-slate-400">Telah Diposting</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        Belum ada dokumen stock opname fisik yang tercatat.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($opnames->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $opnames->links() }}
    </div>
    @endif
</div>
@endsection
