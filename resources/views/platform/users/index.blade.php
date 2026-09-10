@extends('platform.layouts.app')

@section('title', 'Seluruh Pengguna Platform — AKRU SaaS')

@section('content')
<div class="space-y-6" x-data="{ resetModalOpen: false, selectedUser: null, newPassword: '' }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-white tracking-tight">Direktori Pengguna Lintas Entitas</h1>
            <p class="text-xs text-slate-400">Kelola seluruh akun pengguna terdaftar, hak akses Super Admin, dan fitur impersonasi.</p>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('platform.users.index') }}" class="w-full flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama pengguna, email, atau telepon..." 
                       class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors">
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select name="superadmin" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-indigo-500">
                    <option value="">Semua Peran</option>
                    <option value="1" {{ request('superadmin') === '1' ? 'selected' : '' }}>Hanya Super Admin</option>
                    <option value="0" {{ request('superadmin') === '0' ? 'selected' : '' }}>Pengguna Tenant Biasa</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-3.5">Nama & Profil</th>
                        <th class="px-4 py-3.5">Email & Kontak</th>
                        <th class="px-4 py-3.5">Perusahaan Terkait</th>
                        <th class="px-4 py-3.5">Peran Platform</th>
                        <th class="px-6 py-3.5 text-right">Aksi & Bantuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-900/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-xs text-white">
                                    {{ substr($u->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-100 flex items-center gap-2">
                                        <span>{{ $u->name }}</span>
                                        @if($u->is_superadmin)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/30">Super Admin</span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-slate-500">Bergabung: {{ $u->created_at?->format('d M Y') ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            <div class="font-mono text-slate-300">{{ $u->email }}</div>
                            <div class="text-[11px] text-slate-500">{{ $u->phone ?? 'Tidak ada nomor telepon' }}</div>
                        </td>

                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                @forelse($u->companies as $c)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-900 text-slate-300 border border-slate-800">
                                        {{ $c->name }}
                                    </span>
                                @empty
                                    <span class="text-slate-500 italic text-[11px]">Belum terhubung ke perusahaan</span>
                                @endforelse
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            @if($u->is_superadmin)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                    🛡️ Platform Owner
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-900 text-slate-400">
                                    User Tenant
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Impersonate Button (if not self) --}}
                                @if($u->id !== auth()->id())
                                <form method="POST" action="{{ route('platform.users.impersonate', $u->id) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-[11px] font-semibold transition-all cursor-pointer" title="Login sebagai user ini untuk troubleshooting">
                                        Login Sebagai
                                    </button>
                                </form>
                                @endif

                                {{-- Reset Password Button --}}
                                <button @click="selectedUser = {{ json_encode($u) }}; resetModalOpen = true"
                                        type="button" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-[11px] font-semibold cursor-pointer">
                                    Reset Password
                                </button>

                                {{-- Toggle Superadmin --}}
                                @if($u->id !== auth()->id())
                                <form method="POST" action="{{ route('platform.users.toggle-superadmin', $u->id) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer {{ $u->is_superadmin ? 'bg-amber-500/10 text-amber-400 hover:bg-rose-500/20 hover:text-rose-400' : 'bg-slate-900 text-slate-400 hover:text-amber-300' }}">
                                        {{ $u->is_superadmin ? 'Cabut Admin' : 'Jadikan Admin' }}
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            Tidak ada pengguna yang cocok dengan pencarian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-800 bg-slate-950">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    {{-- Reset Password Modal --}}
    <div x-show="resetModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="resetModalOpen = false" class="w-full max-w-sm rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Reset Password Pengguna</h3>
                <button @click="resetModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form :action="'/platform/users/' + selectedUser?.id + '/reset-password'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <div class="font-bold text-slate-200" x-text="selectedUser?.name"></div>
                    <div class="text-[11px] text-slate-500 font-mono" x-text="selectedUser?.email"></div>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Password Baru</label>
                    <input type="password" name="new_password" required minlength="8" placeholder="Minimal 8 karakter..." 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="resetModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">
                        Simpan Password Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
