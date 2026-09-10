@extends('layouts.pdf')

@section('title', 'Laporan Stok Gudang & Nilai Persediaan — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}</div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">LAPORAN STOK & VALUASI PERSEDIAAN GUDANG</h2>
        <div class="meta">Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 15px;">
    <thead>
        <tr>
            <th style="width: 30px; text-align: center;">No.</th>
            <th style="width: 100px;">Kode SKU</th>
            <th>Nama Barang / Produk</th>
            <th style="width: 80px; text-align: right;">Stok Fisik</th>
            <th style="width: 50px; text-align: center;">Satuan</th>
            <th style="width: 110px; text-align: right;">Harga Pokok (Rp)</th>
            <th style="width: 130px; text-align: right;">Total Nilai (Rp)</th>
            <th style="width: 75px; text-align: center;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($balances as $idx => $b)
        <tr>
            <td style="text-align: center; font-size: 8pt;">{{ $idx + 1 }}</td>
            <td style="font-family: monospace; font-weight: 600;">{{ $b->item->sku ?? '-' }}</td>
            <td>{{ $b->item->name ?? '-' }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: bold;">{{ number_format($b->quantity, 0, ',', '.') }}</td>
            <td style="text-align: center; font-size: 8pt;">{{ $b->item->unit ?? 'Pcs' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($b->unit_cost ?? $b->item->purchase_price ?? 0, 0, ',', '.') }}</td>
            <td style="text-align: right; font-family: monospace; font-weight: bold; color: #059669;">
                {{ number_format($b->total_value, 0, ',', '.') }}
            </td>
            <td style="text-align: center; font-size: 8pt;">
                @php
                    $isLow = $b->quantity <= ($b->item->min_stock ?? 5);
                    $isOut = $b->quantity <= 0;
                @endphp
                <span style="font-weight: 600; color: {{ $isOut ? '#e11d48' : ($isLow ? '#d97706' : '#059669') }};">
                    {{ $isOut ? 'HABIS' : ($isLow ? 'MENIPIS' : 'AMAN') }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data persediaan barang di gudang.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL PERSEDIAAN GUDANG:</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; padding: 8px;">{{ number_format($totalQty, 0, ',', '.') }}</td>
            <td></td>
            <td></td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #059669; padding: 8px;">
                Rp {{ number_format($totalValuation, 0, ',', '.') }}
            </td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endsection

@section('signatures')
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disiapkan Oleh:</div>
    <div class="signature-line">{{ auth()->user()->name ?? 'Warehouse Staff' }}</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Diperiksa Oleh:</div>
    <div class="signature-line">Warehouse Supervisor</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disetujui Oleh:</div>
    <div class="signature-line">Operations Director / Owner</div>
</div>
@endsection
