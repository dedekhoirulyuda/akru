@extends('layouts.pdf')

@section('title', 'Neraca Saldo (Trial Balance) — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">
            NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}
        </div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">NERACA SALDO (TRIAL BALANCE)</h2>
        <div class="meta">Periode: {{ date('d/m/Y', strtotime($dateFrom)) }} s/d {{ date('d/m/Y', strtotime($dateTo)) }}</div>
        <div class="meta">Status: <strong style="color: {{ $isBalanced ? '#059669' : '#e11d48' }};">{{ $isBalanced ? 'BALANCE (SEIMBANG)' : 'TIDAK SEIMBANG' }}</strong></div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 10px;">
    <thead>
        <tr>
            <th rowspan="2" style="width: 80px;">Kode</th>
            <th rowspan="2">Nama Akun Perkiraan</th>
            <th colspan="2" style="text-align: center; border-bottom: 1px solid #cbd5e1;">Saldo Awal</th>
            <th colspan="2" style="text-align: center; border-bottom: 1px solid #cbd5e1;">Mutasi Periode</th>
            <th colspan="2" style="text-align: center; border-bottom: 1px solid #cbd5e1;">Saldo Akhir</th>
        </tr>
        <tr>
            <th style="width: 90px; text-align: right;">Debit (Rp)</th>
            <th style="width: 90px; text-align: right;">Kredit (Rp)</th>
            <th style="width: 90px; text-align: right;">Debit (Rp)</th>
            <th style="width: 90px; text-align: right;">Kredit (Rp)</th>
            <th style="width: 95px; text-align: right;">Debit (Rp)</th>
            <th style="width: 95px; text-align: right;">Kredit (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $r)
        <tr>
            <td style="font-family: monospace; font-weight: 600;">{{ $r['account']->code }}</td>
            <td>{{ $r['account']->name }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $r['opening_debit'] > 0 ? number_format($r['opening_debit'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $r['opening_credit'] > 0 ? number_format($r['opening_credit'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $r['period_debit'] > 0 ? number_format($r['period_debit'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $r['period_credit'] > 0 ? number_format($r['period_credit'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: 600;">{{ $r['closing_debit'] > 0 ? number_format($r['closing_debit'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: 600;">{{ $r['closing_credit'] > 0 ? number_format($r['closing_credit'], 0, ',', '.') : '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data neraca saldo untuk periode ini.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="2" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL KESELURUHAN:</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace;">{{ number_format($grandOpeningDebit, 0, ',', '.') }}</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace;">{{ number_format($grandOpeningCredit, 0, ',', '.') }}</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace;">{{ number_format($grandPeriodDebit, 0, ',', '.') }}</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace;">{{ number_format($grandPeriodCredit, 0, ',', '.') }}</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #1d4ed8;">{{ number_format($grandClosingDebit, 0, ',', '.') }}</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #1d4ed8;">{{ number_format($grandClosingCredit, 0, ',', '.') }}</td>
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
