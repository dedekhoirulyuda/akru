@extends('layouts.app')

@section('title', 'Kas Kecil (Petty Cash) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" x-data="{ showFundModal: false, showVoucherModal: false }">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kas Kecil (Petty Cash Imprest)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengelolaan kas operasional harian kantor dengan metode saldo tetap (imprest fund) dan pengisian kembali</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" @click="$dispatch('open-fund-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 shadow-sm transition-colors">
            + Daftarkan Kas Kecil
        </button>
        <button type="button" @click="$dispatch('open-voucher-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            + Catat Pengeluaran Kas
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{ fundModal: false, voucherModal: false }" @open-fund-modal.window="fundModal = true" @open-voucher-modal.window="voucherModal = true">

    <!-- Active Petty Cash Funds Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($funds as $f)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200">
                        {{ $f->bankAccount->name ?? 'Kas Kecil' }}
                    </span>
                    <span class="text-xs text-slate-500">
                        Kasir: <strong class="text-slate-700">{{ $f->custodian->name ?? 'Admin' }}</strong>
                    </span>
                </div>
                <h3 class="text-lg font-bold text-slate-900">{{ $f->fund_name }}</h3>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Plafon Tetap (Imprest):</span>
                        <span class="font-mono font-semibold text-slate-800">Rp {{ number_format($f->imprest_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Sisa Saldo Kas Riil:</span>
                        <span class="font-mono font-bold text-emerald-600 text-sm">Rp {{ number_format($f->current_balance, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400">
                    Terpakai: Rp {{ number_format($f->imprest_amount - $f->current_balance, 0, ',', '.') }}
                </span>
                @if($f->current_balance < $f->imprest_amount)
                <form method="POST" action="{{ route('petty-cash.replenish', $f->id) }}" onsubmit="return confirm('Isi kembali kas kecil ini ke plafon penuh?')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-semibold text-xs transition-colors">
                        Isi Kembali (Replenish) ⟳
                    </button>
                </form>
                @else
                <span class="text-xs text-emerald-600 font-medium">Saldo Penuh ✓</span>
                @endif
            </div>
        </div>
        @empty
        <div class="md:col-span-3 bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center text-slate-400">
            Belum ada pos kas kecil (imprest fund) yang didaftarkan. Klik tombol "+ Daftarkan Kas Kecil" di atas.
        </div>
        @endforelse
    </div>

    <!-- Table of Petty Cash Vouchers -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800 text-sm">Riwayat Voucher Pengeluaran Kas Kecil</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-36 whitespace-nowrap">No. Voucher</th>
                        <th class="py-3 px-6 w-28 whitespace-nowrap">Tanggal</th>
                        <th class="py-3 px-6">Pos Kas Kecil</th>
                        <th class="py-3 px-6">Penerima / Pemohon</th>
                        <th class="py-3 px-6">Uraian Keperluan</th>
                        <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Nominal</th>
                        <th class="py-3 px-6 w-28 text-center whitespace-nowrap">Tipe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($vouchers as $v)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-mono font-medium text-blue-600 whitespace-nowrap">
                            {{ $v->voucher_number }}
                        </td>
                        <td class="py-3.5 px-6 text-xs text-slate-600 font-mono whitespace-nowrap">
                            {{ $v->voucher_date->format('d/m/Y') }}
                        </td>
                        <td class="py-3.5 px-6 font-medium text-slate-900">
                            {{ $v->pettyCashFund->fund_name ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 text-slate-800">
                            {{ $v->recipient_name ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 text-xs text-slate-600">
                            {{ $v->description }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-bold {{ $v->type === 'expense' ? 'text-rose-600' : 'text-emerald-600' }} whitespace-nowrap">
                            {{ $v->type === 'expense' ? '-' : '+' }} Rp {{ number_format($v->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $v->type === 'expense' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                {{ $v->type === 'expense' ? 'Pengeluaran' : 'Pengisian' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            Belum ada voucher mutasi kas kecil yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vouchers->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $vouchers->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Tambah Pos Kas Kecil -->
    <div x-show="fundModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div @click="fundModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <div class="relative z-10 inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Daftarkan Pos Kas Kecil Baru</h3>
                <form method="POST" action="{{ route('petty-cash.funds.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Pos Kas</label>
                        <input type="text" name="fund_name" required placeholder="Contoh: Kas Kecil Operasional HO" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Akun Buku Kas/Bank Terkait</label>
                        <select name="bank_account_id" required class="w-full text-sm rounded-lg border-slate-300">
                            @foreach($bankAccounts as $ba)
                                <option value="{{ $ba->id }}">{{ $ba->name }} ({{ $ba->account_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Plafon Saldo Tetap (Imprest Amount Rp)</label>
                        <input type="number" name="imprest_amount" value="5000000" min="100000" step="100000" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Penanggung Jawab (Kasir)</label>
                        <select name="custodian_id" class="w-full text-sm rounded-lg border-slate-300">
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" @click="fundModal = false" class="px-4 py-2 border rounded-lg text-sm text-slate-600">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Simpan Pos Kas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Catat Voucher Pengeluaran -->
    <div x-show="voucherModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div @click="voucherModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <div class="relative z-10 inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Catat Pengeluaran Kas Kecil</h3>
                <form method="POST" action="{{ route('petty-cash.vouchers.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Pilih Pos Kas Kecil</label>
                        <select name="petty_cash_fund_id" required class="w-full text-sm rounded-lg border-slate-300">
                            @foreach($funds as $f)
                                <option value="{{ $f->id }}">{{ $f->fund_name }} (Sisa: Rp {{ number_format($f->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Tanggal Transaksi</label>
                        <input type="date" name="voucher_date" value="{{ date('Y-m-d') }}" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Penerima / Pembawa Bon</label>
                        <input type="text" name="recipient_name" required placeholder="Nama staf / kurir" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Alokasi Akun Beban</label>
                        <select name="expense_account_id" required class="w-full text-sm rounded-lg border-slate-300">
                            @foreach($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Uraian Pengeluaran</label>
                        <input type="text" name="description" required placeholder="Contoh: Beli air galon & snack tamu" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" value="50000" min="1000" step="500" required class="w-full text-sm rounded-lg border-slate-300 font-mono font-bold">
                    </div>
                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" @click="voucherModal = false" class="px-4 py-2 border rounded-lg text-sm text-slate-600">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Catat Pengeluaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
