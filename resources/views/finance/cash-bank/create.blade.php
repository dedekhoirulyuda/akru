@extends('layouts.app')

@section('title', 'Catat Mutasi Kas / Bank — AKRU')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
            <a href="{{ route('cash-bank.index') }}" class="hover:text-blue-600">Kas & Bank</a>
            <span>&rsaquo;</span>
            <span class="text-slate-900 font-medium">Catat Mutasi</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Catat Mutasi Kas Masuk / Keluar</h1>
    </div>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('cash-bank.store') }}" x-data="{
    type: 'cash_out',
    bkmNumber: '{{ $bkmNumber }}',
    bkkNumber: '{{ $bkkNumber }}',
    accounts: {{ Js::from($accounts) }},

    get currentNumber() {
        return this.type === 'cash_in' ? this.bkmNumber : this.bkkNumber;
    },
    get filteredAccounts() {
        if (this.type === 'cash_in') {
            return this.accounts.filter(a => ['revenue', 'liability', 'equity'].includes(a.type));
        } else {
            return this.accounts.filter(a => ['expense', 'asset', 'liability'].includes(a.type));
        }
    }
}" class="max-w-2xl mx-auto">
    @csrf

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
        <h3 class="font-bold text-slate-900 text-base border-b border-slate-100 pb-3">Detail Transaksi Kas</h3>

        {{-- Type Toggle --}}
        <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center justify-center p-3 rounded-lg border cursor-pointer font-bold text-sm transition-all"
                :class="type === 'cash_out' ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-500'">
                <input type="radio" name="type" value="cash_out" x-model="type" class="sr-only">
                <span>&darr; Kas Keluar (BKK - Biaya/Pengeluaran)</span>
            </label>

            <label class="flex items-center justify-center p-3 rounded-lg border cursor-pointer font-bold text-sm transition-all"
                :class="type === 'cash_in' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500'">
                <input type="radio" name="type" value="cash_in" x-model="type" class="sr-only">
                <span>&uarr; Kas Masuk (BKM - Pendapatan Lain)</span>
            </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Rekening Kas / Bank Terkait *</label>
                <select name="bank_account_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    @foreach($bankAccounts as $b)
                        <option value="{{ $b->id }}">{{ $b->bank_name }} ({{ $b->account_number ?? 'Kas' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">No. Bukti Transaksi *</label>
                <input type="text" name="transaction_number" :value="currentNumber" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Transaksi *</label>
                <input type="date" name="transaction_date" required value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Penerima / Penyetor (Pihak Terkait)</label>
                <input type="text" name="counterparty" placeholder="Contoh: Bpk. Dani / PLN / Telkom" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1" x-text="type === 'cash_out' ? 'Alokasi Akun Beban / Pengeluaran *' : 'Alokasi Akun Pendapatan / Sumber Dana *'"></label>
            <select name="account_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                <option value="">-- Pilih Akun GL Lawan --</option>
                <template x-for="acc in filteredAccounts" :key="acc.id">
                    <option :value="acc.id" x-text="acc.code + ' - ' + acc.name"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Nominal (Rp) *</label>
            <input type="number" name="total_amount" required min="1" placeholder="500000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-base font-bold font-mono text-slate-900 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Uraian Transaksi</label>
            <textarea name="notes" rows="2" placeholder="Contoh: Pembayaran tagihan internet kantor bulan berjalan" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <a href="{{ route('cash-bank.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-500 shadow-sm transition-all">
                Simpan & Post ke Buku Besar
            </button>
        </div>
    </div>
</form>
@endsection
