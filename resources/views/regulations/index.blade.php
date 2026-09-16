@extends('layouts.app')

@section('title', 'Pusat Peraturan & Standar Kepatuhan — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white shadow-sm font-bold text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 leading-tight">Pusat Peraturan & Standar Kepatuhan</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Kompilasi peraturan akuntansi (SAK/PSAK), perpajakan, kepabeanan & impor, PMK, serta Surat Edaran resmi terintegrasi</p>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-akru-ai'))" class="px-3.5 py-2 rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 text-xs font-semibold flex items-center gap-2 transition-colors cursor-pointer">
            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Konsultasi AKRU AI</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{
    activeModal: false,
    selectedReg: null,
    openDetail(reg) {
        this.selectedReg = reg;
        this.activeModal = true;
    }
}">

    <!-- Overview Stats Banner -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-medium text-slate-500">Semua Regulasi</div>
            <div class="text-xl font-bold text-slate-900 mt-0.5">{{ $counts['all'] }}</div>
            <div class="text-[10px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Tersinkronisasi Terkini
            </div>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-medium text-emerald-700">Akuntansi (SAK/PSAK)</div>
            <div class="text-xl font-bold text-emerald-700 mt-0.5">{{ $counts['akuntansi'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Standar IAI & Dewan Standar</div>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-medium text-rose-700">Pajak (UU & PP)</div>
            <div class="text-xl font-bold text-rose-700 mt-0.5">{{ $counts['pajak'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">UU HPP & Tarif PPh/PPN</div>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-medium text-blue-700">Kepabeanan & Impor</div>
            <div class="text-xl font-bold text-blue-700 mt-0.5">{{ $counts['kepabeanan'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Bea Masuk, CIF & PIB</div>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs">
            <div class="text-[11px] font-medium text-purple-700">Peraturan Menkeu (PMK)</div>
            <div class="text-xl font-bold text-purple-700 mt-0.5">{{ $counts['pmk'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Juknis TER, Natura, Coretax</div>
        </div>
    </div>

    <!-- Search Bar & Category Navigation Filter -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3.5">
        <!-- Search Input Form -->
        <form method="GET" action="{{ route('regulations.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <input type="hidden" name="category" value="{{ $category }}">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="q" value="{{ $search }}" 
                       placeholder="Cari peraturan, nomor, kata kunci (misal: impor, bea masuk, pph 22, natura, sewa psak 73, ter 21)..." 
                       class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-400">
                @if(!empty($search))
                <a href="{{ route('regulations.index', ['category' => $category]) }}" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
                @endif
            </div>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs sm:text-sm font-semibold flex items-center justify-center gap-1.5 transition-colors shadow-xs">
                <span>Cari Peraturan</span>
            </button>
        </form>

        <!-- Category Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pt-1 pb-0.5 border-t border-slate-100">
            @php
                $catTabs = [
                    ['id' => 'all', 'label' => 'Semua', 'count' => $counts['all']],
                    ['id' => 'akuntansi', 'label' => 'Akuntansi (SAK/PSAK)', 'count' => $counts['akuntansi']],
                    ['id' => 'pajak', 'label' => 'Perpajakan (UU & PP)', 'count' => $counts['pajak']],
                    ['id' => 'kepabeanan', 'label' => 'Kepabeanan & Impor', 'count' => $counts['kepabeanan']],
                    ['id' => 'pmk', 'label' => 'Peraturan Menkeu (PMK)', 'count' => $counts['pmk']],
                    ['id' => 'surat_edaran', 'label' => 'Surat Edaran (SE)', 'count' => $counts['surat_edaran']],
                ];
            @endphp
            @foreach($catTabs as $tab)
            <a href="{{ route('regulations.index', ['category' => $tab['id'], 'q' => $search]) }}" 
               class="px-3 py-1.5 rounded-lg text-xs whitespace-nowrap font-medium transition-colors flex items-center gap-1.5 {{ $category === $tab['id'] ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200' }}">
                <span>{{ $tab['label'] }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $category === $tab['id'] ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700 font-bold' }}">{{ $tab['count'] }}</span>
            </a>
            @endforeach
        </div>
    </div>

    <!-- Active Filter Info (if search active) -->
    @if(!empty($search) || ($category && $category !== 'all'))
    <div class="flex items-center justify-between text-xs text-slate-600 px-1">
        <div>
            Menampilkan hasil untuk: 
            @if(!empty($search)) <span class="font-bold text-slate-900">"{{ $search }}"</span> @endif
            @if($category && $category !== 'all') pada kategori <span class="font-bold text-indigo-700 uppercase">{{ $category }}</span> @endif
            ({{ count($regulations) }} regulasi ditemukan)
        </div>
        <a href="{{ route('regulations.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold underline">Reset Semua Filter</a>
    </div>
    @endif

    <!-- Regulations List Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse($regulations as $reg)
        <div class="bg-white rounded-xl border border-slate-200 hover:border-indigo-300 hover:shadow-md transition-all p-5 flex flex-col justify-between space-y-4">
            <!-- Header Card -->
            <div>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if($reg->category === 'akuntansi')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">AKUNTANSI (SAK)</span>
                        @elseif($reg->category === 'pajak')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">PERPAJAKAN</span>
                        @elseif($reg->category === 'kepabeanan')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">KEPABEANAN & IMPOR</span>
                        @elseif($reg->category === 'pmk')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800">PERATURAN MENKEU</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">SURAT EDARAN</span>
                        @endif

                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">{{ $reg->level }}</span>
                        <span class="text-xs font-bold text-slate-400 font-mono">{{ $reg->year }}</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ strtoupper($reg->status) }}
                    </span>
                </div>

                <!-- Number & Title -->
                <h3 class="text-base font-bold text-slate-900 mt-2.5 leading-snug">
                    {{ $reg->number }}
                </h3>
                <h4 class="text-xs font-semibold text-indigo-700 mt-0.5">
                    {{ $reg->title }}
                </h4>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    {{ $reg->about }}
                </p>
            </div>

            <!-- Highlights / Accounting & Tax Impact Box -->
            <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100 space-y-2 text-xs">
                @if(!empty($reg->accounting_implications))
                <div class="flex items-start gap-2">
                    <span class="text-emerald-600 font-bold flex-shrink-0 mt-0.5">📘 SAK:</span>
                    <span class="text-slate-700 line-clamp-2">{{ $reg->accounting_implications }}</span>
                </div>
                @endif
                @if(!empty($reg->tax_implications))
                <div class="flex items-start gap-2">
                    <span class="text-rose-600 font-bold flex-shrink-0 mt-0.5">⚖️ Pajak:</span>
                    <span class="text-slate-700 line-clamp-2">{{ $reg->tax_implications }}</span>
                </div>
                @endif
            </div>

            <!-- Footer Action Buttons -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                <button type="button" @click="openDetail({{ Js::from($reg) }})"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                    <span>Lihat Detail & Pasal Kunci</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                @if(!empty($reg->official_source_url))
                <a href="{{ $reg->official_source_url }}" target="_blank" rel="noopener noreferrer" 
                   class="inline-flex items-center gap-1 text-[11px] text-slate-400 hover:text-slate-700 font-medium transition-colors">
                    <span>JDIH / Dokumen Resmi</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full p-12 text-center bg-white rounded-xl border border-slate-200">
            <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="text-sm font-bold text-slate-700 mt-3">Tidak Ada Regulasi yang Cocok</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Coba gunakan kata kunci pencarian yang lebih umum seperti "impor", "pajak", "pph", atau klik tombol reset filter.</p>
            <a href="{{ route('regulations.index') }}" class="inline-block mt-4 px-4 py-2 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100">
                Kembali ke Semua Regulasi
            </a>
        </div>
        @endforelse
    </div>

    <!-- DETAIL MODAL DIALOG (Alpine.js) -->
    <div x-show="activeModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="activeModal = false"
             class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden border border-slate-200"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100">

            <!-- Modal Header -->
            <div class="p-5 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/40 uppercase" x-text="selectedReg?.category"></span>
                        <span class="text-xs font-mono text-slate-400" x-text="selectedReg?.level + ' • Tahun ' + selectedReg?.year"></span>
                    </div>
                    <h3 class="text-base font-bold text-white mt-1.5" x-text="selectedReg?.number"></h3>
                    <p class="text-xs text-slate-300 mt-0.5" x-text="selectedReg?.title"></p>
                </div>
                <button @click="activeModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Scrollable Content -->
            <div class="p-6 overflow-y-auto space-y-5 text-xs text-slate-700">
                <!-- Summary -->
                <div>
                    <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                        Ringkasan Substansi
                    </h4>
                    <p class="text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-lg border border-slate-100" x-text="selectedReg?.summary"></p>
                </div>

                <!-- Key Points / Clauses -->
                <template x-if="selectedReg?.key_points && selectedReg?.key_points.length > 0">
                    <div>
                        <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            Ketentuan Kunci & Tarif Penting
                        </h4>
                        <ul class="space-y-1.5">
                            <template x-for="(point, idx) in selectedReg?.key_points" :key="idx">
                                <li class="flex items-start gap-2 bg-indigo-50/50 p-2.5 rounded-lg border border-indigo-100/60">
                                    <span class="text-indigo-600 font-bold mt-0.5">•</span>
                                    <span class="text-slate-800 font-medium leading-relaxed" x-text="point"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                <!-- Accounting Implications -->
                <template x-if="selectedReg?.accounting_implications">
                    <div class="bg-emerald-50/60 p-4 rounded-xl border border-emerald-200">
                        <h4 class="font-bold text-emerald-900 text-xs flex items-center gap-1.5 mb-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Panduan Perlakuan Akuntansi & Jurnal (SAK)
                        </h4>
                        <p class="text-emerald-950 leading-relaxed font-sans" x-text="selectedReg?.accounting_implications"></p>
                    </div>
                </template>

                <!-- Tax Implications -->
                <template x-if="selectedReg?.tax_implications">
                    <div class="bg-rose-50/60 p-4 rounded-xl border border-rose-200">
                        <h4 class="font-bold text-rose-900 text-xs flex items-center gap-1.5 mb-1.5">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Kepatuhan Perpajakan & Pelaporan SPT / Coretax
                        </h4>
                        <p class="text-rose-950 leading-relaxed font-sans" x-text="selectedReg?.tax_implications"></p>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
                <template x-if="selectedReg?.official_source_url">
                    <a :href="selectedReg?.official_source_url" target="_blank" rel="noopener noreferrer" 
                       class="inline-flex items-center gap-1 text-xs text-indigo-700 hover:text-indigo-900 font-bold underline">
                        <span>Buka Teks Asli di JDIH Kemenkeu &rarr;</span>
                    </a>
                </template>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" @click="activeModal = false" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold text-xs transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
