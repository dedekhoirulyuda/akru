@extends('layouts.pdf')

@section('title', 'Laporan Arus Kas — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}</div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">LAPORAN ARUS KAS (CASH FLOW STATEMENT)</h2>
        <div class="meta">Periode: {{ date('d/m/Y', strtotime($dateFrom)) }} s/d {{ date('d/m/Y', strtotime($dateTo)) }}</div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 15px;">
    <thead>
        <tr>
            <th>Aktivitas & Aliran Kas</th>
            <th style="width: 180px;">Sumber Modul</th>
            <th style="width: 150px; text-align: right;">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        {{-- Saldo Awal --}}
        <tr style="background: #f8fafc; font-weight: bold;">
            <td colspan="2">SALDO KAS & SETARA KAS AWAL PERIODE ({{ date('d/m/Y', strtotime($dateFrom)) }})</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($openingCash, 0, ',', '.') }}</td>
        </tr>

        {{-- Arus Kas Masuk --}}
        <tr style="background: #f1f5f9;"><td colspan="3" style="padding-top: 10px;"><strong>ARUS KAS MASUK (PENERIMAAN)</strong></td></tr>
        @forelse($receipts as $r)
        <tr>
            <td style="padding-left: 20px;">Penerimaan Kas Masuk</td>
            <td style="font-size: 8pt; color: #475569;">{{ strtoupper($r->source_type ?? 'Lain-lain') }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($r->amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 20px; color: #94a3b8;">Tidak ada penerimaan kas masuk pada periode ini</td></tr>
        @endforelse
        <tr style="font-weight: bold; background: #ecfdf5;">
            <td colspan="2">TOTAL KAS MASUK</td>
            <td style="text-align: right; font-family: monospace; color: #059669;">Rp {{ number_format($totalIn, 0, ',', '.') }}</td>
        </tr>

        {{-- Arus Kas Keluar --}}
        <tr style="background: #f1f5f9;"><td colspan="3" style="padding-top: 10px;"><strong>ARUS KAS KELUAR (PENGELUARAN)</strong></td></tr>
        @forelse($disbursements as $db)
        <tr>
            <td style="padding-left: 20px;">Pengeluaran Kas Keluar</td>
            <td style="font-size: 8pt; color: #475569;">{{ strtoupper($db->source_type ?? 'Lain-lain') }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($db->amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 20px; color: #94a3b8;">Tidak ada pengeluaran kas keluar pada periode ini</td></tr>
        @endforelse
        <tr style="font-weight: bold; background: #fff1f2;">
            <td colspan="2">TOTAL KAS KELUAR</td>
            <td style="text-align: right; font-family: monospace; color: #e11d48;">Rp {{ number_format($totalOut, 0, ',', '.') }}</td>
        </tr>

        {{-- Kenaikan/Penurunan Kas Bersih --}}
        <tr style="font-weight: bold; border-top: 2px solid #1e293b;">
            <td colspan="2">KENAIKAN / (PENURUNAN) KAS BERSIH PERIODE BERJALAN</td>
            <td style="text-align: right; font-family: monospace; color: {{ $netCashFlow >= 0 ? '#059669' : '#e11d48' }};">
                Rp {{ number_format($netCashFlow, 0, ',', '.') }}
            </td>
        </tr>

        {{-- Saldo Akhir --}}
        <tr class="total-row" style="background: #e0f2fe;">
            <td colspan="2" style="padding: 10px 8px; font-size: 11pt;">SALDO KAS & SETARA KAS AKHIR PERIODE ({{ date('d/m/Y', strtotime($dateTo)) }})</td>
            <td style="text-align: right; font-family: monospace; font-size: 11pt; color: #0369a1; padding: 10px 8px;">
                Rp {{ number_format($closingCash, 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>
@endsection

@section('signatures')
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disiapkan Oleh:</div>
    <div class="signature-line">{{ auth()->user()->name ?? 'Finance Staff' }}</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Diperiksa Oleh:</div>
    <div class="signature-line">Treasury Manager</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disetujui Oleh:</div>
    <div class="signature-line">Finance Director / Owner</div>
</div>
@endsection
