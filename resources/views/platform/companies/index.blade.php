@extends('platform.layouts.app')

@section('title', 'Kelola Entitas Perusahaan Klien — AKRU SaaS')

@section('content')
<div class="space-y-6" x-data="{ createModalOpen: false, statusModalOpen: false, selectedCompany: null, selectedStatus: 'active', suspendedReason: '' }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-white tracking-tight">Daftar Entitas Perusahaan (Tenants)</h1>
            <p class="text-xs text-slate-400">Kelola seluruh entitas bisnis, pantau kuota, cabang, dan status operasional.</p>
        </div>
        <button @click="createModalOpen = true" type="button" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2 cursor-pointer self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Daftarkan Perusahaan Baru</span>
        </button>
    </div>

    {{-- Filters & Search --}}
    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('platform.companies.index') }}" class="w-full flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama perusahaan, NPWP, email, atau kota..." 
                       class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors">
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select name="status" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none focus:border-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Masa Trial</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Ditangguhkan (Suspend)</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('platform.companies.index') }}" class="px-3 py-2 rounded-xl bg-slate-900 text-slate-400 hover:text-white text-xs">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Companies Table --}}
    <div class="rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-3.5">Perusahaan & Legalitas</th>
                        <th class="px-4 py-3.5">Paket Langganan</th>
                        <th class="px-4 py-3.5">Pengguna / Cabang</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Masa Berlaku</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($companies as $company)
                    <tr class="hover:bg-slate-900/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center font-bold text-xs text-indigo-400">
                                    {{ substr($company->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-100 flex items-center gap-2">
                                        <a href="{{ route('platform.companies.show', $company->id) }}" class="hover:text-indigo-400 transition-colors">
                                            {{ $company->name }}
                                        </a>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono">{{ $company->entity_type }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">
                                        NPWP: {{ $company->npwp ?? '-' }} &bull; {{ $company->city ?? 'Indonesia' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            @if($company->subscription && $company->subscription->plan)
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                    {{ $company->subscription->plan->name }}
                                </span>
                            @else
                                <span class="text-slate-500 italic text-[11px]">Belum Berlangganan</span>
                            @endif
                        </td>

                        <td class="px-4 py-4 text-slate-300 text-[11px]">
                            <div class="flex items-center gap-3">
                                <span title="Pengguna terdaftar">👥 {{ $company->users_count }} User</span>
                                <span title="Cabang aktif">🏢 {{ $company->branches_count }} Cabang</span>
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            @if($company->status === 'active')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    ● Aktif
                                </span>
                            @elseif($company->status === 'trial')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                    ● Trial
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20" title="{{ $company->suspended_reason }}">
                                    ● Suspend
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4 text-slate-400 font-mono text-[11px]">
                            {{ $company->subscription?->ends_at?->format('d M Y') ?? '-' }}
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="selectedCompany = {{ json_encode($company) }}; selectedStatus = '{{ $company->status }}'; suspendedReason = '{{ addslashes($company->suspended_reason ?? '') }}'; statusModalOpen = true"
                                        type="button" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-[11px] font-semibold cursor-pointer">
                                    Ubah Status
                                </button>
                                <a href="{{ route('platform.companies.show', $company->id) }}" class="px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-[11px] font-semibold transition-all">
                                    Detail
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            Tidak ada data perusahaan yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
        <div class="px-6 py-4 border-t border-slate-800 bg-slate-950">
            {{ $companies->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Tambah Perusahaan Baru --}}
    <div x-show="createModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="createModalOpen = false" class="w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Daftarkan Perusahaan Klien Baru</h3>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('platform.companies.store') }}" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Nama Perusahaan / Merek Dagang *</label>
                    <input type="text" name="name" required placeholder="Contoh: PT Sinar Niaga Makmur" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Bentuk Badan Hukum *</label>
                        <select name="entity_type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                            <option value="PT">PT (Perseroan Terbatas)</option>
                            <option value="CV">CV (Persekutuan Komanditer)</option>
                            <option value="Perorangan">Usaha Perorangan (UD)</option>
                            <option value="Yayasan">Yayasan / NPO</option>
                            <option value="Koperasi">Koperasi</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Kota / Lokasi</label>
                        <input type="text" name="city" placeholder="Jakarta Selatan" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Email Perusahaan</label>
                        <input type="email" name="email" placeholder="admin@sinarniaga.co.id" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Nomor Telepon</label>
                        <input type="text" name="phone" placeholder="021-5551234" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Paket Langganan Awal *</label>
                        <select name="plan_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} (Rp {{ number_format($plan->price_per_month,0,',','.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Status Akun Awal *</label>
                        <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                            <option value="active">Aktif Langsung</option>
                            <option value="trial">Masa Percobaan (14 Hari)</option>
                            <option value="suspended">Tangguhkan (Suspend)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="createModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer shadow-lg shadow-indigo-600/30">
                        Simpan & Buat Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Ubah Status Perusahaan --}}
    <div x-show="statusModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="statusModalOpen = false" class="w-full max-w-md rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Ubah Status Perusahaan</h3>
                <button @click="statusModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form :action="'/platform/companies/' + selectedCompany?.id + '/status'" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PATCH')

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <div class="font-bold text-slate-200" x-text="selectedCompany?.name"></div>
                    <div class="text-[11px] text-slate-500" x-text="selectedCompany?.city ?? 'Indonesia'"></div>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Pilih Status Baru</label>
                    <select name="status" x-model="selectedStatus" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                        <option value="active">Aktif (Operasional Penuh)</option>
                        <option value="trial">Masa Percobaan (Trial)</option>
                        <option value="suspended">Ditangguhkan (Suspend - Kunci Akses)</option>
                    </select>
                </div>

                <div x-show="selectedStatus === 'suspended'" class="space-y-1">
                    <label class="font-semibold text-rose-400">Alasan Penangguhan (Akan tampil ke user)</label>
                    <textarea name="suspended_reason" x-model="suspendedReason" rows="3" placeholder="Contoh: Pembayaran invoice tagihan langganan telah jatuh tempo lebih dari 7 hari." 
                              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-200 focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="statusModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
