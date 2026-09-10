@extends('layouts.app')

@section('title', 'Rekonsiliasi Bank — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Rekonsiliasi Bank</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pencocokan mutasi catatan buku besar (GL) dengan rekening koran bank fisik</p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET" action="{{ route('reconciliation.index') }}" class="flex items-center gap-2">
            <select name="bank_account_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm focus:border-blue-500">
                @foreach($bankAccounts as $b)
                    <option value="{{ $b->id }}" {{ $selectedBank?->id == $b->id ? 'selected' : '' }}>
                        {{ $b->bank_name }} ({{ $b->account_number ?? 'Kas' }})
                    </option>
                @endforeach
            </select>
        </form>
        <x-export-dropdown 
            :excelRoute="route('reconciliation.export.excel', ['bank_account_id' => $selectedBank?->id])"
            :pdfRoute="route('reconciliation.export.pdf', ['bank_account_id' => $selectedBank?->id])"
        />
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{
    bookBalance: {{ (float) $bookBalance }},
    statementBalance: {{ (float) $bookBalance }},
    formatRupiah(num) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
    },
    get difference() {
        return this.statementBalance - this.bookBalance;
    }
}">
    {{-- Reconciliation Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Saldo Buku Besar (GL)</p>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-2" x-text="formatRupiah(bookBalance)"></p>
            <p class="text-xs text-slate-500 mt-1">Berdasarkan seluruh jurnal terposting pada akun {{ $selectedBank->account->code ?? '' }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Saldo Akhir Rekening Koran</label>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-sm text-slate-400 font-bold">Rp</span>
                <input type="number" x-model.number="statementBalance" class="w-full text-xl font-bold font-mono text-blue-600 rounded-lg border border-slate-300 px-3 py-1.5 focus:border-blue-500">
            </div>
            <p class="text-xs text-slate-400 mt-1">Ketik saldo akhir menurut mutasi fisik bank Anda</p>
        </div>

        <div class="rounded-xl shadow-sm border p-5 transition-colors"
            :class="difference === 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200'">
            <p class="text-xs font-semibold uppercase tracking-wider" :class="difference === 0 ? 'text-emerald-700' : 'text-rose-700'">
                Selisih Rekonsiliasi
            </p>
            <p class="text-2xl font-bold font-mono mt-2" :class="difference === 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="formatRupiah(difference)"></p>
            <p class="text-xs mt-1" :class="difference === 0 ? 'text-emerald-700' : 'text-rose-700'" x-text="difference === 0 ? '✓ Saldo buku dan rekening koran cocok sempurna.' : '⚠ Terdapat perbedaan yang perlu ditelusuri.'"></p>
        </div>
    </div>

    {{-- Movements Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Mutasi Buku Kas & Bank Terposting</h3>
            <span class="text-xs text-slate-500 font-mono">{{ $movements->count() }} Transaksi Tercatat</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-12 text-center">Status</th>
                        <th class="py-3 px-6 w-28">Tanggal</th>
                        <th class="py-3 px-6 w-36">No. Jurnal</th>
                        <th class="py-3 px-6">Keterangan Transaksi</th>
                        <th class="py-3 px-6 w-36 text-right">Debit (Masuk)</th>
                        <th class="py-3 px-6 w-36 text-right">Kredit (Keluar)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($movements as $m)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-6 text-center">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                ✓
                            </span>
                        </td>
                        <td class="py-3 px-6 font-mono text-xs text-slate-600">{{ \Carbon\Carbon::parse($m->journal_date)->format('d/m/Y') }}</td>
                        <td class="py-3 px-6 font-mono text-xs font-medium text-blue-600">{{ $m->journal_number }}</td>
                        <td class="py-3 px-6 text-xs text-slate-800">{{ $m->description }}</td>
                        <td class="py-3 px-6 text-right font-mono text-xs {{ $m->debit > 0 ? 'font-bold text-emerald-600' : 'text-slate-300' }}">
                            {{ $m->debit > 0 ? number_format($m->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-6 text-right font-mono text-xs {{ $m->credit > 0 ? 'font-bold text-rose-600' : 'text-slate-300' }}">
                            {{ $m->credit > 0 ? number_format($m->credit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400">Belum ada mutasi pada rekening ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
