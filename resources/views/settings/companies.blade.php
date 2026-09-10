@extends('layouts.app')

@section('title', 'Entitas Perusahaan (Multi-Company) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Entitas Perusahaan (Multi-Company)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Kelola dan beralih konteks antar-entitas bisnis (PT, CV, Kantor Cabang) dengan isolasi data terjamin</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="$dispatch('open-company-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Entitas Baru
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ showModal: false }" @open-company-modal.window="showModal = true">
    <!-- Grid of Companies -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($companies as $comp)
        @php
            $isActive = ($comp->id == $activeCompanyId);
        @endphp
        <div class="bg-white rounded-2xl border transition-all p-6 relative flex flex-col justify-between {{ $isActive ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-md' : 'border-slate-200 shadow-xs hover:border-slate-300' }}">
            <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg {{ $isActive ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700' }}">
                        {{ substr($comp->name, 0, 1) }}
                    </div>
                    @if($isActive)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                            Sedang Aktif
                        </span>
                    @endif
                </div>

                <h3 class="font-bold text-slate-900 text-base leading-snug">{{ $comp->name }}</h3>
                <p class="text-xs text-slate-500 font-mono mt-0.5">NPWP: {{ $comp->tax_id ?? '-' }}</p>

                <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>{{ $comp->branches_count ?? 1 }} Cabang Terdaftar</span>
                    </div>
                    @if($comp->address)
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="truncate">{{ $comp->address }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                @if($isActive)
                    <span class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                        ✓ Terhubung ke Sesi Ini
                    </span>
                @else
                    <form method="POST" action="{{ route('companies.switch', $comp) }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Beralih ke Entitas Ini
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal Tambah Perusahaan -->
    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form method="POST" action="{{ route('companies.store') }}">
                    @csrf
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">Tambah Entitas Perusahaan Baru</h3>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nama Perusahaan (Brand / Operasional)</label>
                            <input type="text" name="name" required placeholder="Contoh: PT Akru Sumber Rejeki" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nama Resmi Badan Hukum (sesuai Akta/SK Kemenkumham)</label>
                            <input type="text" name="legal_name" placeholder="Contoh: PT Akru Sumber Rejeki Indonesia" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">NPWP 16 Digit / NIK</label>
                                <input type="text" name="tax_id" placeholder="0000000000000000" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nomor Telepon</label>
                                <input type="text" name="phone" placeholder="021-xxxxxxxx" class="w-full text-sm rounded-lg border-slate-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Email Resmi Perusahaan</label>
                            <input type="email" name="email" placeholder="finance@perusahaan.co.id" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Alamat Domisili Perusahaan</label>
                            <textarea name="address" rows="2" placeholder="Jalan, Gedung, Kota, Kode Pos" class="w-full text-sm rounded-lg border-slate-300"></textarea>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold shadow-sm">Daftarkan Perusahaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
