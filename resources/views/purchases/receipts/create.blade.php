@extends('layouts.app')

@section('title', 'Catat Penerimaan Barang — AKRU')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('goods-receipts.index') }}" class="p-2 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 transition-colors">
        ←
    </a>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Catat Penerimaan Barang Gudang (Goods Receipt) Baru</h1>
        <p class="text-sm text-slate-500 mt-0.5">Konfirmasi fisik barang yang tiba dari pemasok untuk penambahan saldo inventaris</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <form method="POST" action="{{ route('goods-receipts.store') }}" class="p-6 space-y-6">
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
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Gudang Tujuan Masuk *</label>
                <select name="warehouse_id" required class="w-full text-sm rounded-lg border-slate-300">
                    <option value="">-- Pilih Gudang Masuk --</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tanggal Penerimaan *</label>
                <input type="date" name="receipt_date" value="{{ date('Y-m-d') }}" required class="w-full text-sm rounded-lg border-slate-300">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Referensi PO Terkait (Opsional)</label>
                <select name="purchase_order_id" class="w-full text-sm rounded-lg border-slate-300">
                    <option value="">-- Tanpa Referensi PO --</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->po_number }} ({{ $po->supplier->name ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">No. Surat Jalan Vendor / Resi</label>
                <input type="text" name="supplier_delivery_number" placeholder="Contoh: SJ-VENDOR-99882" class="w-full text-sm rounded-lg border-slate-300">
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">Item Fisik yang Diterima</h3>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Kuantitas Diterima Baik</label>
                        <input type="number" name="items[0][accepted_quantity]" value="1" min="0.01" step="0.01" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Catatan Kondisi Barang</label>
            <textarea name="notes" rows="3" placeholder="Kondisi kemasan rapi, segel utuh..." class="w-full text-sm rounded-lg border-slate-300"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('goods-receipts.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-medium">Batal</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-500 shadow-sm text-sm transition-colors">
                Terima Barang & Tambah Stok
            </button>
        </div>
    </form>
</div>
@endsection
