@extends('layouts.app')

@section('title', 'Bagan Akun (Chart of Accounts) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Bagan Akun (Chart of Accounts)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Struktur akun standar akuntansi Indonesia (SAK) terisolasi untuk {{ $currentCompany->name ?? 'Perusahaan' }}</p>
    </div>
    <div class="flex items-center gap-2" x-data="{ openModal: false, openImportModal: false }">
        <button @click="openImportModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 text-sm font-semibold hover:bg-emerald-100 transition-colors shadow-xs cursor-pointer">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Impor Excel
        </button>

        <button @click="openModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Akun
        </button>

        {{-- Modal Impor Excel COA --}}
        <div x-show="openImportModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div @click="openImportModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
                <div class="relative z-10 bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 text-left border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Impor Bagan Akun (COA) dari Excel</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Unggah banyak akun sekaligus menggunakan format spreadsheet Excel resmi</p>
                        </div>
                        <button type="button" @click="openImportModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="mb-4 p-3.5 bg-blue-50/70 border border-blue-100 rounded-xl">
                        <div class="flex items-start gap-2.5">
                            <span class="text-base">💡</span>
                            <div class="text-xs text-blue-900">
                                <p class="font-semibold">Format Kolom Template Excel:</p>
                                <p class="text-blue-700 mt-0.5 font-mono text-[11px]">Kode Akun, Nama Akun, Kategori Akun (Aset / Kewajiban / Ekuitas / Pendapatan / HPP / Beban), Saldo Normal (Debit / Kredit), Sub Klasifikasi, Catatan / Deskripsi</p>
                            </div>
                        </div>
                        <div class="mt-3 text-right">
                            <a href="{{ route('coa.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-blue-300 text-blue-700 text-xs font-semibold hover:bg-blue-50 shadow-xs transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Unduh Template Excel (.xlsx)
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('coa.import') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1.5">Pilih Berkas Excel (.xlsx / .xls) *</label>
                            <input type="file" name="file" required accept=".xlsx,.xls,.csv,.txt" class="w-full text-xs rounded-lg border border-slate-300 p-2 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-[11px] text-slate-400 mt-1">Format yang didukung: Microsoft Excel (.xlsx, .xls) atau berkas CSV.</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="openImportModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-sm transition-colors">Unggah & Impor Akun</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Akun Manual --}}
        <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div @click="openModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
                <div class="relative z-10 bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-left border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Tambah Akun Baru</h3>
                    <form method="POST" action="{{ route('coa.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Akun</label>
                        <input type="text" name="code" required placeholder="Contoh: 1130" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Akun</label>
                        <input type="text" name="name" required placeholder="Contoh: Kas Kecil Operasional" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Klasifikasi</label>
                            <select name="type" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                                <option value="asset">Aset</option>
                                <option value="liability">Kewajiban / Hutang</option>
                                <option value="equity">Ekuitas / Modal</option>
                                <option value="revenue">Pendapatan</option>
                                <option value="cogs">Harga Pokok Penjualan</option>
                                <option value="expense">Beban Operasional</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Saldo Normal</label>
                            <select name="normal_balance" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                                <option value="debit">Debit</option>
                                <option value="credit">Kredit</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi / Catatan</label>
                        <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Simpan Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    @php
        $typeLabels = [
            'asset' => ['label' => '1. Aset (Aktiva)', 'color' => 'bg-emerald-50 text-emerald-800 border-emerald-200'],
            'liability' => ['label' => '2. Kewajiban (Hutang)', 'color' => 'bg-amber-50 text-amber-800 border-amber-200'],
            'equity' => ['label' => '3. Ekuitas (Modal)', 'color' => 'bg-indigo-50 text-indigo-800 border-indigo-200'],
            'revenue' => ['label' => '4. Pendapatan (Revenue)', 'color' => 'bg-blue-50 text-blue-800 border-blue-200'],
            'cogs' => ['label' => '5. Harga Pokok Penjualan (HPP)', 'color' => 'bg-orange-50 text-orange-800 border-orange-200'],
            'expense' => ['label' => '6. Beban Operasional', 'color' => 'bg-rose-50 text-rose-800 border-rose-200'],
        ];
    @endphp

    @foreach($grouped as $type => $accs)
        @if($accs->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-3 border-b border-slate-200 flex items-center justify-between {{ $typeLabels[$type]['color'] }}">
                <h3 class="font-bold text-sm tracking-wide">{{ $typeLabels[$type]['label'] }}</h3>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-white/60">{{ $accs->count() }} Akun</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <th class="py-3 px-6 w-32">Kode</th>
                            <th class="py-3 px-6">Nama Akun</th>
                            <th class="py-3 px-6 w-44">Sub-Klasifikasi</th>
                            <th class="py-3 px-6 w-28 text-center">Saldo Normal</th>
                            <th class="py-3 px-6 w-24 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($accs as $account)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-6 font-mono font-medium text-blue-600">{{ $account->code }}</td>
                            <td class="py-3 px-6 font-medium text-slate-900">{{ $account->name }}</td>
                            <td class="py-3 px-6 text-slate-500 capitalize">{{ str_replace('_', ' ', $account->sub_type ?? '-') }}</td>
                            <td class="py-3 px-6 text-center">
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded {{ $account->normal_balance === 'debit' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ strtoupper($account->normal_balance) }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach
</div>
@endsection
