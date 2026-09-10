@extends('platform.layouts.app')

@section('title', "Detail Perusahaan: {$company->name} — AKRU SaaS")

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb & Top bar --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs">
            <a href="{{ route('platform.companies.index') }}" class="text-slate-400 hover:text-indigo-400 flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Daftar Perusahaan</span>
            </a>
            <span class="text-slate-600">/</span>
            <span class="text-slate-200 font-bold">{{ $company->name }}</span>
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('platform.companies.destroy', $company->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin mengarsipkan perusahaan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 text-xs font-semibold transition-all cursor-pointer">
                    Arsipkan Perusahaan
                </button>
            </form>
        </div>
    </div>

    {{-- Company Profile Header --}}
    <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center font-extrabold text-white text-2xl shadow-xl shadow-indigo-600/20">
                {{ substr($company->name, 0, 2) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-extrabold text-white">{{ $company->name }}</h1>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 font-mono">{{ $company->entity_type }}</span>
                    @if($company->status === 'active')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Aktif</span>
                    @elseif($company->status === 'trial')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Trial</span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">Suspended</span>
                    @endif
                </div>
                <div class="mt-1 text-xs text-slate-400 flex flex-wrap items-center gap-3">
                    <span>NPWP: <strong class="text-slate-300 font-mono">{{ $company->npwp ?? '-' }}</strong></span>
                    <span>NIB: <strong class="text-slate-300 font-mono">{{ $company->nib ?? '-' }}</strong></span>
                    <span>Kota: <strong class="text-slate-300">{{ $company->city ?? '-' }}</strong></span>
                    <span>PKP: <strong class="text-slate-300">{{ $company->is_pkp ? 'Ya (PKP)' : 'Non-PKP' }}</strong></span>
                </div>
            </div>
        </div>

        {{-- Subscription Badge & Stats --}}
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 text-xs flex items-center gap-6">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Paket Langganan</div>
                <div class="font-bold text-indigo-400 text-sm">{{ $company->subscription?->plan?->name ?? 'Belum ada paket' }}</div>
                <div class="text-[10px] text-slate-400">Berlaku s/d: {{ $company->subscription?->ends_at?->format('d M Y') ?? '-' }}</div>
            </div>
            <div class="h-8 w-px bg-slate-800"></div>
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Transaksi Bulan Ini</div>
                <div class="font-bold text-white text-sm">{{ $monthlyTxCount }} Set</div>
                <div class="text-[10px] text-slate-400">Total: {{ $totalTxCount }} Set</div>
            </div>
        </div>
    </div>

    {{-- Grid: Left Configuration, Right Branches & Users --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Status & Quota Controls --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white">Status & Operasional Tenant</h3>
                <form method="POST" action="{{ route('platform.companies.status', $company->id) }}" class="space-y-3 text-xs">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="font-semibold text-slate-300 block mb-1">Status Operasional</label>
                        <select name="status" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-slate-200">
                            <option value="active" {{ $company->status === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="trial" {{ $company->status === 'trial' ? 'selected' : '' }}>Masa Percobaan (Trial)</option>
                            <option value="suspended" {{ $company->status === 'suspended' ? 'selected' : '' }}>Ditangguhkan (Suspend)</option>
                        </select>
                    </div>

                    <div>
                        <label class="font-semibold text-slate-300 block mb-1">Catatan Penangguhan (Bila Suspend)</label>
                        <textarea name="suspended_reason" rows="2" placeholder="Alasan akun ditangguhkan..." 
                                  class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-200">{{ $company->suspended_reason }}</textarea>
                    </div>

                    <button type="submit" class="w-full py-2 rounded-xl bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white font-bold transition-all">
                        Perbarui Status
                    </button>
                </form>
            </div>

            {{-- Custom Quota Overrides --}}
            <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-white">Override Kuota Khusus</h3>
                    <p class="text-[11px] text-slate-400">Kosongkan untuk mengikuti batas kuota bawaan paket.</p>
                </div>

                <form method="POST" action="{{ route('platform.companies.quota', $company->id) }}" class="space-y-3 text-xs">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="font-semibold text-slate-300 block mb-1">Maksimal Pengguna (User)</label>
                        <input type="number" name="max_users_override" value="{{ $company->max_users_override }}" placeholder="Bawaan paket: {{ $company->subscription?->plan?->max_users ?? '-' }}" 
                               class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-slate-200">
                    </div>

                    <div>
                        <label class="font-semibold text-slate-300 block mb-1">Maksimal Cabang (Branch)</label>
                        <input type="number" name="max_branches_override" value="{{ $company->max_branches_override }}" placeholder="Bawaan paket: {{ $company->subscription?->plan?->max_branches ?? '-' }}" 
                               class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-slate-200">
                    </div>

                    <div>
                        <label class="font-semibold text-slate-300 block mb-1">Maksimal Transaksi / Bulan</label>
                        <input type="number" name="max_transactions_override" value="{{ $company->max_transactions_override }}" placeholder="Bawaan paket: {{ $company->subscription?->plan?->max_transactions_per_month ?? '-' }}" 
                               class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-slate-200">
                    </div>

                    <button type="submit" class="w-full py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition-all shadow-lg shadow-indigo-600/30">
                        Simpan Kuota Khusus
                    </button>
                </form>
            </div>
        </div>

        {{-- Right: Branches & Users --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Branches Card --}}
            <div class="rounded-2xl bg-slate-950 border border-slate-800 p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>Daftar Cabang Entitas</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">{{ $company->branches->count() }} Cabang</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900 text-slate-400 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-4 py-2">Nama Cabang</th>
                                <th class="px-3 py-2">Kode</th>
                                <th class="px-4 py-2">Tipe</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 font-medium">
                            @foreach($company->branches as $branch)
                            <tr>
                                <td class="px-4 py-2.5 text-slate-200 font-bold">{{ $branch->name }}</td>
                                <td class="px-3 py-2.5 font-mono text-slate-400">{{ $branch->code }}</td>
                                <td class="px-4 py-2.5">
                                    @if($branch->is_head_office)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">Kantor Pusat</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-400">Cabang Operasional</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="text-emerald-400 font-bold">Aktif</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Users Card --}}
            <div class="rounded-2xl bg-slate-950 border border-slate-800 p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>Pengguna yang Terhubung</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">{{ $company->users->count() }} Pengguna</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900 text-slate-400 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-4 py-2">Nama & Email</th>
                                <th class="px-4 py-2">Status Akun</th>
                                <th class="px-4 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 font-medium">
                            @foreach($company->users as $user)
                            <tr>
                                <td class="px-4 py-2.5">
                                    <div class="font-bold text-slate-200">{{ $user->name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $user->email }}</div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="text-emerald-400 font-bold">Aktif</span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <form method="POST" action="{{ route('platform.users.impersonate', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white text-[11px] font-semibold transition-all cursor-pointer">
                                            Login Sebagai
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
