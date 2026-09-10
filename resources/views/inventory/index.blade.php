@extends('layouts.app')

@section('title', 'Persediaan & Kartu Stok (Inventory) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Persediaan & Kartu Stok (Inventory)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pemantauan stok pergudangan, mutasi kartu stok perpetual, dan penyesuaian stok opname</p>
    </div>
    <div class="flex items-center gap-2">
        <x-export-dropdown 
            :excel-url="route('inventory.export.excel')" 
            :pdf-url="route('inventory.export.pdf')" 
            label="Export Stok" />
        <button @click="$dispatch('open-adjustment-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Penyesuaian Stok (Opname)
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ activeTab: 'balances', showAdjustmentModal: false }" @open-adjustment-modal.window="showAdjustmentModal = true">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-400">Total Nilai Persediaan</div>
            <div class="text-2xl font-bold text-slate-900 mt-1 font-mono">
                Rp {{ number_format($totalValuation, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">Valuasi berbasis metode Moving Average</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-400">Total SKU Aktif</div>
            <div class="text-2xl font-bold text-blue-600 mt-1 font-mono">
                {{ $totalItemsCount }} SKU
            </div>
            <div class="text-xs text-slate-500 mt-1">Barang tercatat dalam master inventaris</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-400">Peringatan Stok Rendah</div>
            <div class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-1 font-mono">
                {{ $lowStockCount }} SKU
            </div>
            <div class="text-xs text-slate-500 mt-1">Stok di bawah batas minimum pemesanan</div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-200 mb-6">
        <button @click="activeTab = 'balances'" :class="activeTab === 'balances' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2.5 text-sm border-b-2 transition-colors">
            Saldo Persediaan Gudang
        </button>
        <button @click="activeTab = 'movements'" :class="activeTab === 'movements' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2.5 text-sm border-b-2 transition-colors">
            Kartu Stok (Mutasi)
        </button>
    </div>

    <!-- Tab 1: Balances Table -->
    <div x-show="activeTab === 'balances'" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800 text-sm">Daftar Saldo Akhir Gudang Utama</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-36">Kode SKU</th>
                        <th class="py-3 px-6">Nama Barang</th>
                        <th class="py-3 px-6 w-28 text-center">Satuan</th>
                        <th class="py-3 px-6 w-32 text-right">Kuantitas</th>
                        <th class="py-3 px-6 w-40 text-right">Biaya Rata-Rata</th>
                        <th class="py-3 px-6 w-44 text-right">Total Valuasi</th>
                        <th class="py-3 px-6 w-28 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($balances as $bal)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-mono font-medium text-blue-600">
                            {{ $bal->item->code ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 font-medium text-slate-900">
                            {{ $bal->item->name ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 text-center text-xs text-slate-500">
                            {{ $bal->item->unit ?? 'PCS' }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900">
                            {{ number_format($bal->quantity, 2, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono text-slate-600">
                            Rp {{ number_format($bal->average_cost, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-semibold text-slate-900">
                            Rp {{ number_format($bal->total_value, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-center">
                            @if($bal->quantity <= 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Habis</span>
                            @elseif($bal->quantity <= ($bal->item->min_stock ?? 5))
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Menipis</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aman</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            Belum ada data saldo barang persediaan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 2: Movements Table (Kartu Stok) -->
    <div x-show="activeTab === 'movements'" style="display: none;">
        <!-- Filters -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
            <form method="GET" action="{{ route('inventory.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Filter Barang (SKU)</label>
                    <select name="item_id" class="w-full text-sm rounded-lg border-slate-300">
                        <option value="">-- Semua Barang --</option>
                        @foreach($items as $it)
                        <option value="{{ $it->id }}" {{ $selectedItemId == $it->id ? 'selected' : '' }}>
                            {{ $it->code }} - {{ $it->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full text-sm rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full text-sm rounded-lg border-slate-300">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('inventory.index') }}" class="px-3 py-2 border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <th class="py-3 px-6 w-28">Tanggal</th>
                            <th class="py-3 px-6">Barang</th>
                            <th class="py-3 px-6 w-32 text-center">Jenis Mutasi</th>
                            <th class="py-3 px-6 w-28 text-right">Masuk / Keluar</th>
                            <th class="py-3 px-6 w-28 text-right">Saldo Akhir</th>
                            <th class="py-3 px-6 w-36 text-right">Nilai Total</th>
                            <th class="py-3 px-6">Keterangan / Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($movements as $m)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-6 font-mono text-xs text-slate-600">
                                {{ $m->movement_date->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-6">
                                <div class="font-medium text-slate-900">{{ $m->item->name ?? '-' }}</div>
                                <div class="font-mono text-xs text-slate-400">{{ $m->item->code ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                @if($m->movement_type === 'purchase')
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Pembelian</span>
                                @elseif($m->movement_type === 'sales')
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">Penjualan</span>
                                @elseif($m->movement_type === 'adjustment')
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">Penyesuaian</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">{{ ucfirst($m->movement_type) }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono font-semibold {{ $m->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $m->quantity > 0 ? '+' : '' }}{{ number_format($m->quantity, 2, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono text-slate-900">
                                {{ number_format($m->balance_after, 2, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono text-slate-900">
                                Rp {{ number_format($m->total_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-500">
                                <div>{{ $m->notes ?? '-' }}</div>
                                @if($m->source_type)
                                <div class="font-mono text-[11px] text-slate-400">Ref: {{ $m->source_type }} #{{ $m->source_id }}</div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                Belum ada riwayat pergerakan stok untuk filter ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $movements->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Modal Penyesuaian Stok (Opname) -->
    <div x-show="showAdjustmentModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showAdjustmentModal" @click="showAdjustmentModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showAdjustmentModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form method="POST" action="{{ route('inventory.adjustment') }}">
                    @csrf
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">Form Penyesuaian Stok (Stock Opname)</h3>
                        <button type="button" @click="showAdjustmentModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Pilih Barang</label>
                            <select name="item_id" required class="w-full text-sm rounded-lg border-slate-300">
                                @foreach($items as $it)
                                <option value="{{ $it->id }}">{{ $it->code }} — {{ $it->name }} ({{ $it->unit ?? 'PCS' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Jenis Penyesuaian</label>
                                <select name="adjustment_type" required class="w-full text-sm rounded-lg border-slate-300">
                                    <option value="in">Penambahan Stok (+ In)</option>
                                    <option value="out">Pengurangan Stok (- Out)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tanggal</label>
                                <input type="date" name="movement_date" value="{{ date('Y-m-d') }}" required class="w-full text-sm rounded-lg border-slate-300">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Kuantitas Selisih</label>
                                <input type="number" step="0.01" min="0.01" name="quantity" required placeholder="0.00" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Harga Pokok (HPP) / Unit</label>
                                <input type="number" step="100" min="0" name="unit_cost" required placeholder="0" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Alasan Penyesuaian / Catatan</label>
                            <textarea name="notes" rows="2" required placeholder="Contoh: Hasil Stock Opname Akhir Bulan / Barang Rusak / Koreksi Fisik" class="w-full text-sm rounded-lg border-slate-300"></textarea>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showAdjustmentModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-semibold shadow-sm">Simpan Penyesuaian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
