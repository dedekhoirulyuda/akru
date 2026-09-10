@extends('layouts.app')

@section('title', 'Cabang & Lokasi Gudang — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Cabang & Lokasi Gudang</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengelolaan struktur cabang kantor operasional dan titik lokasi pergudangan logistik</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="$dispatch('open-branch-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Cabang Baru
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ showModal: false }" @open-branch-modal.window="showModal = true" class="space-y-8">
    <!-- Branches Section -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800 text-sm">Daftar Kantor Cabang Operasional ({{ $branches->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-32 whitespace-nowrap">Kode Cabang</th>
                        <th class="py-3 px-6">Nama Cabang</th>
                        <th class="py-3 px-6 w-36 whitespace-nowrap">Telepon</th>
                        <th class="py-3 px-6">Alamat Lokasi</th>
                        <th class="py-3 px-6 w-44 text-center whitespace-nowrap">Tipe Kantor</th>
                        <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($branches as $b)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-mono font-bold text-blue-600 whitespace-nowrap">{{ $b->code }}</td>
                        <td class="py-3.5 px-6 font-medium text-slate-900">{{ $b->name }}</td>
                        <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">{{ $b->phone ?? '-' }}</td>
                        <td class="py-3.5 px-6 text-xs text-slate-600">{{ $b->address ?? '-' }}</td>
                        <td class="py-3.5 px-6 text-center whitespace-nowrap">
                            @if($b->is_head_office)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200 whitespace-nowrap">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span>
                                    Kantor Pusat
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Cabang Pembantu
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-6 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                Aktif
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            Belum ada kantor cabang yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Warehouses Section -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800 text-sm">Lokasi Titik Gudang Penyimpanan ({{ $warehouses->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-32">Kode Gudang</th>
                        <th class="py-3 px-6">Nama Gudang</th>
                        <th class="py-3 px-6">Alamat Gudang</th>
                        <th class="py-3 px-6 w-24 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($warehouses as $wh)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-mono font-bold text-teal-600">{{ $wh->code }}</td>
                        <td class="py-3.5 px-6 font-medium text-slate-900">{{ $wh->name }}</td>
                        <td class="py-3.5 px-6 text-xs text-slate-600">{{ $wh->address ?? '-' }}</td>
                        <td class="py-3.5 px-6 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-400">
                            Belum ada lokasi gudang yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Cabang -->
    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form method="POST" action="{{ route('branches.store') }}">
                    @csrf
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">Tambah Cabang Kantor Baru</h3>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Kode Cabang</label>
                            <input type="text" name="code" required placeholder="Contoh: SBY, BDG" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nama Cabang</label>
                            <input type="text" name="name" required placeholder="Contoh: Kantor Cabang Surabaya" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nomor Telepon</label>
                            <input type="text" name="phone" placeholder="031-xxxxxxx" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Alamat Lengkap</label>
                            <textarea name="address" rows="2" placeholder="Jalan, Gedung, Kota" class="w-full text-sm rounded-lg border-slate-300"></textarea>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold shadow-sm">Simpan Cabang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
