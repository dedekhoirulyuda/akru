@extends('layouts.pdf')

@section('title', 'Buku Besar — ' . ($selectedAccount ? $selectedAccount->code : 'All'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">
            NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}
        </div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">BUKU BESAR (GENERAL LEDGER)</h2>
        <div style="font-size: 11pt; font-weight: bold; color: #2563eb;">
            {{ $selectedAccount ? ($selectedAccount->code . ' - ' . $selectedAccount->name) : 'Semua Akun' }}
        </div>
        <div class="meta">Periode: {{ date('d/m/Y', strtotime($dateFrom)) }} s/d {{ date('d/m/Y', strtotime($dateTo)) }}</div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 10px;">
    <thead>
        <tr>
            <th style="width: 80px; text-align: center;">Tanggal</th>
            <th style="width: 130px;">No. Jurnal</th>
            <th style="width: 90px;">Sumber</th>
            <th>Uraian / Keterangan</th>
            <th style="width: 110px; text-align: right;">Debit (Rp)</th>
            <th style="width: 110px; text-align: right;">Kredit (Rp)</th>
            <th style="width: 120px; text-align: right;">Saldo (Rp)</th>
        </tr>
    </thead>
    <tbody>
        {{-- Saldo Awal --}}
        <tr style="background: #f8fafc; font-style: italic;">
            <td style="text-align: center;">{{ date('d/m/Y', strtotime($dateFrom)) }}</td>
            <td>-</td>
            <td style="font-size: 8pt; font-weight: bold; color: #475569;">SALDO AWAL</td>
            <td>Saldo Awal per {{ date('d/m/Y', strtotime($dateFrom)) }}</td>
            <td style="text-align: right;">-</td>
            <td style="text-align: right;">-</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace;">{{ number_format($openingBalance, 0, ',', '.') }}</td>
        </tr>

        @forelse($ledgerEntries as $entry)
        <tr>
            <td style="text-align: center;">{{ $entry->journal_date ? date('d/m/Y', strtotime($entry->journal_date)) : '-' }}</td>
            <td style="font-family: monospace; font-weight: 600;">{{ $entry->journal_number }}</td>
            <td style="font-size: 8pt; color: #64748b;">{{ $entry->source_type ?? '-' }}</td>
            <td>{{ $entry->memo ?: ($entry->header_description ?: '-') }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $entry->debit > 0 ? number_format($entry->debit, 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $entry->credit > 0 ? number_format($entry->credit, 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: 600;">{{ number_format($entry->running_balance, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align: center; padding: 15px; color: #94a3b8;">
                Tidak ada mutasi transaksi pada periode ini.
            </td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="4" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL MUTASI & SALDO AKHIR:</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; padding: 8px;">
                Rp {{ number_format($totalDebit, 0, ',', '.') }}
            </td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; padding: 8px;">
                Rp {{ number_format($totalCredit, 0, ',', '.') }}
            </td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #1d4ed8; padding: 8px;">
                Rp {{ number_format($closingBalance, 0, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>
@endsection

@section('signatures')
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disiapkan Oleh:</div>
    <div class="signature-line">{{ auth()->user()->name ?? 'Accounting Staff' }}</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Diperiksa Oleh:</div>
    <div class="signature-line">Accounting Supervisor</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disetujui Oleh:</div>
    <div class="signature-line">Finance Director / Owner</div>
</div>
@endsection
