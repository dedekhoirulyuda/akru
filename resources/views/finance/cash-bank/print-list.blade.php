@extends('layouts.pdf')

@section('title', 'Register Mutasi Kas & Bank — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}</div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">REGISTER TRANSAKSI KAS & BANK (BKM / BKK)</h2>
        <div class="meta">Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 15px;">
    <thead>
        <tr>
            <th style="width: 30px; text-align: center;">No.</th>
            <th style="width: 130px;">No. Bukti</th>
            <th style="width: 75px; text-align: center;">Tanggal</th>
            <th style="width: 80px; text-align: center;">Tipe</th>
            <th style="width: 110px;">Kas / Bank</th>
            <th>Akun Lawan / Uraian</th>
            <th style="width: 120px; text-align: right;">Masuk (Rp)</th>
            <th style="width: 120px; text-align: right;">Keluar (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $idx => $t)
        @php $isIn = in_array($t->type, ['in', 'cash_in']); @endphp
        <tr>
            <td style="text-align: center; font-size: 8pt;">{{ $idx + 1 }}</td>
            <td style="font-family: monospace; font-weight: 600;">{{ $t->transaction_number }}</td>
            <td style="text-align: center;">{{ $t->transaction_date ? date('d/m/Y', strtotime($t->transaction_date)) : '-' }}</td>
            <td style="text-align: center; font-size: 8pt; font-weight: bold; color: {{ $isIn ? '#059669' : '#e11d48' }};">
                {{ $isIn ? 'BKM' : 'BKK' }}
            </td>
            <td>{{ $t->bank_name }}</td>
            <td>
                <div>{{ $t->counterparty ?: '-' }}</div>
                <div style="font-size: 8pt; color: #64748b;">{{ $t->account_code }} - {{ $t->account_name }} ({{ $t->notes ?? '-' }})</div>
            </td>
            <td style="text-align: right; font-family: monospace; color: #059669;">
                {{ $isIn ? number_format($t->total_amount, 0, ',', '.') : '-' }}
            </td>
            <td style="text-align: right; font-family: monospace; color: #e11d48;">
                {{ !$isIn ? number_format($t->total_amount, 0, ',', '.') : '-' }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data mutasi kas & bank.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="6" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL REKAPITULASI:</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #059669; padding: 8px;">
                Rp {{ number_format($totalIn, 0, ',', '.') }}
            </td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; color: #e11d48; padding: 8px;">
                Rp {{ number_format($totalOut, 0, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>
@endsection

@section('signatures')
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disiapkan Oleh:</div>
    <div class="signature-line">{{ auth()->user()->name ?? 'Kasir / Staff Kas' }}</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Diperiksa Oleh:</div>
    <div class="signature-line">Treasury Supervisor</div>
</div>
<div class="signature-box">
    <div style="font-size: 8pt; color: #64748b;">Disetujui Oleh:</div>
    <div class="signature-line">Finance Director / Owner</div>
</div>
@endsection
