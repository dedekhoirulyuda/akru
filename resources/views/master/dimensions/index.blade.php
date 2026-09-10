@extends('layouts.app')

@section('title', 'Dimensi Analitik (Cost Centers & Projects) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" x-data>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Dimensi Analitik (Cost Centers & Projects)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengelompokan analitik multi-level untuk pelaporan laba rugi per divisi, departemen, dan proyek</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" @click="$dispatch('open-dimension-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Dimensi Baru
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{ dimModal: false, valModal: false, selectedDimId: null, selectedDimName: '' }" 
     @open-dimension-modal.window="dimModal = true"
     @open-val-modal.window="valModal = true; selectedDimId = $event.detail.id; selectedDimName = $event.detail.name">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($dimensions as $dim)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                            {{ $dim->code }}
                        </span>
                        <h3 class="text-base font-bold text-slate-900 mt-1">{{ $dim->name }}</h3>
                    </div>
                    <button type="button" @click="$dispatch('open-val-modal', { id: {{ $dim->id }}, name: '{{ $dim->name }}' })" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition-colors">
                        + Tambah Nilai
                    </button>
                </div>

                <div class="space-y-2">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Daftar Nilai / Pos Terdaftar ({{ $dim->values->count() }}):</p>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @forelse($dim->values as $val)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-50 text-slate-800 border border-slate-200">
                            <span class="font-mono text-slate-500 text-[10px]">{{ $val->code }}</span>
                            {{ $val->name }}
                        </span>
                        @empty
                        <span class="text-xs text-slate-400 italic">Belum ada nilai dimensi yang didaftarkan.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center text-slate-400">
            Belum ada dimensi analitik yang dibuat. Klik tombol "+ Tambah Dimensi Baru" di atas.
        </div>
        @endforelse
    </div>

    <!-- Modal Tambah Dimensi -->
    <div x-show="dimModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div @click="dimModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <div class="relative z-10 inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Tambah Kategori Dimensi Baru</h3>
                <form method="POST" action="{{ route('dimensions.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Kode Dimensi</label>
                        <input type="text" name="code" required placeholder="Contoh: DEPT, PROJ, CC" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Dimensi</label>
                        <input type="text" name="name" required placeholder="Contoh: Departemen Operasional" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" @click="dimModal = false" class="px-4 py-2 border rounded-lg text-sm text-slate-600">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Simpan Dimensi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Nilai Dimensi -->
    <div x-show="valModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div @click="valModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <div class="relative z-10 inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Tambah Nilai Dimensi</h3>
                <p class="text-xs text-slate-500 mb-4" x-text="'Untuk Kategori: ' + selectedDimName"></p>
                <form :action="'/dimensions/' + selectedDimId + '/values'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Kode Nilai</label>
                        <input type="text" name="code" required placeholder="Contoh: HR, MKT, PROJ-01" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Nilai</label>
                        <input type="text" name="name" required placeholder="Contoh: Human Resources" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" @click="valModal = false" class="px-4 py-2 border rounded-lg text-sm text-slate-600">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Tambah Nilai</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
