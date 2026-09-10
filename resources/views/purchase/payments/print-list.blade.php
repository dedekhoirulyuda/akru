@extends('layouts.pdf')

@section('title', 'Register Pembayaran Hutang — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}</div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">REGISTER PEMBAYARAN HUTANG PEMASOK</h2>
        <div class="meta">Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 15px;">
    <thead>
        <tr>
            <th style="width: 30px; text-align: center;">No.</th>
            <th style="width: 140px;">No. Pembayaran</th>
            <th style="width: 80px; text-align: center;">Tanggal</th>
            <th>Pemasok / Vendor</th>
            <th style="width: 130px;">Rekening Keluar</th>
            <th style="width: 85px; text-align: center;">Metode</th>
            <th style="width: 130px; text-align: right;">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($payments as $idx => $p)
        <tr>
            <td style="text-align: center; font-size: 8pt;">{{ $idx + 1 }}</td>
            <td style="font-family: monospace; font-weight: 600;">{{ $p->payment_number }}</td>
            <td style="text-align: center;">{{ $p->payment_date ? $p->payment_date->format('d/m/Y') : '-' }}</td>
            <td>{{ $p->contact->name ?? '-' }}</td>
            <td>{{ $p->bankAccount->bank_name ?? '-' }}</td>
            <td style="text-align: center; font-size: 8pt;">{{ strtoupper($p->payment_method) }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: bold; color: #e11d48;">
                {{ number_format($p->total_amount, 0, ',', '.') }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data pembayaran hutang pemasok.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="6" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL PEMBAYARAN:</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #e11d48; padding: 8px;">
                Rp {{ number_format($totalPaid, 0, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>
@endsection

@section('signatures')
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disiapkan Oleh:</div>
    <div class="signature-line">{{ auth()->user()->name ?? 'AP Staff' }}</div>
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
