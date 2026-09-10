@extends('layouts.pdf')

@section('title', 'Neraca Keuangan — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}</div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">NERACA POSISI KEUANGAN (BALANCE SHEET)</h2>
        <div class="meta">Per Tanggal: {{ date('d/m/Y', strtotime($asOfDate)) }}</div>
        <div class="meta">Status: <strong style="color: {{ $isBalanced ? '#059669' : '#e11d48' }};">{{ $isBalanced ? 'BALANCE (SEIMBANG)' : 'TIDAK SEIMBANG' }}</strong></div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 15px;">
    <thead>
        <tr>
            <th>Kategori & Nama Akun Perkiraan</th>
            <th style="width: 100px; text-align: center;">Kode Akun</th>
            <th style="width: 150px; text-align: right;">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        {{-- AKTIVA --}}
        <tr style="background: #e2e8f0; font-weight: bold;"><td colspan="3">ASET (AKTIVA)</td></tr>
        
        <tr style="background: #f8fafc;"><td colspan="3" style="padding-left: 10px;"><strong>1. Aset Lancar</strong></td></tr>
        @forelse($currentAssets as $a)
        <tr>
            <td style="padding-left: 24px;">{{ $a->name }}</td>
            <td style="text-align: center; font-family: monospace;">{{ $a->code }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($a->net_amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 24px; color: #94a3b8;">Tidak ada akun aset lancar</td></tr>
        @endforelse
        <tr style="font-weight: bold; background: #f1f5f9;">
            <td colspan="2" style="padding-left: 10px;">TOTAL ASET LANCAR</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($totalCurrentAssets, 0, ',', '.') }}</td>
        </tr>

        <tr style="background: #f8fafc;"><td colspan="3" style="padding-left: 10px;"><strong>2. Aset Tetap & Tidak Lancar</strong></td></tr>
        @forelse($fixedAssets as $f)
        <tr>
            <td style="padding-left: 24px;">{{ $f->name }}</td>
            <td style="text-align: center; font-family: monospace;">{{ $f->code }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($f->net_amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 24px; color: #94a3b8;">Tidak ada akun aset tetap</td></tr>
        @endforelse
        <tr style="font-weight: bold; background: #f1f5f9;">
            <td colspan="2" style="padding-left: 10px;">TOTAL ASET TETAP</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($totalFixedAssets, 0, ',', '.') }}</td>
        </tr>

        <tr class="total-row" style="background: #e0f2fe;">
            <td colspan="2" style="padding: 8px;">TOTAL KESELURUHAN ASET (AKTIVA)</td>
            <td style="text-align: right; font-family: monospace; font-size: 10pt; color: #0369a1; padding: 8px;">
                Rp {{ number_format($totalAssets, 0, ',', '.') }}
            </td>
        </tr>

        {{-- PASIVA --}}
        <tr style="background: #e2e8f0; font-weight: bold;"><td colspan="3" style="padding-top: 15px;">KEWAJIBAN & EKUITAS (PASIVA)</td></tr>

        <tr style="background: #f8fafc;"><td colspan="3" style="padding-left: 10px;"><strong>1. Kewajiban Lancar (Hutang Jangka Pendek)</strong></td></tr>
        @forelse($currentLiabilities as $l)
        <tr>
            <td style="padding-left: 24px;">{{ $l->name }}</td>
            <td style="text-align: center; font-family: monospace;">{{ $l->code }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($l->net_amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 24px; color: #94a3b8;">Tidak ada kewajiban lancar</td></tr>
        @endforelse
        <tr style="font-weight: bold; background: #f1f5f9;">
            <td colspan="2" style="padding-left: 10px;">TOTAL KEWAJIBAN LANCAR</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($totalCurrentLiabilities, 0, ',', '.') }}</td>
        </tr>

        <tr style="background: #f8fafc;"><td colspan="3" style="padding-left: 10px;"><strong>2. Ekuitas (Modal Pemilik)</strong></td></tr>
        @forelse($equities as $e)
        <tr>
            <td style="padding-left: 24px;">{{ $e->name }}</td>
            <td style="text-align: center; font-family: monospace;">{{ $e->code }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($e->net_amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 24px; color: #94a3b8;">Tidak ada akun ekuitas</td></tr>
        @endforelse
        <tr>
            <td style="padding-left: 24px; font-style: italic;">Laba Ditahan / Akumulasi Laba Tahun Berjalan</td>
            <td style="text-align: center; font-family: monospace;">-</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($netProfitAccumulated, 0, ',', '.') }}</td>
        </tr>
        <tr style="font-weight: bold; background: #f1f5f9;">
            <td colspan="2" style="padding-left: 10px;">TOTAL EKUITAS</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($totalEquity, 0, ',', '.') }}</td>
        </tr>

        <tr class="total-row" style="background: #e0f2fe;">
            <td colspan="2" style="padding: 8px;">TOTAL KEWAJIBAN & EKUITAS (PASIVA)</td>
            <td style="text-align: right; font-family: monospace; font-size: 10pt; color: #0369a1; padding: 8px;">
                Rp {{ number_format($totalLiabilitiesAndEquity, 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
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
    <div class="signature-line">Direktur Keuangan / Owner</div>
</div>
@endsection
