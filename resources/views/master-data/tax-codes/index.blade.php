@extends('layouts.app')

@section('title', 'Tarif Pajak (Tax Codes) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Tarif Pajak Indonesia</h1>
        <p class="text-sm text-slate-500 mt-0.5">Konfigurasi tarif PPN & PPh sesuai regulasi perpajakan Indonesia (UU HPP & Coretax Ready)</p>
    </div>
    <div x-data="{ openModal: false }">
        <button @click="openModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kode Pajak
        </button>

        {{-- Modal Tambah Kode Pajak --}}
        <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4" style="display: none;">
            <div @click.away="openModal = false" class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Tambah Kode Pajak Baru</h3>
                <form method="POST" action="{{ route('tax-codes.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Pajak</label>
                            <input type="text" name="code" required placeholder="PPN11" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Pajak</label>
                            <select name="tax_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                                <option value="PPN">PPN (Pajak Pertambahan Nilai)</option>
                                <option value="PPH21">PPh 21 (Gaji & Tenaga Ahli)</option>
                                <option value="PPH22">PPh 22 (Impor & Pembelian Bahan)</option>
                                <option value="PPH23">PPh 23 (Jasa & Sewa Alat)</option>
                                <option value="PPH4_2">PPh 4(2) Final (Sewa Gedung)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama / Deskripsi</label>
                        <input type="text" name="name" required placeholder="PPN 11% Tarif Standar" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tarif Pajak (%)</label>
                        <input type="number" step="0.01" name="rate" required min="0" max="100" placeholder="11.00" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Akun Hutang Pajak (Penjualan / Kredit)</label>
                        <select name="sales_account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                            <option value="">-- Pilih Akun GL --</option>
                            @foreach($accounts->where('type', 'liability') as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Akun Piutang Pajak (Pembelian / Debit)</label>
                        <select name="purchase_account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                            <option value="">-- Pilih Akun GL --</option>
                            @foreach($accounts->where('type', 'asset') as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Simpan Tarif</button>
                    </div>
                </form>
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
                    <th class="py-3 px-6 w-32">Kode</th>
                    <th class="py-3 px-6">Nama & Deskripsi Pajak</th>
                    <th class="py-3 px-6 w-28 text-center">Jenis</th>
                    <th class="py-3 px-6 w-28 text-right">Tarif</th>
                    <th class="py-3 px-6">Akun Keluaran</th>
                    <th class="py-3 px-6">Akun Masukan</th>
                    <th class="py-3 px-6 w-24 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($taxCodes as $tc)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono font-medium text-blue-600">{{ $tc->code }}</td>
                    <td class="py-3.5 px-6 font-medium text-slate-900">{{ $tc->name }}</td>
                    <td class="py-3.5 px-6 text-center">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-slate-100 text-slate-800">
                            {{ $tc->tax_type }}
                        </span>
                    </td>
                    <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900">
                        {{ number_format($tc->rate, 2) }}%
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600">
                        {{ $tc->salesAccount ? $tc->salesAccount->code . ' - ' . $tc->salesAccount->name : '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600">
                        {{ $tc->purchaseAccount ? $tc->purchaseAccount->code . ' - ' . $tc->purchaseAccount->name : '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">Belum ada kode pajak terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
