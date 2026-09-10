@extends('layouts.app')

@section('title', 'Buat Penawaran Harga — AKRU')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('quotations.index') }}" class="p-2 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 transition-colors">
        ←
    </a>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Buat Penawaran Harga Baru</h1>
        <p class="text-sm text-slate-500 mt-0.5">Terbitkan penawaran estimasi biaya dan barang resmi untuk calon pelanggan</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <form method="POST" action="{{ route('quotations.store') }}" class="p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Pelanggan / Prospek *</label>
                <select name="contact_id" required class="w-full text-sm rounded-lg border-slate-300">
                    <option value="">-- Pilih Pelanggan --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tanggal Penawaran *</label>
                <input type="date" name="quotation_date" value="{{ date('Y-m-d') }}" required class="w-full text-sm rounded-lg border-slate-300">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Berlaku Hingga *</label>
                <input type="date" name="valid_until" value="{{ date('Y-m-d', strtotime('+14 days')) }}" required class="w-full text-sm rounded-lg border-slate-300">
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">Item Penawaran Barang / Jasa</h3>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Produk / Jasa</label>
                        <select name="items[0][item_id]" required class="w-full text-sm rounded-lg border-slate-300">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($items as $i)
                                <option value="{{ $i->id }}">{{ $i->code }} — {{ $i->name }} (Rp {{ number_format($i->sale_price ?? 0, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Kuantitas</label>
                        <input type="number" name="items[0][quantity]" value="1" min="0.01" step="0.01" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Harga Satuan (Rp)</label>
                        <input type="number" name="items[0][unit_price]" value="100000" min="0" step="100" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Catatan / Syarat Ketentuan</label>
            <textarea name="notes" rows="3" placeholder="Contoh: Harga belum termasuk biaya pengiriman. Pembayaran bertahap 50% DP." class="w-full text-sm rounded-lg border-slate-300"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('quotations.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-medium">Batal</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-500 shadow-sm text-sm transition-colors">
                Terbitkan Penawaran
            </button>
        </div>
    </form>
</div>
@endsection
