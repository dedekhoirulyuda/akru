@extends('layouts.pdf')

@section('title', 'FAKTUR PENJUALAN - ' . $invoice->invoice_number)

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? 'PT AKRU MAJU BERSAMA' }}</div>
        <div class="meta">
            {{ $currentCompany->address ?? 'Jl. Jenderal Sudirman Kav. 52-53' }}<br>
            NPWP: {{ $currentCompany->npwp ?? '01.234.567.8-012.000' }} | Telp: {{ $currentCompany->phone ?? '021-5152535' }}
        </div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 16pt; font-weight: bold; color: #1d4ed8; margin-bottom: 4px;">FAKTUR PENJUALAN</h2>
        <div style="font-family: monospace; font-size: 10pt; font-weight: bold;">{{ $invoice->invoice_number }}</div>
        <div class="meta">Tanggal: {{ $invoice->invoice_date->format('d/m/Y') }}</div>
        <div class="meta">Jatuh Tempo: {{ $invoice->due_date->format('d/m/Y') }}</div>
    </div>
</div>

<div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #cbd5e1; display: flex; justify-content: space-between;">
    <div>
        <div style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Ditagihkan Kepada:</div>
        <div style="font-size: 11pt; font-weight: bold; margin-top: 2px;">{{ $invoice->contact->name }}</div>
        <div class="meta">
            {{ $invoice->contact->identity_number ? 'NPWP/NIK: ' . $invoice->contact->identity_number : '' }}<br>
            {{ $invoice->contact->address ?? '-' }}
        </div>
    </div>
    <div style="text-align: right;">
        <div style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Status Pembayaran:</div>
        <div style="font-size: 11pt; font-weight: bold; color: {{ $invoice->remaining_amount > 0 ? '#e11d48' : '#059669' }}; margin-top: 2px;">
            {{ $invoice->remaining_amount <= 0 ? 'LUNAS' : 'BELUM LUNAS' }}
        </div>
    </div>
</div>
@endsection

@section('content')
<table style="margin-top: 15px;">
    <thead>
        <tr>
            <th style="width: 35px; text-align: center;">No.</th>
            <th>Deskripsi Barang / Jasa</th>
            <th style="width: 60px; text-align: center;">Qty</th>
            <th style="width: 110px; text-align: right;">Harga Satuan</th>
            <th style="width: 60px; text-align: center;">Disc</th>
            <th style="width: 120px; text-align: right;">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->lines as $idx => $line)
        <tr>
            <td style="text-align: center; font-size: 8pt;">{{ $idx + 1 }}</td>
            <td>
                <strong>{{ $line->description }}</strong>
                @if($line->item)
                    <div style="font-size: 7.5pt; color: #64748b;">SKU: {{ $line->item->sku }}</div>
                @endif
            </td>
            <td style="text-align: center; font-family: monospace;">{{ number_format($line->quantity, 0) }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($line->unit_price, 0, ',', '.') }}</td>
            <td style="text-align: center; font-size: 8pt;">{{ $line->discount_percent > 0 ? $line->discount_percent . '%' : '-' }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: 600;">{{ number_format($line->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="text-align: right; font-weight: 600; border-top: 1px solid #cbd5e1;">Subtotal DPP:</td>
            <td style="text-align: right; font-family: monospace; font-weight: 600; border-top: 1px solid #cbd5e1;">
                Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right; font-weight: 600;">PPN ({{ $invoice->taxCode->rate ?? 0 }}%):</td>
            <td style="text-align: right; font-family: monospace; font-weight: 600;">
                Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}
            </td>
        </tr>
        <tr class="total-row">
            <td colspan="5" style="text-align: right; font-size: 11pt; font-weight: bold;">TOTAL TAGIHAN:</td>
            <td style="text-align: right; font-family: monospace; font-size: 11pt; font-weight: bold; color: #1d4ed8;">
                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>

@if($invoice->notes)
<div style="margin-top: 15px; padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 8.5pt;">
    <strong>Catatan:</strong> {{ $invoice->notes }}
</div>
@endif
@endsection

@section('signatures')
<div class="signature-box">
    <div>Penerima / Pembeli,</div>
    <div class="signature-line">( ............................................ )</div>
</div>
<div class="signature-box">
    <div>Hormat kami,</div>
    <div class="signature-line">{{ $currentCompany->name ?? 'PT AKRU MAJU BERSAMA' }}</div>
</div>
@endsection
