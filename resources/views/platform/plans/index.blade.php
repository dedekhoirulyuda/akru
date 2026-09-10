@extends('platform.layouts.app')

@section('title', 'Kelola Paket Langganan SaaS — AKRU')

@section('content')
<div class="space-y-6" x-data="{ planModalOpen: false, editMode: false, currentPlan: { id: null, name: '', slug: '', description: '', price_per_month: 0, price_per_year: 0, max_users: 5, max_branches: 1, max_transactions_per_month: 1000, has_ai: false, max_ai_chats_per_day: null, modules: [] } }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-white tracking-tight">Katalog Paket & Kebijakan Harga SaaS</h1>
            <p class="text-xs text-slate-400">Atur batasan kuota, harga bulanan/tahunan, dan modul yang disertakan dalam tiap paket.</p>
        </div>
        <button @click="editMode = false; currentPlan = { id: null, name: '', slug: '', description: '', price_per_month: 0, price_per_year: 0, max_users: 5, max_branches: 1, max_transactions_per_month: 1000, has_ai: false, max_ai_chats_per_day: null, modules: [] }; planModalOpen = true" 
                type="button" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2 cursor-pointer self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Paket Baru</span>
        </button>
    </div>

    {{-- Plan Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($plans as $plan)
        <div class="rounded-2xl bg-slate-950 border border-slate-800 p-6 flex flex-col justify-between relative overflow-hidden group hover:border-indigo-500/40 transition-all shadow-xl">
            @if(!$plan->is_active)
            <div class="absolute top-3 right-3 px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-800 text-slate-400 uppercase tracking-widest border border-slate-700">
                Nonaktif (Arsip)
            </div>
            @endif

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-white group-hover:text-indigo-400 transition-colors">{{ $plan->name }}</h3>
                        <div class="text-[11px] font-mono text-slate-500">slug: {{ $plan->slug }}</div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        {{ $plan->subscriptions_count }} Pelanggan
                    </span>
                </div>

                <p class="text-xs text-slate-400 min-h-[36px] line-clamp-2">{{ $plan->description ?? 'Tidak ada deskripsi paket.' }}</p>

                {{-- Price Display --}}
                <div class="p-4 rounded-xl bg-slate-900 border border-slate-800/80">
                    <div class="flex items-baseline gap-1">
                        <span class="text-xs text-slate-400 font-semibold">Rp</span>
                        <span class="text-2xl font-black text-white tracking-tight">{{ number_format($plan->price_per_month, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-500">/ bulan</span>
                    </div>
                    @if($plan->price_per_year)
                    <div class="mt-1 text-[11px] text-emerald-400 font-medium">
                        Tahunan: Rp {{ number_format($plan->price_per_year, 0, ',', '.') }}/thn
                    </div>
                    @endif
                </div>

                {{-- Feature Limits --}}
                <div class="space-y-2 text-xs text-slate-300">
                    <div class="flex items-center justify-between py-1 border-b border-slate-800/60">
                        <span class="text-slate-400">Batas Pengguna:</span>
                        <span class="font-bold text-slate-100 font-mono">{{ $plan->max_users }} User</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-800/60">
                        <span class="text-slate-400">Batas Cabang:</span>
                        <span class="font-bold text-slate-100 font-mono">{{ $plan->max_branches }} Cabang</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-800/60">
                        <span class="text-slate-400">Batas Transaksi:</span>
                        <span class="font-bold text-slate-100 font-mono">{{ number_format($plan->max_transactions_per_month, 0, ',', '.') }} /bln</span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-400">Akses AI Financial Assistant:</span>
                        @if($plan->has_ai)
                            @if($plan->max_ai_chats_per_day)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Aktif ({{ $plan->max_ai_chats_per_day }} chat/hari)</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Aktif (Unlimited) ✨</span>
                            @endif
                        @else
                            <span class="text-slate-500 text-[10px]">Tidak</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card Actions --}}
            <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between gap-2">
                <form method="POST" action="{{ route('platform.plans.toggle', $plan->id) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold border border-slate-800 transition-colors cursor-pointer">
                        {{ $plan->is_active ? 'Arsipkan' : 'Aktifkan' }}
                    </button>
                </form>

                <div class="flex items-center gap-2">
                    <button @click="editMode = true; currentPlan = {{ json_encode($plan) }}; planModalOpen = true" 
                            type="button" class="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white text-xs font-bold transition-all border border-indigo-500/30 cursor-pointer">
                        Edit Paket
                    </button>
                    @if($plan->subscriptions_count === 0)
                    <form method="POST" action="{{ route('platform.plans.destroy', $plan->id) }}" onsubmit="return confirm('Hapus paket ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg hover:bg-rose-500/20 text-slate-500 hover:text-rose-400 transition-colors cursor-pointer" title="Hapus paket">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Create / Edit Plan Modal --}}
    <div x-show="planModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="planModalOpen = false" class="w-full max-w-xl rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white" x-text="editMode ? 'Edit Paket Langganan' : 'Buat Paket Langganan Baru'"></h3>
                <button @click="planModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form :action="editMode ? '/platform/plans/' + currentPlan.id : '{{ route('platform.plans.store') }}'" method="POST" class="space-y-4 text-xs">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Nama Paket *</label>
                        <input type="text" name="name" x-model="currentPlan.name" required placeholder="Contoh: AKRU Scaleup" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Slug (URL / Identifier)</label>
                        <input type="text" name="slug" x-model="currentPlan.slug" placeholder="scaleup" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Deskripsi Singkat</label>
                    <textarea name="description" x-model="currentPlan.description" rows="2" placeholder="Uraian manfaat paket ini untuk bisnis..." 
                              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-200 focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Tarif Bulanan (IDR) *</label>
                        <input type="number" name="price_per_month" x-model="currentPlan.price_per_month" required min="0" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Tarif Tahunan (IDR)</label>
                        <input type="number" name="price_per_year" x-model="currentPlan.price_per_year" min="0" placeholder="Opsional (Diskon tahunan)" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Maks. Pengguna *</label>
                        <input type="number" name="max_users" x-model="currentPlan.max_users" required min="1" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Maks. Cabang *</label>
                        <input type="number" name="max_branches" x-model="currentPlan.max_branches" required min="1" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Maks. Tx / Bulan *</label>
                        <input type="number" name="max_transactions_per_month" x-model="currentPlan.max_transactions_per_month" required min="10" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200">
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-slate-200">Akses Asisten AI Finansial</div>
                        <div class="text-[11px] text-slate-500">Mengaktifkan asisten AI konsultasi pembukuan & audit</div>
                    </div>
                    <input type="checkbox" name="has_ai" value="1" x-model="currentPlan.has_ai" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-800">
                </div>

                <div x-show="currentPlan.has_ai" class="space-y-1">
                    <label class="font-semibold text-slate-300">Batas Chat AI / Hari (Free Trial / Terbatas)</label>
                    <input type="number" name="max_ai_chats_per_day" x-model="currentPlan.max_ai_chats_per_day" min="1" placeholder="Kosongkan jika Unlimited (Contoh: 10 untuk Trial)" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    <p class="text-[10px] text-slate-500">Masukkan angka batas chat per hari (misal 10). Jika dikosongkan, pengguna memiliki akses tanpa batas (Unlimited).</p>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="planModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer shadow-lg shadow-indigo-600/30">
                        Simpan Paket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
