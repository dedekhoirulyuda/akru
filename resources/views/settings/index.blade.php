@extends('layouts.app')

@section('title', 'Pengaturan Perusahaan & Sistem — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Pengaturan Perusahaan & Sistem</h1>
        <p class="text-sm text-slate-500 mt-0.5">Konfigurasi profil badan usaha, nomor pokok wajib pajak, dan preferensi operasional akuntansi</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl">
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Identitas Badan Usaha -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 mb-4">
                Identitas Badan Usaha (Entitas Aktif)
            </h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nama Perusahaan (Brand)</label>
                        <input type="text" name="name" value="{{ old('name', $company->name) }}" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nama Resmi Badan Hukum</label>
                        <input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">NPWP 16-Digit / NIK</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id', $company->tax_id) }}" placeholder="0000000000000000" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nomor Telepon Kantor</label>
                        <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" placeholder="021-xxxxxxx" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Email Resmi</label>
                        <input type="email" name="email" value="{{ old('email', $company->email) }}" placeholder="contact@perusahaan.com" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Alamat Domisili Usaha</label>
                    <textarea name="address" rows="2" class="w-full text-sm rounded-lg border-slate-300">{{ old('address', $company->address) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Preferensi Akuntansi & Perpajakan -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 mb-4">
                Konfigurasi Fiskal & Penomoran Dokumen
            </h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Mata Uang Pelaporan</label>
                        <select name="settings[currency]" class="w-full text-sm rounded-lg border-slate-300">
                            <option value="IDR" {{ ($settings['currency'] ?? 'IDR') === 'IDR' ? 'selected' : '' }}>Rupiah Indonesia (IDR)</option>
                            <option value="USD" {{ ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' }}>US Dollar (USD)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tarif Default PPN</label>
                        <select name="settings[default_ppn_rate]" class="w-full text-sm rounded-lg border-slate-300">
                            <option value="11" {{ ($settings['default_ppn_rate'] ?? '11') == '11' ? 'selected' : '' }}>11% (Tarif Saat Ini)</option>
                            <option value="12" {{ ($settings['default_ppn_rate'] ?? '') == '12' ? 'selected' : '' }}>12% (Kesiapan UU HPP 2025/2026)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Metode Valuasi Persediaan</label>
                        <select name="settings[inventory_method]" class="w-full text-sm rounded-lg border-slate-300">
                            <option value="average" {{ ($settings['inventory_method'] ?? 'average') === 'average' ? 'selected' : '' }}>Moving Average (Rata-rata)</option>
                            <option value="fifo" {{ ($settings['inventory_method'] ?? '') === 'fifo' ? 'selected' : '' }}>FIFO (First In First Out)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Prefix Faktur Penjualan</label>
                        <input type="text" name="settings[sales_prefix]" value="{{ $settings['sales_prefix'] ?? 'INV' }}" placeholder="INV" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Prefix Faktur Pembelian</label>
                        <input type="text" name="settings[purchase_prefix]" value="{{ $settings['purchase_prefix'] ?? 'BILL' }}" placeholder="BILL" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Banner -->
        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/60 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-xs text-emerald-800">
                    <span class="font-bold">Konektivitas Coretax Aktif:</span> Profil perusahaan ini memenuhi syarat kepatuhan DJP PMK 112/2022.
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end gap-3">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                Simpan Perubahan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
