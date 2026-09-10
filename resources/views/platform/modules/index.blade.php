@extends('platform.layouts.app')

@section('title', 'Katalog Modul & Feature Flags — AKRU SaaS')

@section('content')
<div class="space-y-8" x-data="{ planConfigModalOpen: false, selectedPlan: null }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-white tracking-tight">Katalog Modul & Manajemen Fitur SaaS</h1>
            <p class="text-xs text-slate-400">Kontrol aktivasi modul sistem secara global, atur bundel modul per paket langganan, dan berikan override entitas.</p>
        </div>
    </div>

    {{-- Module Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($modules as $key => $mod)
        <div class="rounded-2xl bg-slate-950 border border-slate-800 p-6 flex flex-col justify-between group hover:border-indigo-500/40 transition-all shadow-xl">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        {{ $mod['badge'] }}
                    </span>

                    {{-- Global Toggle Form --}}
                    <form method="POST" action="{{ route('platform.modules.toggle-global', $key) }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 cursor-pointer group" title="Klik untuk mengubah status global">
                            @if($globalFlags[$key])
                                <span class="text-[10px] font-bold text-emerald-400">Aktif Global</span>
                                <div class="w-8 h-4 rounded-full bg-emerald-500 p-0.5 flex justify-end">
                                    <div class="w-3 h-3 rounded-full bg-white shadow"></div>
                                </div>
                            @else
                                <span class="text-[10px] font-bold text-slate-500">Nonaktif</span>
                                <div class="w-8 h-4 rounded-full bg-slate-800 p-0.5 flex justify-start">
                                    <div class="w-3 h-3 rounded-full bg-slate-500 shadow"></div>
                                </div>
                            @endif
                        </button>
                    </form>
                </div>

                <h3 class="text-base font-extrabold text-white group-hover:text-indigo-400 transition-colors">
                    {{ $mod['name'] }}
                </h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    {{ $mod['desc'] }}
                </p>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 font-mono">
                <span>module_id: {{ $key }}</span>
                <span class="text-slate-400">Production Ready</span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Matrix by Subscription Plan --}}
    <div class="rounded-2xl bg-slate-950 border border-slate-800 p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-white">Bundling Modul Berdasarkan Paket Langganan</h3>
                <p class="text-xs text-slate-400">Tentukan modul apa saja yang terbuka bagi pelanggan tiap paket langganan.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($plans as $plan)
            <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between">
                        <h4 class="font-extrabold text-slate-200 text-sm">{{ $plan->name }}</h4>
                        <span class="text-xs font-mono text-indigo-400 font-bold">Rp {{ number_format($plan->price_per_month, 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @php
                            $planMods = is_array($plan->modules) ? $plan->modules : [];
                            // If empty, standard defaults
                            if (empty($planMods)) {
                                $planMods = ($plan->slug === 'starter') 
                                    ? ['accounting', 'finance', 'sales', 'purchase'] 
                                    : array_keys($modules);
                            }
                        @endphp

                        @foreach($planMods as $pm)
                            @if(isset($modules[$pm]))
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                {{ $modules[$pm]['name'] }}
                            </span>
                            @endif
                        @endforeach
                    </div>
                </div>

                <button @click="selectedPlan = {{ json_encode($plan) }}; planConfigModalOpen = true" 
                        type="button" class="w-full py-2 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-300 hover:text-white text-xs font-bold transition-all cursor-pointer">
                    Konfigurasi Modul Paket
                </button>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Modal Plan Modules Config --}}
    <div x-show="planConfigModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="planConfigModalOpen = false" class="w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">
                    <span>Modul untuk Paket: </span>
                    <span class="text-indigo-400" x-text="selectedPlan?.name"></span>
                </h3>
                <button @click="planConfigModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form :action="'/platform/modules/plan/' + selectedPlan?.id" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-2">
                    <p class="text-slate-400">Centang modul yang diizinkan untuk paket ini:</p>

                    @foreach($modules as $mKey => $mVal)
                    <label class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800/80 hover:border-indigo-500/30 cursor-pointer">
                        <div>
                            <div class="font-bold text-slate-200">{{ $mVal['name'] }}</div>
                            <div class="text-[11px] text-slate-500">{{ $mVal['desc'] }}</div>
                        </div>
                        <input type="checkbox" name="modules[]" value="{{ $mKey }}" 
                               :checked="selectedPlan?.modules && selectedPlan.modules.includes('{{ $mKey }}')"
                               class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-800">
                    </label>
                    @endforeach
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="planConfigModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">
                        Simpan Modul Paket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
