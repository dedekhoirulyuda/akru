@extends('layouts.pdf')

@section('title', 'Daftar Stock Opname — AKRU')

@section('content')
    {{-- Header --}}
    <div class="company-header">
        <div class="company-name">{{ session('active_company_name', 'PT AKRU MAJU BERSAMA') }}</div>
        <div class="doc-title">DAFTAR STOCK OPNAME FISIK GUDANG</div>
        <div class="doc-subtitle">
            Periode: Semua Periode | Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Data Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th style="width: 120px;">No. Opname</th>
                <th style="width: 80px;">Tanggal</th>
                <th>Gudang Lokasi</th>
                <th style="width: 110px;">Dibuat Oleh</th>
                <th style="width: 110px;">Disetujui Oleh</th>
                <th style="width: 90px;" class="text-center">Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($opnames as $idx => $opn)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-mono" style="font-weight: 600;">{{ $opn->opname_number }}</td>
                    <td>{{ $opn->opname_date ? date('d/m/Y', strtotime($opn->opname_date)) : '-' }}</td>
                    <td>{{ $opn->warehouse->name ?? '-' }}</td>
                    <td>{{ $opn->creator->name ?? '-' }}</td>
                    <td>{{ $opn->approver->name ?? '-' }}</td>
                    <td class="text-center">
                        <span style="display:inline-block; padding: 2px 6px; border-radius: 3px; font-size: 7.5pt; font-weight: bold; background: {{ $opn->status === 'approved' ? '#dcfce7; color: #15803d;' : '#fef9c3; color: #a16207;' }}">
                            {{ strtoupper(str_replace('_', ' ', $opn->status)) }}
                        </span>
                    </td>
                    <td>{{ $opn->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 24px; color: #94a3b8;">
                        Tidak ada data stock opname fisik.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Signatures --}}
    <div class="signature-section">
        <div class="signature-box">
            <div class="title">Dibuat Oleh (Warehouse Staff),</div>
            <div class="line"></div>
            <div class="name">{{ auth()->user()->name ?? 'Petugas Gudang' }}</div>
        </div>
        <div class="signature-box">
            <div class="title">Diperiksa Oleh (Head Warehouse),</div>
            <div class="line"></div>
            <div class="name">( ............................................ )</div>
        </div>
        <div class="signature-box">
            <div class="title">Disetujui Oleh (Operations Manager),</div>
            <div class="line"></div>
            <div class="name">( ............................................ )</div>
        </div>
    </div>
@endsection
