@extends('layouts.app')

@section('title', 'Dashboard Eksekutif & Kontrol Bisnis — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard Kontrol Bisnis & Pajak</h1>
        <p class="text-sm text-slate-500 mt-0.5">Ringkasan real-time arus kas, penjualan, kepatuhan perpajakan Coretax, dan posisi buku besar</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Ledger & Tax Synced
        </span>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Top KPI Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <!-- 1. Omzet Penjualan -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold uppercase text-slate-400">Omzet Bulan Ini</div>
            <div class="text-lg font-bold font-mono text-slate-900 mt-1">
                Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-blue-600 mt-1 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Penjualan terfaktur
            </div>
        </div>

        <!-- 2. Estimasi Laba Bersih -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold uppercase text-slate-400">Laba Bersih (P&L)</div>
            <div class="text-lg font-bold font-mono {{ $netProfitMonth >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1">
                Rp {{ number_format($netProfitMonth, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">Laba komersial berjalan</div>
        </div>

        <!-- 3. Saldo Kas & Bank -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold uppercase text-slate-400">Total Kas & Bank</div>
            <div class="text-lg font-bold font-mono text-blue-700 mt-1">
                Rp {{ number_format($cashAndBankBalance, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">Likuiditas seluruh rekening</div>
        </div>

        <!-- 4. Piutang Usaha (AR) -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold uppercase text-slate-400">Sisa Piutang (AR)</div>
            <div class="text-lg font-bold font-mono text-amber-600 mt-1">
                Rp {{ number_format($totalReceivables, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">Tagihan ke pelanggan</div>
        </div>

        <!-- 5. Hutang Usaha (AP) -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold uppercase text-slate-400">Sisa Hutang (AP)</div>
            <div class="text-lg font-bold font-mono text-rose-600 mt-1">
                Rp {{ number_format($totalPayables, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">Kewajiban ke pemasok</div>
        </div>

        <!-- 6. PPN Masa -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-semibold uppercase text-slate-400">Status PPN Masa</div>
            <div class="text-lg font-bold font-mono {{ $selisihPpn >= 0 ? 'text-amber-600' : 'text-purple-600' }} mt-1">
                Rp {{ number_format(abs($selisihPpn), 0, ',', '.') }}
            </div>
            <div class="text-[11px] font-semibold {{ $selisihPpn >= 0 ? 'text-amber-700' : 'text-purple-700' }} mt-1">
                {{ $selisihPpn >= 0 ? 'Kurang Bayar' : 'Lebih Bayar' }}
            </div>
        </div>
    </div>

    <!-- Quick Action Shortcuts -->
    <div class="bg-slate-900 rounded-2xl p-6 text-white shadow-md">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold">Pintasan Cepat Operasional (Quick Actions)</h3>
                <p class="text-xs text-slate-400 mt-0.5">Akses cepat pembuatan dokumen transaksi, mutasi keuangan, dan laporan</p>
            </div>
            <span class="text-xs font-mono text-slate-400">PT AKRU CONTROL</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <a href="{{ route('sales.create') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-800 hover:bg-blue-600 text-center transition-all group">
                <svg class="w-5 h-5 mb-1 text-blue-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="text-xs font-medium">Faktur Penjualan</span>
            </a>
            <a href="{{ route('receipts.create') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-800 hover:bg-emerald-600 text-center transition-all group">
                <svg class="w-5 h-5 mb-1 text-emerald-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span class="text-xs font-medium">Terima Piutang</span>
            </a>
            <a href="{{ route('purchases.create') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-800 hover:bg-amber-600 text-center transition-all group">
                <svg class="w-5 h-5 mb-1 text-amber-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span class="text-xs font-medium">Tagihan Beli</span>
            </a>
            <a href="{{ route('cash-bank.create') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-800 hover:bg-purple-600 text-center transition-all group">
                <svg class="w-5 h-5 mb-1 text-purple-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs font-medium">Pengeluaran Kas</span>
            </a>
            <a href="{{ route('manual-journals.create') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-800 hover:bg-blue-500 text-center transition-all group">
                <svg class="w-5 h-5 mb-1 text-cyan-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span class="text-xs font-medium">Jurnal Memorial</span>
            </a>
            <a href="{{ route('tax.coretax') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-800 hover:bg-emerald-500 text-center transition-all group">
                <svg class="w-5 h-5 mb-1 text-emerald-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span class="text-xs font-medium">Ekspor Coretax</span>
            </a>
        </div>
    </div>

    <!-- Recent Transactions Feed Tabs -->
    <div x-data="{ tab: 'sales' }" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <button @click="tab = 'sales'" :class="tab === 'sales' ? 'bg-white text-blue-600 shadow-xs font-semibold' : 'text-slate-500 hover:text-slate-700'" class="px-3.5 py-1.5 rounded-lg text-xs transition-all">
                    Faktur Penjualan Terkini
                </button>
                <button @click="tab = 'purchases'" :class="tab === 'purchases' ? 'bg-white text-blue-600 shadow-xs font-semibold' : 'text-slate-500 hover:text-slate-700'" class="px-3.5 py-1.5 rounded-lg text-xs transition-all">
                    Faktur Pembelian Terkini
                </button>
                <button @click="tab = 'journals'" :class="tab === 'journals' ? 'bg-white text-blue-600 shadow-xs font-semibold' : 'text-slate-500 hover:text-slate-700'" class="px-3.5 py-1.5 rounded-lg text-xs transition-all">
                    Jurnal Buku Besar Terposting
                </button>
            </div>
            <a :href="tab === 'sales' ? '{{ route('sales.index') }}' : (tab === 'purchases' ? '{{ route('purchases.index') }}' : '{{ route('journals.index') }}')" class="text-xs text-blue-600 hover:underline flex items-center gap-1 font-medium">
                Lihat Semua <span x-text="tab === 'sales' ? 'Penjualan' : (tab === 'purchases' ? 'Pembelian' : 'Jurnal')"></span> &rarr;
            </a>
        </div>

        <!-- 1. Recent Sales -->
        <div x-show="tab === 'sales'">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-36">No. Faktur</th>
                        <th class="py-3 px-6 w-28">Tanggal</th>
                        <th class="py-3 px-6">Pelanggan</th>
                        <th class="py-3 px-6 w-36 text-right">Total Tagihan</th>
                        <th class="py-3 px-6 w-36 text-right">Sisa Piutang</th>
                        <th class="py-3 px-6 w-28 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($recentSales as $inv)
                    <tr class="hover:bg-slate-50/80">
                        <td class="py-3 px-6 font-mono font-medium">
                            <a href="{{ route('sales.show', $inv) }}" class="text-blue-600 hover:underline">{{ $inv->invoice_number }}</a>
                        </td>
                        <td class="py-3 px-6 font-mono text-xs text-slate-500">{{ $inv->invoice_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-6 font-medium text-slate-900">{{ $inv->contact->name ?? '-' }}</td>
                        <td class="py-3 px-6 text-right font-mono font-medium text-slate-900">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                        <td class="py-3 px-6 text-right font-mono font-medium {{ $inv->remaining_amount > 0 ? 'text-amber-600' : 'text-emerald-600' }}">Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}</td>
                        <td class="py-3 px-6 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $inv->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada transaksi penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 2. Recent Purchases -->
        <div x-show="tab === 'purchases'" style="display: none;">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-36">No. Tagihan</th>
                        <th class="py-3 px-6 w-28">Tanggal</th>
                        <th class="py-3 px-6">Pemasok</th>
                        <th class="py-3 px-6 w-36 text-right">Total Tagihan</th>
                        <th class="py-3 px-6 w-36 text-right">Sisa Hutang</th>
                        <th class="py-3 px-6 w-28 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($recentPurchases as $pinv)
                    <tr class="hover:bg-slate-50/80">
                        <td class="py-3 px-6 font-mono font-medium">
                            <a href="{{ route('purchases.show', $pinv) }}" class="text-amber-600 hover:underline">{{ $pinv->invoice_number }}</a>
                        </td>
                        <td class="py-3 px-6 font-mono text-xs text-slate-500">{{ $pinv->invoice_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-6 font-medium text-slate-900">{{ $pinv->contact->name ?? '-' }}</td>
                        <td class="py-3 px-6 text-right font-mono font-medium text-slate-900">Rp {{ number_format($pinv->total_amount, 0, ',', '.') }}</td>
                        <td class="py-3 px-6 text-right font-mono font-medium {{ $pinv->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">Rp {{ number_format($pinv->remaining_amount, 0, ',', '.') }}</td>
                        <td class="py-3 px-6 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $pinv->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ ucfirst($pinv->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada transaksi pembelian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Recent Journals -->
        <div x-show="tab === 'journals'" style="display: none;">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-36">No. Jurnal</th>
                        <th class="py-3 px-6 w-28">Tanggal</th>
                        <th class="py-3 px-6 w-36">Sumber</th>
                        <th class="py-3 px-6">Uraian / Deskripsi</th>
                        <th class="py-3 px-6 w-36 text-right">Debit</th>
                        <th class="py-3 px-6 w-36 text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($recentJournals as $j)
                    <tr class="hover:bg-slate-50/80">
                        <td class="py-3 px-6 font-mono font-medium">
                            <a href="{{ route('journals.show', $j) }}" class="text-blue-600 hover:underline">{{ $j->journal_number }}</a>
                        </td>
                        <td class="py-3 px-6 font-mono text-xs text-slate-500">{{ $j->journal_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-6 text-xs">
                            <span class="px-2 py-0.5 rounded bg-slate-100 font-mono text-slate-600">{{ $j->source_type ?? 'manual' }}</span>
                        </td>
                        <td class="py-3 px-6 text-slate-800 truncate max-w-xs">{{ $j->description ?? '-' }}</td>
                        <td class="py-3 px-6 text-right font-mono font-medium text-slate-900">Rp {{ number_format($j->total_debit, 0, ',', '.') }}</td>
                        <td class="py-3 px-6 text-right font-mono font-medium text-slate-900">Rp {{ number_format($j->total_credit, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada jurnal terposting.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
