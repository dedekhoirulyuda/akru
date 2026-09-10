@extends('layouts.app')

@section('title', 'Produk & Jasa — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Produk & Jasa</h1>
        <p class="text-sm text-slate-500 mt-0.5">Katalog barang dagang, jasa, pemetaan akun akuntansi & level persediaan</p>
    </div>
    <div class="flex items-center gap-2" x-data="{ openModal: false, openImportModal: false }">
        <button @click="openImportModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 text-sm font-semibold hover:bg-emerald-100 transition-colors shadow-xs cursor-pointer">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Impor Excel
        </button>

        <button @click="openModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Produk / Jasa
        </button>

        {{-- Modal Impor Excel Produk & Jasa --}}
        <div x-show="openImportModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div @click="openImportModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
                <div class="relative z-10 bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 text-left border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Impor Produk & Jasa dari Excel</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Unggah massal katalog barang dagang, harga beli/jual & pemetaan akun via Excel</p>
                        </div>
                        <button type="button" @click="openImportModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="mb-4 p-3.5 bg-blue-50/70 border border-blue-100 rounded-xl">
                        <div class="flex items-start gap-2.5">
                            <span class="text-base">💡</span>
                            <div class="text-xs text-blue-900">
                                <p class="font-semibold">Format Kolom Template Excel:</p>
                                <p class="text-blue-700 mt-0.5 font-mono text-[11px]">Kode / SKU, Nama Produk / Jasa, Jenis Item (Barang / Jasa), Harga Beli (Rp), Harga Jual (Rp), Kelola Stok (Ya / Tidak), Kode Akun Penjualan, Kode Akun HPP, Deskripsi / Catatan</p>
                            </div>
                        </div>
                        <div class="mt-3 text-right">
                            <a href="{{ route('products.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-blue-300 text-blue-700 text-xs font-semibold hover:bg-blue-50 shadow-xs transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Unduh Template Excel (.xlsx)
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1.5">Pilih Berkas Excel (.xlsx / .xls) *</label>
                            <input type="file" name="file" required accept=".xlsx,.xls,.csv,.txt" class="w-full text-xs rounded-lg border border-slate-300 p-2 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-[11px] text-slate-400 mt-1">Format yang didukung: Microsoft Excel (.xlsx, .xls) atau berkas CSV.</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="openImportModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500 shadow-sm">Mulai Impor Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Tambah Produk --}}
        <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div @click="openModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
                <div class="relative z-10 bg-white rounded-xl shadow-xl max-w-lg w-full p-6 text-left border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Tambah Produk / Jasa Baru</h3>
                <form method="POST" action="{{ route('products.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">SKU / Kode Item</label>
                            <input type="text" name="sku" required placeholder="PRD-001" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Item</label>
                            <select name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                                <option value="goods">Barang Dagang (Fisik / Stok)</option>
                                <option value="service">Jasa / Konsultasi</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk / Jasa</label>
                        <input type="text" name="name" required placeholder="Contoh: Laptop Bisnis Pro 14 inch" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Harga Beli / Pokok (Rp)</label>
                            <input type="number" name="buy_price" required min="0" value="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Harga Jual (Rp)</label>
                            <input type="number" name="sell_price" required min="0" value="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Akun Pendapatan</label>
                            <select name="sales_account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                                <option value="">-- Pilih Akun Penjualan --</option>
                                @foreach($accounts->where('type', 'revenue') as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Akun HPP</label>
                            <select name="cogs_account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                                <option value="">-- Pilih Akun HPP --</option>
                                @foreach($accounts->where('type', 'cogs') as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Simpan Produk</button>
                    </div>
                </form>
                </div>
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
                    <th class="py-3 px-6 w-32">SKU</th>
                    <th class="py-3 px-6">Nama Item</th>
                    <th class="py-3 px-6 w-28 text-center">Tipe</th>
                    <th class="py-3 px-6 w-36 text-right">Harga Beli</th>
                    <th class="py-3 px-6 w-36 text-right">Harga Jual</th>
                    <th class="py-3 px-6 w-28 text-center">Stok</th>
                    <th class="py-3 px-6 w-24 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($items as $item)
                @php
                    $stockQty = $item->inventoryBalances->sum('quantity');
                @endphp
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600">{{ $item->sku }}</td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">
                        {{ $item->name }}
                        @if($item->salesAccount)
                            <span class="block text-xs text-slate-400 font-normal">Akun: {{ $item->salesAccount->name }}</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded {{ $item->type === 'goods' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $item->type === 'goods' ? 'Barang' : 'Jasa' }}
                        </span>
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono text-xs text-slate-600">
                        Rp {{ number_format($item->buy_price, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono text-xs font-medium text-slate-900">
                        Rp {{ number_format($item->sell_price, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center font-mono text-xs">
                        @if($item->type === 'goods')
                            <span class="font-bold {{ $stockQty > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ number_format($stockQty, 0) }}
                            </span>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">Belum ada produk / jasa terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
