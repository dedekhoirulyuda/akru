@extends('layouts.app')

@section('title', 'Pemantauan PPh Withholding & Bukti Potong — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Pemantauan PPh Pemotongan & Bukti Potong</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Subledger kepatuhan PPh Pasal 21 (Gaji/Tenaga Ahli), PPh Pasal 23 (Jasa/Sewa), dan PPh Final Pasal 4 ayat (2)</p>
    </div>
</div>
@endsection

@section('content')
<!-- Filter Box -->
<div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm mb-6">
    <form method="GET" action="{{ route('tax.pph') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Masa Pajak</label>
            <input type="month" name="tax_period" value="{{ $taxPeriod }}" class="text-sm rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Jenis PPh</label>
            <select name="tax_type" class="text-sm rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                <option value="ALL" {{ $taxType === 'ALL' ? 'selected' : '' }}>-- Semua Jenis PPh --</option>
                <option value="PPH21" {{ $taxType === 'PPH21' ? 'selected' : '' }}>PPh Pasal 21 (Karyawan/Ahli)</option>
                <option value="PPH23" {{ $taxType === 'PPH23' ? 'selected' : '' }}>PPh Pasal 23 (Jasa/Dividen/Royalti)</option>
                <option value="PPH4_2" {{ $taxType === 'PPH4_2' ? 'selected' : '' }}>PPh Final Pasal 4 ayat (2) (Sewa/Konstruksi)</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium transition-colors cursor-pointer">
            Filter Data
        </button>
    </form>
</div>

<!-- Recap Cards by Tax Type -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    @forelse($recapByType as $rc)
    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase text-slate-400 dark:text-slate-500">Pajak {{ $rc->tax_type }}</span>
            <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 font-semibold">{{ $rc->count }} Bupot</span>
        </div>
        <div class="text-2xl font-bold font-mono text-purple-700 dark:text-purple-400 mt-2">
            Rp {{ number_format($rc->total_tax, 0, ',', '.') }}
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Dasar Pengenaan Pajak: Rp {{ number_format($rc->total_base, 0, ',', '.') }}</div>
    </div>
    @empty
    <div class="col-span-3 bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 text-center text-slate-400 dark:text-slate-500 text-sm">
        Belum ada bukti pemotongan PPh yang tercatat untuk masa pajak {{ $taxPeriod }}.
    </div>
    @endforelse
</div>

<!-- Withholding Table -->
<div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/60 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800 dark:text-white text-sm">Daftar Bukti Potong Unifikasi PPh</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-28">Tanggal</th>
                    <th class="py-3 px-6 w-28">Jenis PPh</th>
                    <th class="py-3 px-6">Nama Penerima Penghasilan</th>
                    <th class="py-3 px-6 w-44">NPWP / NIK</th>
                    <th class="py-3 px-6 w-36 text-right">DPP Bruto</th>
                    <th class="py-3 px-6 w-20 text-center">Tarif</th>
                    <th class="py-3 px-6 w-36 text-right">PPh Dipotong</th>
                    <th class="py-3 px-6 w-28 text-center">Dokumen Ref</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                @forelse($entries as $e)
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-600 dark:text-slate-400">{{ $e->tax_date->format('d/m/Y') }}</td>
                    <td class="py-3.5 px-6 font-mono text-xs font-bold text-purple-700 dark:text-purple-400">{{ $e->tax_type }}</td>
                    <td class="py-3.5 px-6 font-medium text-slate-900 dark:text-white">{{ $e->counterparty_name ?? ($e->contact->name ?? '-') }}</td>
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $e->counterparty_npwp ?? ($e->contact->npwp ?? '-') }}</td>
                    <td class="py-3.5 px-6 text-right font-mono text-slate-900 dark:text-white font-medium">
                        Rp {{ number_format($e->base_amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center font-mono text-xs text-slate-600 dark:text-slate-400">{{ $e->rate }}%</td>
                    <td class="py-3.5 px-6 text-right font-mono text-purple-700 dark:text-purple-400 font-bold">
                        Rp {{ number_format($e->tax_amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3.5 px-6 text-center font-mono text-xs text-slate-500 dark:text-slate-400">
                        {{ $e->invoice_number ?? ($e->source_type . ' #' . $e->source_id) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500">
                        Tidak ada transaksi pemotongan PPh yang ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
