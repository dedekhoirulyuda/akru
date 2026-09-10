@extends('layouts.app')

@section('title', 'Paket Langganan & Kuota SaaS — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Paket Langganan & Kuota SaaS</h1>
        <p class="text-sm text-slate-500 mt-0.5">Status lisensi entitas, pemantauan batas kuota operasional, dan eskalasi kapasitas bisnis</p>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-8">
    @php
        $currPlan = $subscription?->plan ?? $plans->firstWhere('slug', 'professional') ?? $plans->first();
        $userLimit = $currPlan?->max_users ?? 5;
        $branchLimit = $currPlan?->max_branches ?? 1;
        $trxLimit = $currPlan?->max_transactions_per_month ?? 1000;

        $userPct = min(100, round(($usage['users'] / max(1, $userLimit)) * 100));
        $branchPct = min(100, round(($usage['branches'] / max(1, $branchLimit)) * 100));
        $trxPct = min(100, round(($usage['transactions'] / max(1, $trxLimit)) * 100));
    @endphp

    <!-- Current Plan & Quota Card -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-950 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden border border-slate-700/50">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 pb-6 border-b border-slate-700/60">
            <div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        {{ strtoupper($subscription?->status ?? 'ACTIVE') }}
                    </span>
                    <span class="text-xs text-slate-400">Siklus: Tahunan / Auto-renew</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-2 text-white">
                    {{ $currPlan->name ?? 'AKRU Professional' }}
                </h2>
                <p class="text-sm text-slate-300 mt-1 max-w-2xl">
                    {{ $currPlan->description ?? 'Paket komplit akuntansi, kontrol keuangan, inventaris & pajak Coretax-ready' }}
                </p>
            </div>
            <div class="bg-white/5 backdrop-blur-xs px-5 py-3 rounded-xl border border-white/10 shrink-0">
                <div class="text-xs text-slate-400 uppercase font-semibold">Investasi Layanan</div>
                <div class="text-xl sm:text-2xl font-black text-white mt-0.5">
                    Rp {{ number_format($currPlan->price_per_month ?? 299000, 0, ',', '.') }}
                    <span class="text-xs text-slate-400 font-normal">/ bln</span>
                </div>
                <div class="text-xs text-emerald-400 mt-1">Coretax Ready & Terenkripsi</div>
            </div>
        </div>

        <!-- Quota Usage Meters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ !empty($usage['ai_limit']) ? '4' : '3' }} gap-6 pt-6">
            <!-- Quota 1: Users -->
            <div class="bg-white/5 backdrop-blur-xs p-4 rounded-xl border border-white/10">
                <div class="flex justify-between items-center text-xs mb-2">
                    <span class="text-slate-300 font-medium">Batas Anggota Tim (Seat)</span>
                    <span class="font-bold text-white font-mono">{{ $usage['users'] }} / {{ $userLimit }}</span>
                </div>
                <div class="w-full bg-slate-700/60 rounded-full h-2 overflow-hidden">
                    <div class="bg-blue-400 h-2 rounded-full transition-all duration-500" style="width: {{ $userPct }}%"></div>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">
                    {{ $userPct }}% terpakai dari kapasitas paket.
                </div>
            </div>

            <!-- Quota 2: Branches -->
            <div class="bg-white/5 backdrop-blur-xs p-4 rounded-xl border border-white/10">
                <div class="flex justify-between items-center text-xs mb-2">
                    <span class="text-slate-300 font-medium">Batas Kantor Cabang</span>
                    <span class="font-bold text-white font-mono">{{ $usage['branches'] }} / {{ $branchLimit }}</span>
                </div>
                <div class="w-full bg-slate-700/60 rounded-full h-2 overflow-hidden">
                    <div class="bg-emerald-400 h-2 rounded-full transition-all duration-500" style="width: {{ $branchPct }}%"></div>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">
                    {{ $branchPct }}% kuota cabang aktif digunakan.
                </div>
            </div>

            <!-- Quota 3: Transactions -->
            <div class="bg-white/5 backdrop-blur-xs p-4 rounded-xl border border-white/10">
                <div class="flex justify-between items-center text-xs mb-2">
                    <span class="text-slate-300 font-medium">Batas Jurnal Bulan Ini</span>
                    <span class="font-bold text-white font-mono">{{ $usage['transactions'] }} / {{ number_format($trxLimit, 0, ',', '.') }}</span>
                </div>
                <div class="w-full bg-slate-700/60 rounded-full h-2 overflow-hidden">
                    <div class="bg-purple-400 h-2 rounded-full transition-all duration-500" style="width: {{ $trxPct }}%"></div>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">
                    Reset otomatis pada tanggal 1 setiap bulan.
                </div>
            </div>

            @if(!empty($usage['ai_limit']))
            @php
                $aiPct = min(100, round(($usage['ai_chats'] / max(1, $usage['ai_limit'])) * 100));
            @endphp
            <!-- Quota 4: AI Chats (Trial) -->
            <div class="bg-white/5 backdrop-blur-xs p-4 rounded-xl border border-white/10">
                <div class="flex justify-between items-center text-xs mb-2">
                    <span class="text-slate-300 font-medium">Batas Chat AI Hari Ini</span>
                    <span class="font-bold text-white font-mono">{{ $usage['ai_chats'] }} / {{ $usage['ai_limit'] }}</span>
                </div>
                <div class="w-full bg-slate-700/60 rounded-full h-2 overflow-hidden">
                    <div class="bg-amber-400 h-2 rounded-full transition-all duration-500" style="width: {{ $aiPct }}%"></div>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">
                    {{ max(0, $usage['ai_limit'] - $usage['ai_chats']) }} sesi tersisa hari ini.
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Available Plans Comparison -->
    <div>
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900">Pilihan Paket Langganan Entitas</h2>
            <p class="text-sm text-slate-500 mt-0.5">Tingkatkan kapasitas tim, cabang, volume transaksi, dan batas asisten AI seiring pertumbuhan bisnis</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            @foreach($plans as $plan)
                @php
                    $isCurrent = ($subscription && $subscription->plan_id === $plan->id) || (!$subscription && $plan->slug === 'free-trial');
                @endphp
                <div class="bg-white rounded-2xl border {{ $isCurrent ? 'border-blue-600 ring-2 ring-blue-600/20 shadow-md' : 'border-slate-200 shadow-xs' }} p-6 flex flex-col justify-between relative">
                    @if($isCurrent)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-[11px] font-bold uppercase tracking-wider py-0.5 px-3 rounded-full shadow-xs">
                            Paket Aktif Saat Ini
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-lg font-bold text-slate-900">{{ $plan->name }}</h3>
                            @if($plan->has_ai)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 uppercase tracking-wide">AI Copilot</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-2 min-h-[36px]">{{ $plan->description }}</p>

                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <div class="flex items-baseline">
                                @if($plan->price_per_month == 0)
                                    <span class="text-2xl font-black text-emerald-600">GRATIS</span>
                                    <span class="text-xs text-slate-400 ml-1">/ Free Trial</span>
                                @else
                                    <span class="text-2xl font-black text-slate-900">Rp {{ number_format($plan->price_per_month, 0, ',', '.') }}</span>
                                    <span class="text-xs text-slate-400 ml-1">/ bulan</span>
                                @endif
                            </div>
                        </div>

                        <!-- Features list -->
                        <ul class="mt-6 space-y-3 text-xs text-slate-600">
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Hingga <strong class="text-slate-900 font-semibold">{{ $plan->max_users }} Pengguna</strong> (Hak Akses Multi-User)</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Maksimal <strong class="text-slate-900 font-semibold">{{ $plan->max_branches }} Cabang Operasional</strong></span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Kuota <strong class="text-slate-900 font-semibold">{{ number_format($plan->max_transactions_per_month, 0, ',', '.') }} Jurnal</strong> / bulan</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Kepatuhan Coretax DJP & E-Faktur XML</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 {{ $plan->has_ai ? 'text-purple-500' : 'text-slate-300' }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $plan->has_ai ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12' }}"/></svg>
                                @if($plan->has_ai)
                                    @if($plan->max_ai_chats_per_day)
                                        <span class="text-purple-900 font-semibold">Asisten AI (Batas: {{ $plan->max_ai_chats_per_day }} chat/hari)</span>
                                    @else
                                        <span class="text-purple-900 font-semibold">Asisten AI (Unlimited Chat)</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 line-through">Asisten Keuangan & Audit AI</span>
                                @endif
                            </li>
                        </ul>
                    </div>

                    <div class="mt-8 pt-4 border-t border-slate-100">
                        @if($isCurrent)
                            <button type="button" disabled class="w-full py-2.5 px-4 rounded-xl bg-slate-100 text-slate-400 font-semibold text-xs text-center cursor-not-allowed">
                                Paket Yang Digunakan
                            </button>
                        @else
                            <form method="POST" action="{{ route('subscription.change-plan') }}">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs text-center shadow-xs transition-colors cursor-pointer">
                                    Pilih Paket Ini
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
