@extends('layouts.pdf')

@section('title', 'Laporan Laba Rugi — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}</div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">LAPORAN LABA RUGI (INCOME STATEMENT)</h2>
        <div class="meta">Periode: {{ date('d/m/Y', strtotime($dateFrom)) }} s/d {{ date('d/m/Y', strtotime($dateTo)) }}</div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 15px;">
    <thead>
        <tr>
            <th>Keterangan Akun / Komponen</th>
            <th style="width: 100px; text-align: center;">Kode Akun</th>
            <th style="width: 150px; text-align: right;">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        {{-- 1. Pendapatan --}}
        <tr style="background: #f8fafc;"><td colspan="3"><strong>1. PENDAPATAN USAHA (REVENUE)</strong></td></tr>
        @forelse($revenues as $r)
        <tr>
            <td style="padding-left: 20px;">{{ $r->name }}</td>
            <td style="text-align: center; font-family: monospace;">{{ $r->code }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($r->net_amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 20px; color: #94a3b8;">Tidak ada akun pendapatan</td></tr>
        @endforelse
        <tr style="font-weight: bold; background: #f1f5f9;">
            <td colspan="2">TOTAL PENDAPATAN</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
        </tr>

        {{-- 2. HPP --}}
        <tr style="background: #f8fafc;"><td colspan="3" style="padding-top: 10px;"><strong>2. BEBAN POKOK PENJUALAN (HPP)</strong></td></tr>
        @forelse($costOfSales as $c)
        <tr>
            <td style="padding-left: 20px;">{{ $c->name }}</td>
            <td style="text-align: center; font-family: monospace;">{{ $c->code }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($c->net_amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 20px; color: #94a3b8;">Tidak ada akun beban pokok penjualan</td></tr>
        @endforelse
        <tr style="font-weight: bold; background: #f1f5f9;">
            <td colspan="2">TOTAL BEBAN POKOK PENJUALAN</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($totalCostOfSales, 0, ',', '.') }}</td>
        </tr>
        <tr style="font-weight: bold; background: #e2e8f0;">
            <td colspan="2">LABA KOTOR (GROSS PROFIT)</td>
            <td style="text-align: right; font-family: monospace; color: #1d4ed8;">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
        </tr>

        {{-- 3. Beban Operasional --}}
        <tr style="background: #f8fafc;"><td colspan="3" style="padding-top: 10px;"><strong>3. BEBAN OPERASIONAL & UMUM</strong></td></tr>
        @forelse($operatingExpenses as $o)
        <tr>
            <td style="padding-left: 20px;">{{ $o->name }}</td>
            <td style="text-align: center; font-family: monospace;">{{ $o->code }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($o->net_amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding-left: 20px; color: #94a3b8;">Tidak ada akun beban operasional</td></tr>
        @endforelse
        <tr style="font-weight: bold; background: #f1f5f9;">
            <td colspan="2">TOTAL BEBAN OPERASIONAL</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($totalOperatingExpense, 0, ',', '.') }}</td>
        </tr>
        <tr style="font-weight: bold; background: #e2e8f0;">
            <td colspan="2">LABA OPERASIONAL</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($operatingProfit, 0, ',', '.') }}</td>
        </tr>

        {{-- 4. Laba Bersih --}}
        <tr style="font-weight: bold; border-top: 2px solid #1e293b;">
            <td colspan="2">LABA SEBELUM PAJAK PPh BADAN</td>
            <td style="text-align: right; font-family: monospace;">Rp {{ number_format($netProfitBeforeTax, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2" style="font-style: italic;">Estimasi Pajak Penghasilan Badan (PPh 22%)</td>
            <td style="text-align: right; font-family: monospace; color: #e11d48;">Rp {{ number_format($estimatedTax, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td colspan="2" style="padding: 10px 8px; font-size: 11pt;">LABA BERSIH PERIODE BERJALAN</td>
            <td style="text-align: right; font-family: monospace; font-size: 11pt; color: #059669; padding: 10px 8px;">
                Rp {{ number_format($netProfitAfterTax, 0, ',', '.') }}
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
    <div class="signature-line">Direktur Utama / Owner</div>
</div>
@endsection
