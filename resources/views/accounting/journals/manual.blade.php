@extends('layouts.app')

@section('title', 'Buat Jurnal Penyesuaian (Manual) — AKRU')

@section('header')
<div class="flex items-center gap-3">
    <a href="{{ route('journals.index') }}" class="p-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Buat Jurnal Penyesuaian (Manual)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Entri jurnal memorial, penyesuaian akhir periode (akrual/penyusutan), atau saldo awal</p>
    </div>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('manual-journals.store') }}" 
      x-data="{
        lines: [
            { account_id: '', description: '', debit: 0, credit: 0, contact_id: '' },
            { account_id: '', description: '', debit: 0, credit: 0, contact_id: '' }
        ],
        addRow() {
            this.lines.push({ account_id: '', description: '', debit: 0, credit: 0, contact_id: '' });
        },
        removeRow(index) {
            if (this.lines.length > 2) {
                this.lines.splice(index, 1);
            }
        },
        get totalDebit() {
            return this.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0);
        },
        get totalCredit() {
            return this.lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0);
        },
        get isBalanced() {
            return this.totalDebit > 0 && Math.abs(this.totalDebit - this.totalCredit) < 0.01;
        },
        formatRupiah(amount) {
            return 'Rp ' + (new Intl.NumberFormat('id-ID')).format(amount);
        }
      }"
      class="space-y-6">
    @csrf

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tanggal Transaksi</label>
                <input type="date" name="journal_date" value="{{ date('Y-m-d') }}" required class="w-full text-sm rounded-lg border-slate-300">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Keterangan / Uraian Transaksi</label>
                <input type="text" name="description" placeholder="Contoh: Penyesuaian beban sewa dibayar di muka September 2026" required class="w-full text-sm rounded-lg border-slate-300">
            </div>
        </div>
    </div>

    <!-- Balance Status Bar -->
    <div class="p-4 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all"
         :class="isBalanced ? 'bg-emerald-50/80 border-emerald-300 text-emerald-900' : 'bg-rose-50/80 border-rose-300 text-rose-900'">
        <div class="flex items-center gap-3">
            <template x-if="isBalanced">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </template>
            <template x-if="!isBalanced">
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </template>
            <div>
                <div class="font-bold text-sm" x-text="isBalanced ? 'Jurnal Seimbang (Ready to Post)' : 'Jurnal Belum Seimbang!'"></div>
                <div class="text-xs" x-text="isBalanced ? 'Total debit sama dengan total kredit.' : 'Selisih: ' + formatRupiah(Math.abs(totalDebit - totalCredit))"></div>
            </div>
        </div>
        <div class="flex items-center gap-6 font-mono font-bold text-sm">
            <div>Debit: <span x-text="formatRupiah(totalDebit)"></span></div>
            <div>Kredit: <span x-text="formatRupiah(totalCredit)"></span></div>
        </div>
    </div>

    <!-- Lines Entry Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800 text-sm">Baris Jurnal Pembukuan</h2>
            <button type="button" @click="addRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-semibold border border-blue-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Baris
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-10 text-center">#</th>
                        <th class="py-3 px-4 w-72">Akun Perkiraan (COA)</th>
                        <th class="py-3 px-4">Keterangan</th>
                        <th class="py-3 px-4 w-44">Kontak Terkait (Opsional)</th>
                        <th class="py-3 px-4 w-40 text-right">Debit (Rp)</th>
                        <th class="py-3 px-4 w-40 text-right">Kredit (Rp)</th>
                        <th class="py-3 px-4 w-12 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <template x-for="(line, index) in lines" :key="index">
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 text-center font-mono text-xs text-slate-400" x-text="index + 1"></td>
                            <td class="py-3 px-4">
                                <select :name="'lines[' + index + '][account_id]'" x-model="line.account_id" required class="w-full text-xs rounded-lg border-slate-300">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-3 px-4">
                                <input type="text" :name="'lines[' + index + '][description]'" x-model="line.description" placeholder="Uraian baris (opsional)" class="w-full text-xs rounded-lg border-slate-300">
                            </td>
                            <td class="py-3 px-4">
                                <select :name="'lines[' + index + '][contact_id]'" x-model="line.contact_id" class="w-full text-xs rounded-lg border-slate-300">
                                    <option value="">- Tidak ada -</option>
                                    @foreach($contacts as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" step="100" min="0" :name="'lines[' + index + '][debit]'" x-model="line.debit" placeholder="0" class="w-full text-xs rounded-lg border-slate-300 font-mono text-right">
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" step="100" min="0" :name="'lines[' + index + '][credit]'" x-model="line.credit" placeholder="0" class="w-full text-xs rounded-lg border-slate-300 font-mono text-right">
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button type="button" @click="removeRow(index)" class="text-slate-400 hover:text-rose-600">✕</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end gap-3">
        <a href="{{ route('journals.index') }}" class="px-5 py-2.5 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</a>
        <button type="submit" :disabled="!isBalanced" :class="isBalanced ? 'bg-blue-600 hover:bg-blue-500 cursor-pointer' : 'bg-slate-300 cursor-not-allowed'" class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
            Posting Jurnal Sekarang
        </button>
    </div>
</form>
@endsection
