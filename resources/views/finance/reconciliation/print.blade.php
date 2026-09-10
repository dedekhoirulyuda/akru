@extends('layouts.pdf')

@section('title', 'Laporan Rekonsiliasi Bank — AKRU')

@section('content')
    {{-- Header --}}
    <div class="company-header">
        <div class="company-name">{{ session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="doc-title">LAPORAN REKONSILIASI BANK (MUTASI GL)</div>
        <div class="doc-subtitle">
            Rekening: <strong>{{ $selectedBank ? $selectedBank->bank_name . ' (' . ($selectedBank->account_number ?? 'Kas Fisik') . ')' : 'Semua Rekening' }}</strong>
            | COA: {{ $selectedBank->account->code ?? '-' }} - {{ $selectedBank->account->name ?? '-' }}
            | Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Ringkasan Saldo Rekonsiliasi --}}
    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
        <div style="flex: 1; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; background: #f8fafc;">
            <div style="font-size: 8pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Total Mutasi Masuk (Debit)</div>
            <div style="font-size: 13pt; font-weight: bold; color: #059669; font-family: monospace; margin-top: 4px;">
                Rp {{ number_format($totalDebit, 0, ',', '.') }}
            </div>
        </div>
        <div style="flex: 1; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; background: #f8fafc;">
            <div style="font-size: 8pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Total Mutasi Keluar (Kredit)</div>
            <div style="font-size: 13pt; font-weight: bold; color: #dc2626; font-family: monospace; margin-top: 4px;">
                Rp {{ number_format($totalCredit, 0, ',', '.') }}
            </div>
        </div>
        <div style="flex: 1; border: 1px solid #93c5fd; border-radius: 6px; padding: 12px; background: #eff6ff;">
            <div style="font-size: 8pt; text-transform: uppercase; color: #1e40af; font-weight: bold;">Saldo Akhir Buku Besar (GL)</div>
            <div style="font-size: 13pt; font-weight: bold; color: #1e3a8a; font-family: monospace; margin-top: 4px;">
                Rp {{ number_format($bookBalance, 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th style="width: 75px;">Tanggal</th>
                <th style="width: 110px;">No. Jurnal</th>
                <th style="width: 90px;">Sumber</th>
                <th>Keterangan / Memo Transaksi</th>
                <th style="width: 100px;" class="text-right">Debit (Masuk)</th>
                <th style="width: 100px;" class="text-right">Kredit (Keluar)</th>
                <th style="width: 110px;" class="text-right">Saldo Kumulatif</th>
            </tr>
        </thead>
        <tbody>
            @php $cumBalance = 0; @endphp
            @forelse($movements as $idx => $m)
                @php $cumBalance += ($m->debit - $m->credit); @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $m->journal_date ? date('d/m/Y', strtotime($m->journal_date)) : '-' }}</td>
                    <td class="font-mono" style="font-weight: 600;">{{ $m->journal_number }}</td>
                    <td><span style="display:inline-block; padding: 2px 6px; background:#f1f5f9; border-radius:3px; font-size:7.5pt; font-weight:bold;">{{ strtoupper(str_replace('_', ' ', $m->source_type ?? 'MANUAL')) }}</span></td>
                    <td>{{ $m->memo ?? '-' }}</td>
                    <td class="text-right font-mono">{{ $m->debit > 0 ? number_format($m->debit, 0, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono">{{ $m->credit > 0 ? number_format($m->credit, 0, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono" style="font-weight: bold; color: {{ $cumBalance >= 0 ? '#0f172a' : '#dc2626' }};">
                        {{ number_format($cumBalance, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 24px; color: #94a3b8;">
                        Tidak ada mutasi buku kas & bank terposting pada periode / rekening ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold; border-top: 2px solid #0f172a;">
                <td colspan="5" class="text-right" style="padding: 8px 10px;">TOTAL MUTASI:</td>
                <td class="text-right font-mono" style="color: #059669; padding: 8px 10px;">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #dc2626; padding: 8px 10px;">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #1e3a8a; padding: 8px 10px;">Rp {{ number_format($bookBalance, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Signatures --}}
    <div class="signature-section">
        <div class="signature-box">
            <div class="title">Disusun Oleh (Staff Treasury),</div>
            <div class="line"></div>
            <div class="name">{{ auth()->user()->name ?? 'Staff Finance' }}</div>
        </div>
        <div class="signature-box">
            <div class="title">Diperiksa Oleh (Supervisor),</div>
            <div class="line"></div>
            <div class="name">( ............................................ )</div>
        </div>
        <div class="signature-box">
            <div class="title">Disetujui Oleh (Finance Manager),</div>
            <div class="line"></div>
            <div class="name">( ............................................ )</div>
        </div>
    </div>
@endsection
