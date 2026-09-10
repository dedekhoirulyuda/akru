@extends('layouts.app')

@section('title', 'Konsol Konsultan & Mitra (Partner Workspace) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Konsol Mitra KKP (Partner Workspace)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Portal pengelolaan multi-perusahaan klien untuk Kantor Konsultan Pajak dan Jasa Akuntan Publik</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 text-xs font-semibold">
            {{ $workspace->name }} ({{ $workspace->license_number ?? 'Resmi' }})
        </span>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Portfolio Dashboard Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Klien Aktif Dikelola</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-2">{{ count($clientOverview) }} Perusahaan</p>
            <span class="text-[11px] text-emerald-600 font-medium">Izin akses & consent valid</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Periode Belum Ditutup</span>
            <p class="text-2xl font-bold font-mono text-amber-600 mt-2">
                {{ collect($clientOverview)->sum('unclosed_periods') }} Periode
            </p>
            <span class="text-[11px] text-slate-400 font-medium">Memerlukan review bulanan</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Antrean Exception Pending</span>
            <p class="text-2xl font-bold font-mono text-rose-600 mt-2">
                {{ collect($clientOverview)->sum('pending_exceptions') }} Dokumen
            </p>
            <span class="text-[11px] text-rose-500 font-medium">Perlu verifikasi konsultan</span>
        </div>
    </div>

    <!-- Managed Client Portfolio Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50">
            <h2 class="font-semibold text-slate-800 text-sm">Portfolio Perusahaan Klien Binaan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6">Nama Perusahaan Klien</th>
                        <th class="py-3 px-6 w-36 whitespace-nowrap">Tingkat Akses</th>
                        <th class="py-3 px-6 w-36 text-center whitespace-nowrap">Status Closing Buku</th>
                        <th class="py-3 px-6 w-36 text-center whitespace-nowrap">Antrean Approval</th>
                        <th class="py-3 px-6 w-32 text-center whitespace-nowrap">Status</th>
                        <th class="py-3 px-6 w-36 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($clientOverview as $row)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-medium text-slate-900">
                            {{ $row['client']->company->name ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                Full Review & Tax
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-center whitespace-nowrap font-mono text-xs">
                            @if($row['unclosed_periods'] > 0)
                                <span class="text-amber-600 font-bold">{{ $row['unclosed_periods'] }} Periode Terbuka</span>
                            @else
                                <span class="text-emerald-600 font-bold">Closed ✓</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-6 text-center whitespace-nowrap font-mono text-xs">
                            <span class="{{ $row['pending_exceptions'] > 0 ? 'text-rose-600 font-bold' : 'text-slate-500' }}">
                                {{ $row['pending_exceptions'] }} Item
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $row['status'] === 'Compliant' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $row['status'] }}
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-center whitespace-nowrap">
                            <form method="POST" action="{{ route('companies.switch', $row['client']->company_id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 text-xs font-semibold transition-colors">
                                    Masuk Kerja →
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
