@extends('layouts.app')

@section('title', 'Catat Penerimaan Pembayaran — AKRU')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
            <a href="{{ route('receipts.index') }}" class="hover:text-blue-600">Penerimaan Pembayaran</a>
            <span>&rsaquo;</span>
            <span class="text-slate-900 font-medium">Catat Baru</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Catat Penerimaan Pembayaran Pelanggan</h1>
    </div>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('receipts.store') }}" x-data="{
    customerId: '',
    invoices: {{ Js::from($unpaidInvoices) }},
    allocatedAmounts: {},
    totalReceived: 0,

    get filteredInvoices() {
        if (!this.customerId) return [];
        return this.invoices.filter(i => i.contact_id == this.customerId);
    },
    autoFill(inv) {
        this.allocatedAmounts[inv.id] = inv.remaining_amount;
        this.recalculateTotal();
    },
    recalculateTotal() {
        let sum = 0;
        for (let key in this.allocatedAmounts) {
            sum += parseFloat(this.allocatedAmounts[key]) || 0;
        }
        this.totalReceived = sum;
    },
    formatRupiah(num) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
    }
}">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h3 class="font-bold text-slate-900 text-base border-b border-slate-100 pb-3">Informasi Pembayaran</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Pelanggan *</label>
                        <select name="contact_id" x-model="customerId" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                            <option value="">-- Pilih Pelanggan --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Setor ke Rekening / Kas *</label>
                        <select name="bank_account_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                            <option value="">-- Pilih Kas / Bank --</option>
                            @foreach($bankAccounts as $b)
                                <option value="{{ $b->id }}">{{ $b->bank_name }} ({{ $b->account_number ?? 'Kas' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">No. Bukti Penerimaan *</label>
                        <input type="text" name="receipt_number" required value="{{ $defaultNumber }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Penerimaan *</label>
                        <input type="date" name="receipt_date" required value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Pembayaran</label>
                        <select name="payment_method" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                            <option value="transfer">Transfer Bank</option>
                            <option value="cash">Tunai / Kas Langsung</option>
                            <option value="giro">Cek / Bilyet Giro</option>
                            <option value="qris">QRIS / Payment Gateway</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Referensi Bank</label>
                        <input type="text" name="reference_number" placeholder="Contoh: TRF-BCA-12345" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea name="notes" rows="2" placeholder="Catatan penerimaan kas" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500"></textarea>
                </div>
            </div>

            {{-- Unpaid Invoices Allocation Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h3 class="font-bold text-slate-900 text-base border-b border-slate-100 pb-3">Alokasi ke Faktur Belum Lunas</h3>

                <div x-show="!customerId" class="py-6 text-center text-slate-400 text-sm">
                    Silakan pilih pelanggan terlebih dahulu untuk memuat tagihan yang belum lunas.
                </div>

                <div x-show="customerId && filteredInvoices.length === 0" class="py-6 text-center text-emerald-600 text-sm" style="display: none;">
                    ✓ Tidak ada tagihan tertunggak untuk pelanggan ini. Seluruh faktur telah lunas.
                </div>

                <div x-show="filteredInvoices.length > 0" class="overflow-x-auto" style="display: none;">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs font-semibold text-slate-500 uppercase border-b border-slate-200">
                                <th class="pb-2">No. Faktur</th>
                                <th class="pb-2 w-28">Tanggal</th>
                                <th class="pb-2 w-36 text-right">Total Faktur</th>
                                <th class="pb-2 w-36 text-right">Sisa Piutang</th>
                                <th class="pb-2 w-44 text-right">Jumlah Alokasi (Rp)</th>
                                <th class="pb-2 w-16"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(inv, idx) in filteredInvoices" :key="inv.id">
                                <tr>
                                    <td class="py-3 font-mono font-medium text-blue-600" x-text="inv.invoice_number"></td>
                                    <td class="py-3 font-mono text-xs text-slate-500" x-text="inv.invoice_date"></td>
                                    <td class="py-3 text-right font-mono text-xs text-slate-600" x-text="formatRupiah(inv.total_amount)"></td>
                                    <td class="py-3 text-right font-mono text-xs font-bold text-rose-600" x-text="formatRupiah(inv.remaining_amount)"></td>
                                    <td class="py-3 text-right">
                                        <input type="hidden" :name="'allocations[' + idx + '][invoice_id]'" :value="inv.id">
                                        <input type="number" step="100" min="0" :max="inv.remaining_amount" :name="'allocations[' + idx + '][amount]'" x-model.number="allocatedAmounts[inv.id]" @input="recalculateTotal()" placeholder="0" class="w-full text-right rounded-md border border-slate-300 px-2 py-1 text-xs font-mono font-semibold">
                                    </td>
                                    <td class="py-3 pl-2 text-right">
                                        <button type="button" @click="autoFill(inv)" class="text-xs text-blue-600 hover:underline">
                                            Lunas
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right: Total & Submit --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h3 class="font-bold text-slate-900 text-base border-b border-slate-100 pb-3">Ringkasan Penerimaan</h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total Dana Diterima (Rp) *</label>
                    <input type="number" name="total_amount" x-model.number="totalReceived" required min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-base font-bold font-mono text-blue-600 focus:border-blue-500">
                    <p class="text-xs text-slate-400 mt-1">Nilai terhitung otomatis dari total alokasi faktur.</p>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-500 shadow-sm shadow-emerald-500/20 transition-all">
                        Simpan & Post ke Buku Besar
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
