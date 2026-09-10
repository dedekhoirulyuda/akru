@extends('platform.layouts.app')

@section('title', 'Dashboard Eksekutif Platform — AKRU SaaS')

@section('content')
<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 rounded-2xl bg-gradient-to-r from-slate-950 via-indigo-950/60 to-slate-950 border border-indigo-500/20 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
        <div class="space-y-1 relative z-10">
            <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-2.5">
                <span>Ringkasan Eksekutif Platform</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-semibold font-mono">SaaS HQ</span>
            </h1>
            <p class="text-xs text-slate-400">Monitoring metrik pendapatan langganan, tagihan, rincian per paket, dan entitas perusahaan klien secara real-time.</p>
        </div>
        <div class="flex items-center gap-3 relative z-10">
            <a href="{{ route('platform.companies.index') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Perusahaan</span>
            </a>
            <a href="{{ route('platform.subscriptions.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Buat Invoice Baru</span>
            </a>
        </div>
    </div>

    {{-- Top Metric Cards: Revenue & Financials --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Pendapatan Bulanan --}}
        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 relative overflow-hidden group hover:border-emerald-500/40 transition-all shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Pendapatan Bulanan</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-black text-xs">
                    Rp
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-400 tracking-tight">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</div>
                <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                    <span class="text-emerald-400 font-bold">Lunas</span>
                    <span>periode {{ now()->translatedFormat('F Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Pendapatan Belum Terbayar (Unpaid) --}}
        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 relative overflow-hidden group hover:border-amber-500/40 transition-all shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pendapatan Belum Terbayar</span>
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-amber-400 tracking-tight">Rp {{ number_format($unpaidRevenue, 0, ',', '.') }}</div>
                <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                    <span class="text-amber-400 font-bold">{{ $unpaidInvoiceCount }} Tagihan</span>
                    <span>menunggu konfirmasi bayar</span>
                </div>
            </div>
        </div>

        {{-- Total Pendapatan Sepanjang Masa (Lifetime) --}}
        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 relative overflow-hidden group hover:border-indigo-500/40 transition-all shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pendapatan Sepanjang Masa</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-black text-xs">
                    ∑
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-white tracking-tight">Rp {{ number_format($allTimeRevenue, 0, ',', '.') }}</div>
                <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                    <span class="text-indigo-400 font-bold">Lifetime Total</span>
                    <span>akumulasi kas masuk</span>
                </div>
            </div>
        </div>

        {{-- MRR & ARR Run-Rate --}}
        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 relative overflow-hidden group hover:border-indigo-500/40 transition-all shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">MRR (Monthly Recurring)</span>
                <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-white tracking-tight">Rp {{ number_format($mrr, 0, ',', '.') }}</div>
                <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                    <span class="text-purple-400 font-bold">ARR: Rp {{ number_format($arr, 0, ',', '.') }}</span>
                    <span>&bull; Kontrak Aktif</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Metric Row: Tenant & Activity Statistics --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
            <span class="text-[11px] font-medium text-slate-400">Perusahaan Terdaftar</span>
            <div class="mt-1 text-xl font-bold text-white">{{ $totalCompanies }}</div>
            <div class="text-[10px] text-emerald-400 mt-0.5">{{ $activeCompanies }} Aktif &bull; {{ $trialCompanies }} Trial</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
            <span class="text-[11px] font-medium text-slate-400">Pengguna Terdaftar</span>
            <div class="mt-1 text-xl font-bold text-white">{{ $totalUsers }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ $superAdminCount }} Super Admin</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
            <span class="text-[11px] font-medium text-slate-400">Transaksi Bulan Ini</span>
            <div class="mt-1 text-xl font-bold text-white">{{ number_format($totalTransactionsThisMonth, 0, ',', '.') }}</div>
            <div class="text-[10px] text-indigo-400 mt-0.5">Jurnal akuntansi tenant</div>
        </div>
        <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
            <span class="text-[11px] font-medium text-slate-400">Paket Terdaftar</span>
            <div class="mt-1 text-xl font-bold text-white">{{ $totalPlansSummary['total_plans'] }} Paket</div>
            <div class="text-[10px] text-purple-400 mt-0.5">{{ $totalPlansSummary['active_subscribers'] }} Pelanggan Aktif</div>
        </div>
    </div>

    {{-- Detail per Paket Langganan & Total Seluruh Paket (Tabel Komprehensif) --}}
    <div class="rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden shadow-2xl space-y-0">
        <div class="px-6 py-4 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-950/80">
            <div>
                <h2 class="text-sm font-bold text-white tracking-wide flex items-center gap-2">
                    <span>Detail Kinerja & Pendapatan per Paket Langganan</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-mono font-bold">{{ count($planDetails) }} Paket Terdaftar</span>
                </h2>
                <p class="text-[11px] text-slate-400">Rincian kontribusi pendapatan, pelanggan aktif, dan akumulasi nilai tiap paket terhadap keseluruhan platform.</p>
            </div>
            <a href="{{ route('platform.plans.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1">
                <span>Kelola Paket & Harga</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/90 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-3.5">Paket Langganan</th>
                        <th class="px-4 py-3.5">Tarif Bulanan / Tahunan</th>
                        <th class="px-4 py-3.5 text-center">Pelanggan Aktif</th>
                        <th class="px-4 py-3.5 text-right">Pendapatan Bulanan (MRR)</th>
                        <th class="px-4 py-3.5">Porsi Kontribusi</th>
                        <th class="px-4 py-3.5 text-right">Tagihan Tertunda</th>
                        <th class="px-6 py-3.5 text-right">Total Terkumpul Sepanjang Masa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($planDetails as $item)
                    @php
                        $p = $item['plan'];
                    @endphp
                    <tr class="hover:bg-slate-900/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center font-bold text-xs text-indigo-400">
                                    {{ substr($p->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-100 flex items-center gap-2">
                                        <span>{{ $p->name }}</span>
                                        @if(!$p->is_active)
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">Arsip</span>
                                        @endif
                                        @if($p->has_ai)
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">AI Ready</span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-500">
                                        {{ $p->max_users }} User &bull; {{ $p->max_branches }} Cabang &bull; {{ number_format($p->max_transactions_per_month, 0, ',', '.') }} Tx/bln
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 text-slate-300 font-mono">
                            <div class="font-bold text-slate-200">Rp {{ number_format($item['monthly_rate'], 0, ',', '.') }}<span class="text-[10px] text-slate-500 font-normal">/bln</span></div>
                            @if($item['yearly_rate'])
                            <div class="text-[10px] text-slate-400">Rp {{ number_format($item['yearly_rate'], 0, ',', '.') }}<span class="text-[9px] text-slate-500">/thn</span></div>
                            @else
                            <div class="text-[10px] text-slate-500">-</div>
                            @endif
                        </td>

                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                {{ $item['active_count'] }} Aktif
                            </span>
                            @if($item['trial_count'] > 0)
                            <div class="text-[10px] text-amber-400 mt-0.5">+{{ $item['trial_count'] }} Trial</div>
                            @endif
                        </td>

                        <td class="px-4 py-4 text-right font-mono font-bold text-white text-xs">
                            Rp {{ number_format($item['mrr'], 0, ',', '.') }}
                        </td>

                        <td class="px-4 py-4">
                            <div class="w-full max-w-[120px] space-y-1">
                                <div class="flex items-center justify-between text-[10px] font-mono">
                                    <span class="text-slate-400">{{ $item['mrr_share'] }}%</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500" style="width: {{ min(100, $item['mrr_share']) }}%"></div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 text-right font-mono text-xs">
                            @if($item['unpaid'] > 0)
                                <span class="text-amber-400 font-bold">Rp {{ number_format($item['unpaid'], 0, ',', '.') }}</span>
                            @else
                                <span class="text-slate-500">Rp 0</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right font-mono font-bold text-emerald-400 text-xs">
                            Rp {{ number_format($item['collected'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-500">Belum ada paket langganan terkonfigurasi.</td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- Baris Total Seluruh Paket Langganan --}}
                <tfoot class="bg-slate-900 border-t-2 border-indigo-500/40 text-slate-200 font-bold text-xs">
                    <tr>
                        <td class="px-6 py-4 text-white uppercase tracking-wider text-[11px]">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                                <span>Total Seluruh Paket Langganan ({{ $totalPlansSummary['total_plans'] }} Paket)</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 font-mono text-slate-400 text-[11px]">
                            Agregat SaaS
                        </td>
                        <td class="px-4 py-4 text-center font-mono">
                            <span class="text-white text-sm">{{ $totalPlansSummary['active_subscribers'] }}</span>
                            <span class="text-[10px] text-slate-400 block">Pelanggan</span>
                        </td>
                        <td class="px-4 py-4 text-right font-mono text-white text-sm">
                            Rp {{ number_format($totalPlansSummary['total_mrr'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4 font-mono text-indigo-400">
                            100.0% Porsi
                        </td>
                        <td class="px-4 py-4 text-right font-mono text-amber-400 text-xs">
                            Rp {{ number_format($totalPlansSummary['total_unpaid'], 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-emerald-400 text-sm">
                            Rp {{ number_format($totalPlansSummary['total_collected'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Secondary Row: Tenants & Invoices Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Companies Table (2 cols) --}}
        <div class="lg:col-span-2 rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white tracking-wide">Perusahaan Baru Mendaftar</h2>
                    <p class="text-[11px] text-slate-400">Entitas tenant yang baru bergabung atau diperbarui</p>
                </div>
                <a href="{{ route('platform.companies.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/60 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-6 py-3">Nama Perusahaan</th>
                            <th class="px-4 py-3">Paket Langganan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Masa Aktif</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse($recentCompanies as $comp)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="font-bold text-slate-200">{{ $comp->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $comp->city ?? 'Indonesia' }} &bull; NPWP: {{ $comp->npwp ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ $comp->subscription?->plan?->name ?? 'Belum ada paket' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($comp->status === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        ● Aktif
                                    </span>
                                @elseif($comp->status === 'trial')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        ● Masa Trial
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        ● Ditangguhkan
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-slate-400 font-mono text-[11px]">
                                {{ $comp->subscription?->ends_at?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ route('platform.companies.show', $comp->id) }}" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white transition-all text-[11px] font-semibold border border-slate-700">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">Belum ada perusahaan terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tagihan Terbaru (1 col) --}}
        <div class="space-y-6">
            <div class="rounded-2xl bg-slate-950 border border-slate-800 p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-white">Tagihan Terakhir</h3>
                    <a href="{{ route('platform.subscriptions.index') }}" class="text-[11px] font-semibold text-indigo-400 hover:text-indigo-300">Lihat Semua</a>
                </div>

                <div class="space-y-3">
                    @forelse($recentInvoices as $inv)
                    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800/80 hover:border-slate-700 transition-all flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-200 text-xs">{{ $inv->company?->name ?? 'Perusahaan' }}</div>
                            <div class="text-[10px] font-mono text-indigo-400">{{ $inv->invoice_number }}</div>
                            <div class="text-[10px] text-slate-500">{{ $inv->due_date?->format('d M Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-white text-xs">Rp {{ number_format($inv->amount, 0, ',', '.') }}</div>
                            @if($inv->status === 'paid')
                                <span class="text-[10px] font-bold text-emerald-400">Lunas</span>
                            @else
                                <span class="text-[10px] font-bold text-amber-400">Pending</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="py-6 text-center text-slate-500 text-xs">
                        Belum ada riwayat tagihan invoice.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Quick Links / System Health --}}
            <div class="rounded-2xl bg-gradient-to-br from-slate-950 to-indigo-950/40 border border-slate-800 p-6 shadow-xl">
                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400">Status Layanan Platform</h3>
                <div class="mt-3 space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Versi Framework</span>
                        <span class="font-mono text-slate-200">Laravel {{ app()->version() }} (PHP {{ PHP_VERSION }})</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Database Driver</span>
                        <span class="font-mono text-emerald-400">SQLite (In-Process ACID)</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Mode Multi-Tenancy</span>
                        <span class="text-indigo-300 font-semibold">Row-Level Isolated Scope</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-slate-400">Penyedia AI Default</span>
                        <span class="text-amber-400 font-semibold">Google Gemini 3.8 Flash</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
