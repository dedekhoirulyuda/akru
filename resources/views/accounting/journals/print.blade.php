@extends('layouts.pdf')

@section('title', 'Laporan Jurnal Umum — ' . ($currentCompany->name ?? 'AKRU'))

@section('report-header')
<div style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="company-name">{{ $currentCompany->name ?? session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="meta">
            NPWP: {{ $currentCompany->npwp ?? '-' }} | {{ $currentCompany->address ?? 'Indonesia' }}
        </div>
    </div>
    <div style="text-align: right;">
        <h2 style="font-size: 14pt; font-weight: bold; color: #1e293b; margin-bottom: 4px;">LAPORAN JURNAL UMUM</h2>
        <div class="meta">Periode: {{ date('d/m/Y', strtotime($dateFrom)) }} s/d {{ date('d/m/Y', strtotime($dateTo)) }}</div>
        <div class="meta">Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>
@endsection

@section('content')
<table style="width: 100%; margin-top: 10px;">
    <thead>
        <tr>
            <th style="width: 30px; text-align: center;">No.</th>
            <th style="width: 130px;">No. Jurnal</th>
            <th style="width: 75px; text-align: center;">Tanggal</th>
            <th style="width: 100px;">Sumber</th>
            <th>Uraian / Keterangan</th>
            <th style="width: 110px; text-align: right;">Debit (Rp)</th>
            <th style="width: 110px; text-align: right;">Kredit (Rp)</th>
            <th style="width: 70px; text-align: center;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($journals as $idx => $j)
        <tr>
            <td style="text-align: center; font-size: 8pt;">{{ $idx + 1 }}</td>
            <td style="font-family: monospace; font-weight: 600;">{{ $j->journal_number }}</td>
            <td style="text-align: center;">{{ $j->journal_date ? date('d/m/Y', strtotime($j->journal_date)) : '-' }}</td>
            <td style="font-size: 8pt; color: #475569;">{{ $j->source_type ?? '-' }}</td>
            <td>{{ $j->description ?? '-' }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($j->total_debit, 0, ',', '.') }}</td>
            <td style="text-align: right; font-family: monospace;">{{ number_format($j->total_credit, 0, ',', '.') }}</td>
            <td style="text-align: center; font-size: 8pt;">
                <span style="font-weight: 600; color: {{ $j->status === 'posted' ? '#059669' : '#e11d48' }};">
                    {{ strtoupper($j->status) }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">
                Tidak ada entri jurnal ditemukan pada rentang periode yang dipilih.
            </td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="5" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL MUTASI:</td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; padding: 8px;">
                Rp {{ number_format($totalDebit, 0, ',', '.') }}
            </td>
            <td style="text-align: right; font-weight: bold; font-family: monospace; padding: 8px;">
                Rp {{ number_format($totalCredit, 0, ',', '.') }}
            </td>
            <td></td>
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
