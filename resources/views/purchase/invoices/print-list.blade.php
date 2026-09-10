@extends('layouts.pdf')

@section('title', 'Register Faktur Pembelian — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}</div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">REGISTER FAKTUR PEMBELIAN (BILLS)</h2>
        <div class="meta">Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 15px;">
    <thead>
        <tr>
            <th style="width: 30px; text-align: center;">No.</th>
            <th style="width: 130px;">No. Tagihan</th>
            <th style="width: 75px; text-align: center;">Tanggal</th>
            <th style="width: 75px; text-align: center;">Jatuh Tempo</th>
            <th>Pemasok / Vendor</th>
            <th style="width: 110px; text-align: right;">Total (Rp)</th>
            <th style="width: 100px; text-align: right;">Terbayar (Rp)</th>
            <th style="width: 100px; text-align: right;">Sisa (Rp)</th>
            <th style="width: 75px; text-align: center;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $idx => $inv)
        <tr>
            <td style="text-align: center; font-size: 8pt;">{{ $idx + 1 }}</td>
            <td style="font-family: monospace; font-weight: 600;">{{ $inv->invoice_number }}</td>
            <td style="text-align: center;">{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '-' }}</td>
            <td style="text-align: center;">{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}</td>
            <td>{{ $inv->contact->name ?? '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($inv->total_amount, 0, ',', '.') }}</td>
            <td style="text-align: right; font-family: monospace; color: #059669;">{{ number_format($inv->paid_amount, 0, ',', '.') }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: bold; color: {{ $inv->remaining_amount > 0 ? '#e11d48' : '#64748b' }};">
                {{ number_format($inv->remaining_amount, 0, ',', '.') }}
            </td>
            <td style="text-align: center; font-size: 8pt;">
                <span style="font-weight: 600; color: {{ $inv->remaining_amount <= 0 ? '#059669' : ($inv->paid_amount > 0 ? '#d97706' : '#e11d48') }};">
                    {{ $inv->remaining_amount <= 0 ? 'LUNAS' : ($inv->paid_amount > 0 ? 'SEBAGIAN' : 'BELUM LUNAS') }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data tagihan pembelian.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="5" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL REKAPITULASI:</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; padding: 8px;">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #059669; padding: 8px;">Rp {{ number_format($totalPaid, 0, ',', '.') }}</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #e11d48; padding: 8px;">Rp {{ number_format($totalRemaining, 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endsection

@section('signatures')
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disiapkan Oleh:</div>
    <div class="signature-line">{{ auth()->user()->name ?? 'Purchasing Staff' }}</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Diperiksa Oleh:</div>
    <div class="signature-line">AP Supervisor</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disetujui Oleh:</div>
    <div class="signature-line">Finance Director / Owner</div>
</div>
@endsection
