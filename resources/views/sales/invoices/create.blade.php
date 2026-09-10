@extends('layouts.app')

@section('title', 'Buat Faktur Penjualan Baru — AKRU')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
            <a href="{{ route('sales.index') }}" class="hover:text-blue-600">Faktur Penjualan</a>
            <span>&rsaquo;</span>
            <span class="text-slate-900 font-medium">Buat Baru</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Buat Faktur Penjualan Baru</h1>
    </div>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('sales.store') }}" x-data="{
    lines: [
        { item_id: '', description: '', quantity: 1, unit_price: 0, discount_percent: 0, total: 0 }
    ],
    items: {{ Js::from($items) }},
    taxCodes: {{ Js::from($taxCodes) }},
    selectedTaxCodeId: '{{ $taxCodes->firstWhere('code', 'PPN11')?->id ?? '' }}',

    addLine() {
        this.lines.push({ item_id: '', description: '', quantity: 1, unit_price: 0, discount_percent: 0, total: 0 });
    },
    removeLine(index) {
        if (this.lines.length > 1) {
            this.lines.splice(index, 1);
        }
    },
    onItemChange(line) {
        const selected = this.items.find(i => i.id == line.item_id);
        if (selected) {
            line.description = selected.name;
            line.unit_price = parseFloat(selected.sell_price) || 0;
        }
    },
    get subtotal() {
        return this.lines.reduce((sum, line) => {
            const qty = parseFloat(line.quantity) || 0;
            const price = parseFloat(line.unit_price) || 0;
            const disc = parseFloat(line.discount_percent) || 0;
            const lineSub = qty * price;
            return sum + (lineSub - (lineSub * disc / 100));
        }, 0);
    },
    get taxRate() {
        const tc = this.taxCodes.find(t => t.id == this.selectedTaxCodeId);
        return tc ? parseFloat(tc.rate) : 0;
    },
    get taxAmount() {
        return Math.round((this.subtotal * this.taxRate) / 100);
    },
    get grandTotal() {
        return this.subtotal + this.taxAmount;
    },
    formatRupiah(num) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
    }
}">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Invoice Meta --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h3 class="font-bold text-slate-900 text-base border-b border-slate-100 pb-3">Informasi Faktur & Pelanggan</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pelanggan (Customer) *</label>
                        <select name="contact_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                            <option value="">-- Pilih Pelanggan --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('contact_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->identity_number ?? 'Non-NPWP' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Faktur *</label>
                        <input type="text" name="invoice_number" required value="{{ old('invoice_number', $defaultNumber) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Faktur *</label>
                        <input type="date" name="invoice_date" required value="{{ old('invoice_date', now()->toDateString()) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Jatuh Tempo *</label>
                        <input type="date" name="due_date" required value="{{ old('due_date', now()->addDays(30)->toDateString()) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan / Keterangan Faktur</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Pembayaran dapat ditransfer ke rekening BCA PT Akru Maju Bersama" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Line Items Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-900 text-base">Rincian Barang & Jasa</h3>
                    <button type="button" @click="addLine()" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Baris
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs font-semibold text-slate-500 uppercase border-b border-slate-200">
                                <th class="pb-2 w-48">Pilih Produk</th>
                                <th class="pb-2">Deskripsi</th>
                                <th class="pb-2 w-20 text-center">Qty</th>
                                <th class="pb-2 w-32 text-right">Harga Satuan</th>
                                <th class="pb-2 w-20 text-center">Disc (%)</th>
                                <th class="pb-2 w-32 text-right">Total Net</th>
                                <th class="pb-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(line, idx) in lines" :key="idx">
                                <tr>
                                    <td class="py-2 pr-2">
                                        <select :name="'lines[' + idx + '][item_id]'" x-model="line.item_id" @change="onItemChange(line)" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs">
                                            <option value="">-- Manual / Jasa --</option>
                                            <template x-for="item in items" :key="item.id">
                                                <option :value="item.id" x-text="item.name"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="text" :name="'lines[' + idx + '][description]'" x-model="line.description" required placeholder="Deskripsi baris" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" step="1" min="1" :name="'lines[' + idx + '][quantity]'" x-model.number="line.quantity" required class="w-full text-center rounded-md border border-slate-300 px-2 py-1.5 text-xs">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" step="100" min="0" :name="'lines[' + idx + '][unit_price]'" x-model.number="line.unit_price" required class="w-full text-right rounded-md border border-slate-300 px-2 py-1.5 text-xs">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" step="0.5" min="0" max="100" :name="'lines[' + idx + '][discount_percent]'" x-model.number="line.discount_percent" class="w-full text-center rounded-md border border-slate-300 px-2 py-1.5 text-xs">
                                    </td>
                                    <td class="py-2 text-right font-mono text-xs font-semibold text-slate-800">
                                        <span x-text="formatRupiah((line.quantity * line.unit_price) - ((line.quantity * line.unit_price) * (line.discount_percent || 0) / 100))"></span>
                                    </td>
                                    <td class="py-2 pl-2 text-center">
                                        <button type="button" @click="removeLine(idx)" class="text-rose-500 hover:text-rose-700" :disabled="lines.length === 1">
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right: Summary & Actions --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h3 class="font-bold text-slate-900 text-base border-b border-slate-100 pb-3">Ringkasan Tagihan</h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Skema Pajak Pertambahan Nilai (PPN)</label>
                    <select name="tax_code_id" x-model="selectedTaxCodeId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        <option value="">Tanpa PPN (Bebas / Non-PKP)</option>
                        <template x-for="tc in taxCodes" :key="tc.id">
                            <option :value="tc.id" x-text="tc.code + ' (' + tc.rate + '%) - ' + tc.name"></option>
                        </template>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal DPP:</span>
                        <span class="font-mono font-medium text-slate-900" x-text="formatRupiah(subtotal)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>PPN (<span x-text="taxRate"></span>%):</span>
                        <span class="font-mono font-medium text-slate-900" x-text="formatRupiah(taxAmount)"></span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-slate-200">
                        <span>Total Tagihan:</span>
                        <span class="text-blue-600 font-mono" x-text="formatRupiah(grandTotal)"></span>
                    </div>
                </div>

                <div class="pt-4 space-y-2">
                    <button type="submit" name="action" value="post" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-500 shadow-sm shadow-blue-500/20 transition-all">
                        Simpan & Post ke Buku Besar
                    </button>
                    <button type="submit" name="action" value="draft" class="w-full rounded-lg bg-slate-100 border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition-all">
                        Simpan Sebagai Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
