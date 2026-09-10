@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Hak Akses Tim — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Pengguna & Hak Akses</h1>
        <p class="text-sm text-slate-500 mt-0.5">Kelola anggota tim entitas, peran akses (RBAC), dan penugasan cabang operasional</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="$dispatch('open-user-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500 shadow-sm transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah / Undang Anggota
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ showModal: false }" @open-user-modal.window="showModal = true" class="space-y-6">

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Total Anggota Terdaftar</div>
            <div class="text-2xl font-extrabold text-slate-900">{{ $members->count() }} <span class="text-xs font-normal text-slate-500">Pengguna</span></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Pilihan Peran Tersedia</div>
            <div class="text-2xl font-extrabold text-blue-600">{{ $roles->count() }} <span class="text-xs font-normal text-slate-500">Role Sistem</span></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Cakupan Cabang</div>
            <div class="text-2xl font-extrabold text-emerald-600">{{ $branches->count() }} <span class="text-xs font-normal text-slate-500">Kantor Cabang</span></div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800 text-sm">Daftar Anggota Tim & Otorisasi Akses</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6">Nama & Email</th>
                        <th class="py-3 px-6">Peran Otorisasi</th>
                        <th class="py-3 px-6">Cabang Ditugaskan</th>
                        <th class="py-3 px-6 text-center">Status</th>
                        <th class="py-3 px-6 text-slate-400">Bergabung</th>
                        <th class="py-3 px-6 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($members as $m)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6">
                            <div class="font-semibold text-slate-900">{{ $m->name }}</div>
                            <div class="text-xs text-slate-500 font-mono">{{ $m->email }}</div>
                        </td>
                        <td class="py-3.5 px-6">
                            @if(in_array(strtolower($m->role_name ?? ''), ['owner', 'admin', 'administrator']))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                    {{ $m->role_name ?? 'Owner' }}
                                </span>
                            @elseif(in_array(strtolower($m->role_name ?? ''), ['accountant', 'finance']))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $m->role_name ?? 'Accountant' }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $m->role_name ?? 'Staff' }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="text-sm font-medium text-slate-800">Semua Cabang (Entitas Penuh)</span>
                        </td>
                        <td class="py-3.5 px-6 text-center">
                            @if($m->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Nonaktif</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-6 text-xs text-slate-500">
                            {{ \Carbon\Carbon::parse($m->created_at)->translatedFormat('d M Y') }}
                        </td>
                        <td class="py-3.5 px-6 text-right">
                            @if($m->user_id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $m->user_id) }}" onsubmit="return confirm('Apakah Anda yakin ingin mencabut akses anggota tim ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline">
                                        Cabut Akses
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400 italic">Akun Anda</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            Belum ada anggota tim terdaftar selain pemilik entitas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Anggota -->
    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">Tambah / Undang Anggota Tim Baru</h3>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nama Lengkap</label>
                            <input type="text" name="name" required placeholder="Contoh: Budi Santoso" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Alamat Email Pengguna</label>
                            <input type="email" name="email" required placeholder="budi@perusahaan.com" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Peran Akses (RBAC)</label>
                                <select name="role_id" required class="w-full text-sm rounded-lg border-slate-300">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Penugasan Cabang</label>
                                <select name="branch_id" class="w-full text-sm rounded-lg border-slate-300">
                                    <option value="">Semua Cabang (Pusat)</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Password Sementara (Opsional)</label>
                            <input type="password" name="password" placeholder="Default: password123 jika dikosongkan" class="w-full text-sm rounded-lg border-slate-300">
                            <p class="text-xs text-slate-400 mt-1">Pengguna dapat mengganti password ini setelah login pertama kali.</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold shadow-sm">Simpan Otorisasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
