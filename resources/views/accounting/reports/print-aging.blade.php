@extends('layouts.pdf')

@section('title', 'Laporan Aging Piutang & Hutang — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}</div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">BUKU PEMBANTU & AGING PIUTANG / HUTANG</h2>
        <div class="meta">Posisi Per Tanggal: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>
@endsection

@section('content')
<h3 style="font-size: 11pt; font-weight: bold; color: #1e293b; margin-top: 15px; margin-bottom: 6px; border-bottom: 2px solid #2563eb; padding-bottom: 4px;">
    1. ANALISIS UMUR PIUTANG USAHA (AR AGING)
</h3>
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Pelanggan / No. Faktur</th>
            <th style="width: 75px; text-align: center;">Tgl Faktur</th>
            <th style="width: 75px; text-align: center;">Jatuh Tempo</th>
            <th style="width: 90px; text-align: right;">Sisa Tagihan</th>
            <th style="width: 80px; text-align: right;">Lancar</th>
            <th style="width: 80px; text-align: right;">1-30 Hari</th>
            <th style="width: 80px; text-align: right;">31-60 Hari</th>
            <th style="width: 80px; text-align: right;">>60 Hari</th>
        </tr>
    </thead>
    <tbody>
        @forelse($arAging as $ar)
        <tr>
            <td>
                <strong>{{ $ar['contact'] }}</strong><br>
                <span style="font-family: monospace; font-size: 8pt; color: #64748b;">{{ $ar['number'] }}</span>
            </td>
            <td style="text-align: center;">{{ $ar['date'] ? date('d/m/Y', strtotime($ar['date'])) : '-' }}</td>
            <td style="text-align: center;">{{ $ar['due_date'] ? date('d/m/Y', strtotime($ar['due_date'])) : '-' }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: bold;">{{ number_format($ar['remaining'], 0, ',', '.') }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $ar['bucket_current'] > 0 ? number_format($ar['bucket_current'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $ar['bucket_1_30'] > 0 ? number_format($ar['bucket_1_30'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $ar['bucket_31_60'] > 0 ? number_format($ar['bucket_31_60'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace; color: #e11d48;">
                {{ ($ar['bucket_61_90'] + $ar['bucket_over_90']) > 0 ? number_format($ar['bucket_61_90'] + $ar['bucket_over_90'], 0, ',', '.') : '-' }}
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 15px;">Tidak ada saldo piutang yang belum lunas.</td></tr>
        @endforelse
    </tbody>
</table>

<h3 style="font-size: 11pt; font-weight: bold; color: #1e293b; margin-top: 25px; margin-bottom: 6px; border-bottom: 2px solid #d97706; padding-bottom: 4px;">
    2. ANALISIS UMUR HUTANG USAHA (AP AGING)
</h3>
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Pemasok / No. Tagihan</th>
            <th style="width: 75px; text-align: center;">Tgl Faktur</th>
            <th style="width: 75px; text-align: center;">Jatuh Tempo</th>
            <th style="width: 90px; text-align: right;">Sisa Tagihan</th>
            <th style="width: 80px; text-align: right;">Lancar</th>
            <th style="width: 80px; text-align: right;">1-30 Hari</th>
            <th style="width: 80px; text-align: right;">31-60 Hari</th>
            <th style="width: 80px; text-align: right;">>60 Hari</th>
        </tr>
    </thead>
    <tbody>
        @forelse($apAging as $ap)
        <tr>
            <td>
                <strong>{{ $ap['contact'] }}</strong><br>
                <span style="font-family: monospace; font-size: 8pt; color: #64748b;">{{ $ap['number'] }}</span>
            </td>
            <td style="text-align: center;">{{ $ap['date'] ? date('d/m/Y', strtotime($ap['date'])) : '-' }}</td>
            <td style="text-align: center;">{{ $ap['due_date'] ? date('d/m/Y', strtotime($ap['due_date'])) : '-' }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: bold;">{{ number_format($ap['remaining'], 0, ',', '.') }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $ap['bucket_current'] > 0 ? number_format($ap['bucket_current'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $ap['bucket_1_30'] > 0 ? number_format($ap['bucket_1_30'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ $ap['bucket_31_60'] > 0 ? number_format($ap['bucket_31_60'], 0, ',', '.') : '-' }}</td>
            <td style="text-align: right; font-family: monospace; color: #e11d48;">
                {{ ($ap['bucket_61_90'] + $ap['bucket_over_90']) > 0 ? number_format($ap['bucket_61_90'] + $ap['bucket_over_90'], 0, ',', '.') : '-' }}
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 15px;">Tidak ada saldo hutang yang belum lunas.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection

@section('signatures')
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disiapkan Oleh:</div>
    <div class="signature-line">{{ auth()->user()->name ?? 'AR/AP Staff' }}</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Diperiksa Oleh:</div>
    <div class="signature-line">Finance Supervisor</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disetujui Oleh:</div>
    <div class="signature-line">Finance Director / Owner</div>
</div>
@endsection
