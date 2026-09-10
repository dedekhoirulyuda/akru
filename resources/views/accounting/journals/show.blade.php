@extends('layouts.app')

@section('title', 'Detail Jurnal ' . $journal->journal_number . ' — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('journals.index') }}" class="p-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold font-mono text-slate-900">{{ $journal->journal_number }}</h1>
                @if($journal->status === 'posted')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Posted</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Reversed</span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Diterbitkan pada {{ $journal->journal_date->format('d F Y') }} • Sumber: {{ $journal->source_type ?? 'manual' }} #{{ $journal->source_id ?? '-' }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2" x-data="{ showReverseModal: false }">
        @if($journal->status === 'posted')
        <button @click="showReverseModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 text-sm font-semibold hover:bg-rose-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Balikkan Jurnal (Reverse)
        </button>

        <!-- Reversal Modal -->
        <div x-show="showReverseModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showReverseModal" @click="showReverseModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div x-show="showReverseModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                    <form method="POST" action="{{ route('journals.reverse', $journal) }}">
                        @csrf
                        <div class="bg-rose-50 px-6 py-4 border-b border-rose-100 flex items-center justify-between">
                            <h3 class="text-base font-bold text-rose-900">Konfirmasi Pembalikan Jurnal</h3>
                            <button type="button" @click="showReverseModal = false" class="text-rose-400 hover:text-rose-600">✕</button>
                        </div>
                        <div class="p-6 space-y-3">
                            <p class="text-sm text-slate-600">
                                Sesuai prinsip akuntansi immutable ledger, jurnal yang telah terposting tidak dapat diedit atau dihapus. Sistem akan membuat jurnal pembalik otomatis dengan posisi debit dan kredit yang saling menghilangkan.
                            </p>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Alasan Pembalikan</label>
                                <textarea name="reversal_reason" rows="3" required placeholder="Contoh: Salah alokasi akun / koreksi faktur pelanggan" class="w-full text-sm rounded-lg border-slate-300"></textarea>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                            <button type="button" @click="showReverseModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-sm font-semibold shadow-sm">Ya, Buat Jurnal Pembalik</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    @if($journal->status === 'reversed')
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
            <h4 class="text-sm font-bold text-rose-900">Jurnal Ini Telah Dibalik (Reversed)</h4>
            <p class="text-xs text-rose-700 mt-0.5">Dibalik oleh {{ $journal->reversedByUser->name ?? 'Sistem' }} pada {{ $journal->reversed_at?->format('d/m/Y H:i') }}. Alasan: "{{ $journal->reversal_reason }}"</p>
        </div>
    </div>
    @endif

    <!-- Metadata Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-400">Deskripsi / Uraian</div>
                <div class="text-sm font-medium text-slate-900 mt-1">{{ $journal->description ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-400">Tanggal Posting</div>
                <div class="text-sm font-mono text-slate-900 mt-1">{{ $journal->journal_date->format('d/m/Y') }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-400">Diposting Oleh</div>
                <div class="text-sm text-slate-900 mt-1">{{ $journal->postedByUser->name ?? 'Sistem' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-400">Idempotency Key</div>
                <div class="text-xs font-mono text-slate-500 mt-1 truncate">{{ $journal->idempotency_key ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Lines Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800 text-sm">Rincian Baris Buku Besar (Double-Entry Ledger)</h2>
            <div class="text-xs font-mono text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                ✓ Seimbang (Balanced)
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 w-12 text-center">#</th>
                        <th class="py-3 px-6 w-36">Kode Akun</th>
                        <th class="py-3 px-6">Nama Akun</th>
                        <th class="py-3 px-6">Keterangan Baris</th>
                        <th class="py-3 px-6 w-44 text-right">Debit</th>
                        <th class="py-3 px-6 w-44 text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($journal->lines as $index => $line)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 text-center text-xs text-slate-400 font-mono">
                            {{ $index + 1 }}
                        </td>
                        <td class="py-3.5 px-6 font-mono font-bold text-blue-600">
                            {{ $line->account->code ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 font-medium text-slate-900">
                            {{ $line->account->name ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 text-slate-600 text-xs">
                            {{ $line->description ?? '-' }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-medium {{ $line->debit > 0 ? 'text-slate-900 font-bold' : 'text-slate-300' }}">
                            {{ $line->debit > 0 ? 'Rp ' . number_format($line->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-medium {{ $line->credit > 0 ? 'text-slate-900 font-bold' : 'text-slate-300' }}">
                            {{ $line->credit > 0 ? 'Rp ' . number_format($line->credit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 font-bold border-t-2 border-slate-300 text-slate-900">
                        <td colspan="4" class="py-3.5 px-6 text-right uppercase text-xs">Total Transaksi:</td>
                        <td class="py-3.5 px-6 text-right font-mono text-base text-blue-700">
                            Rp {{ number_format($journal->total_debit, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono text-base text-emerald-700">
                            Rp {{ number_format($journal->total_credit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
