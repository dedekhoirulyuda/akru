@extends('layouts.app')

@section('title', 'Buat Purchase Order — AKRU')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('purchase-orders.index') }}" class="p-2 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 transition-colors">
        ←
    </a>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Buat Pesanan Pembelian (Purchase Order) Baru</h1>
        <p class="text-sm text-slate-500 mt-0.5">Penerbitan PO resmi ke vendor rekanan untuk pengadaan barang</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <form method="POST" action="{{ route('purchase-orders.store') }}" class="p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Pemasok / Supplier *</label>
                <select name="contact_id" required class="w-full text-sm rounded-lg border-slate-300">
                    <option value="">-- Pilih Supplier --</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tanggal PO *</label>
                <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required class="w-full text-sm rounded-lg border-slate-300">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Target Kedatangan</label>
                <input type="date" name="expected_arrival_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="w-full text-sm rounded-lg border-slate-300">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Berdasarkan PR Internal (Opsional)</label>
            <select name="purchase_request_id" class="w-full text-sm rounded-lg border-slate-300">
                <option value="">-- Tanpa Permintaan Pembelian (PO Langsung) --</option>
                @foreach($approvedPRs as $pr)
                    <option value="{{ $pr->id }}">{{ $pr->request_number }} ({{ $pr->purpose }})</option>
                @endforeach
            </select>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">Item Barang yang Dipesan</h3>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Produk</label>
                        <select name="items[0][item_id]" required class="w-full text-sm rounded-lg border-slate-300">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($items as $i)
                                <option value="{{ $i->id }}">{{ $i->code }} — {{ $i->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Kuantitas</label>
                        <input type="number" name="items[0][quantity]" value="1" min="0.01" step="0.01" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Harga Satuan Beli (Rp)</label>
                        <input type="number" name="items[0][unit_price]" value="80000" min="0" step="100" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Catatan Pembelian</label>
            <textarea name="notes" rows="3" placeholder="Termin pembayaran, syarat pengiriman ke gudang..." class="w-full text-sm rounded-lg border-slate-300"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-medium">Batal</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-500 shadow-sm text-sm transition-colors">
                Terbitkan Purchase Order
            </button>
        </div>
    </form>
</div>
@endsection
