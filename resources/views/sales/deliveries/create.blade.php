@extends('layouts.app')

@section('title', 'Buat Surat Jalan — AKRU')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('deliveries.index') }}" class="p-2 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 transition-colors">
        ←
    </a>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Buat Surat Jalan (Delivery Order) Baru</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengeluaran fisik barang dari titik gudang menuju lokasi penerimaan pelanggan</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <form method="POST" action="{{ route('deliveries.store') }}" class="p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Pelanggan Tujuan *</label>
                <select name="contact_id" required class="w-full text-sm rounded-lg border-slate-300">
                    <option value="">-- Pilih Pelanggan --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Gudang Asal Barang *</label>
                <select name="warehouse_id" required class="w-full text-sm rounded-lg border-slate-300">
                    <option value="">-- Pilih Gudang Asal --</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->code }} — {{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tanggal Pengiriman *</label>
                <input type="date" name="delivery_date" value="{{ date('Y-m-d') }}" required class="w-full text-sm rounded-lg border-slate-300">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Terkait Sales Order (Opsional)</label>
                <select name="sales_order_id" class="w-full text-sm rounded-lg border-slate-300">
                    <option value="">-- Tanpa Referensi SO --</option>
                    @foreach($salesOrders as $so)
                        <option value="{{ $so->id }}">{{ $so->order_number }} ({{ $so->customer->name ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Nama Supir / Kurir</label>
                <input type="text" name="driver_name" placeholder="Nama pengemudi" class="w-full text-sm rounded-lg border-slate-300">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Nomor Polisi Kendaraan</label>
                <input type="text" name="vehicle_number" placeholder="Contoh: B 1234 XYZ" class="w-full text-sm rounded-lg border-slate-300">
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">Item Fisik yang Dikirim</h3>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Produk Fisik</label>
                        <select name="items[0][item_id]" required class="w-full text-sm rounded-lg border-slate-300">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($items as $i)
                                <option value="{{ $i->id }}">{{ $i->code }} — {{ $i->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Kuantitas Kirim</label>
                        <input type="number" name="items[0][quantity]" value="1" min="0.01" step="0.01" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Catatan Pengiriman</label>
            <textarea name="notes" rows="3" placeholder="Instruksi handling, alamat bongkar muat khusus..." class="w-full text-sm rounded-lg border-slate-300"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('deliveries.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-medium">Batal</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-500 shadow-sm text-sm transition-colors">
                Keluarkan Barang & Cetak Surat Jalan
            </button>
        </div>
    </form>
</div>
@endsection
