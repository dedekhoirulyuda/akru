@extends('layouts.app')

@section('title', 'Daftar Pemasok (Suppliers) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Pemasok (Suppliers / Vendors)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Master data penyedia barang dagangan, jasa, dan vendor operasional</p>
    </div>
    <div class="flex items-center gap-2" x-data="{ openModal: false, openImportModal: false }">
        <button @click="openImportModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 text-sm font-semibold hover:bg-emerald-100 transition-colors shadow-xs cursor-pointer">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Impor Excel
        </button>

        <button @click="openModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Pemasok
        </button>

        {{-- Modal Impor Excel Pemasok --}}
        <div x-show="openImportModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div @click="openImportModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
                <div class="relative z-10 bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 text-left border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Impor Pemasok (Vendors) dari Excel</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Unggah data rekanan pemasok barang dan jasa secara kolektif via berkas Excel</p>
                        </div>
                        <button type="button" @click="openImportModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="mb-4 p-3.5 bg-blue-50/70 border border-blue-100 rounded-xl">
                        <div class="flex items-start gap-2.5">
                            <span class="text-base">💡</span>
                            <div class="text-xs text-blue-900">
                                <p class="font-semibold">Format Kolom Template Excel:</p>
                                <p class="text-blue-700 mt-0.5 font-mono text-[11px]">Kode Pemasok, Nama Pemasok / Vendor, Jenis Identitas (NPWP/NIK), Nomor NPWP atau NIK, Email, Nomor Telepon / HP, Alamat Lengkap, Kota, Termin Pembayaran (Hari)</p>
                            </div>
                        </div>
                        <div class="mt-3 text-right">
                            <a href="{{ route('suppliers.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-blue-300 text-blue-700 text-xs font-semibold hover:bg-blue-50 shadow-xs transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Unduh Template Excel (.xlsx)
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('suppliers.import') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1.5">Pilih Berkas Excel (.xlsx / .xls) *</label>
                            <input type="file" name="file" required accept=".xlsx,.xls,.csv,.txt" class="w-full text-xs rounded-lg border border-slate-300 p-2 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-[11px] text-slate-400 mt-1">Format yang didukung: Microsoft Excel (.xlsx, .xls) atau berkas CSV.</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="openImportModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-sm transition-colors">Unggah & Impor Pemasok</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Pemasok Manual --}}
        <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div @click="openModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
                <div class="relative z-10 bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 text-left border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Tambah Pemasok Baru</h3>
                    <form method="POST" action="{{ route('contacts.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" value="supplier">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Pemasok</label>
                            <input type="text" name="code" placeholder="SUPP-002" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pemasok</label>
                            <input type="text" name="name" required placeholder="PT / Toko / Vendor" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Identitas</label>
                            <select name="identity_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                                <option value="NPWP">NPWP (Badan / OP)</option>
                                <option value="NIK">NIK KTP</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor NPWP / NIK</label>
                            <input type="text" name="identity_number" placeholder="01.234.567.8-012.000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" placeholder="sales@vendor.com" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Telepon</label>
                            <input type="text" name="phone" placeholder="021-..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Kantor / Gudang Pemasok</label>
                        <textarea name="address" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kota</label>
                            <input type="text" name="city" placeholder="Jakarta Timur" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Termin Pembayaran (Hari)</label>
                            <input type="number" name="payment_terms_days" value="30" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Simpan Pemasok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-28">Kode</th>
                    <th class="py-3 px-6">Nama Pemasok</th>
                    <th class="py-3 px-6">NPWP / NIK</th>
                    <th class="py-3 px-6">Kontak & Kota</th>
                    <th class="py-3 px-6 text-center">Termin</th>
                    <th class="py-3 px-6 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($suppliers as $supp)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600">{{ $supp->code ?? '-' }}</td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">{{ $supp->name }}</td>
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-600">
                        {{ $supp->identity_number ? $supp->identity_number . ' (' . $supp->identity_type . ')' : '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-500">
                        <div>{{ $supp->phone ?? '-' }}</div>
                        <div class="text-slate-400">{{ $supp->city ?? '-' }}</div>
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-slate-100 text-slate-700">
                            {{ $supp->payment_terms_days > 0 ? 'Net ' . $supp->payment_terms_days : 'Cash / COD' }}
                        </span>
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-400">Belum ada pemasok terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
