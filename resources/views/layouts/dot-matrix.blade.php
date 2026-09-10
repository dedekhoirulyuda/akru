<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'AKRU Dot Matrix Report')</title>
    @php
        $tpl = $printTemplate ?? null;
        $paperSize = $tpl ? $tpl->getCssPageSize() : '241.3mm 279.4mm';
        $marginT = ($tpl->margin_top ?? 6) . 'mm';
        $marginB = ($tpl->margin_bottom ?? 6) . 'mm';
        $marginL = ($tpl->margin_left ?? 8) . 'mm';
        $marginR = ($tpl->margin_right ?? 8) . 'mm';
        $fontSize = ($tpl && $tpl->dm_condensed) ? '8pt' : '10pt';
        $cpl = $tpl->dm_char_per_line ?? 80;
        $fontFamily = $tpl->font_family ?? 'Courier New';
    @endphp
    <style>
        @page {
            size: {{ $paperSize }};
            margin: {{ $marginT }} {{ $marginR }} {{ $marginB }} {{ $marginL }};
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: '{{ $fontFamily }}', 'Courier New', monospace;
            font-size: {{ $fontSize }};
            color: #000;
            background: #fffff5;
            line-height: 1.2;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .dm-page {
            padding: 8px 12px;
        }

        .dm-header {
            text-align: center;
            margin-bottom: 4px;
        }

        .dm-line {
            display: block;
        }

        .dm-sep {
            display: block;
        }

        .dm-table {
            width: 100%;
            border-collapse: collapse;
            font-family: '{{ $fontFamily }}', 'Courier New', monospace;
            font-size: {{ $fontSize }};
        }

        .dm-table th,
        .dm-table td {
            padding: 1px 4px;
            white-space: nowrap;
            vertical-align: top;
        }

        .dm-table th {
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #000;
            border-top: 1px solid #000;
        }

        .dm-table td {
            border-bottom: none;
        }

        .dm-table .text-right { text-align: right; }
        .dm-table .text-center { text-align: center; }

        .dm-table .total-row td {
            border-top: 1px double #000;
            font-weight: bold;
        }

        .dm-signatures {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .dm-sig-box {
            text-align: center;
            min-width: 100px;
        }

        .dm-sig-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            padding-top: 2px;
        }

        .dm-footer {
            margin-top: 8px;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }

        /* Print bar (hidden in print) */
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0 !important; }
        }

        .no-print-bar {
            position: sticky;
            top: 0;
            z-index: 9999;
            background: #92400e;
            color: #fef3c7;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: system-ui, sans-serif;
            margin-bottom: 12px;
        }

        .no-print-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-print { background: #d97706; color: white; }
        .btn-print:hover { background: #b45309; }
        .btn-close { background: #78350f; color: #fcd34d; }
        .btn-close:hover { background: #451a03; color: white; }
    </style>
</head>
<body>
    {{-- Print Bar --}}
    <div class="no-print no-print-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="font-weight: 700; font-size: 14px; letter-spacing: 0.5px; color: #fbbf24;">🖨️ DOT MATRIX OUTPUT</div>
            <span style="font-size: 12px; color: #fde68a;">• Continuous Form / Kertas Bersambung</span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <button onclick="window.print()" type="button" class="no-print-btn btn-print">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"/></svg>
                Cetak ke Dot Matrix (Ctrl+P)
            </button>
            <button onclick="window.close(); history.back();" type="button" class="no-print-btn btn-close">Tutup</button>
        </div>
    </div>

    <div class="dm-page">
        @yield('content')
    </div>

    {{-- Signatures --}}
    @if($tpl && !empty($tpl->getSignaturesOrDefault()))
    <div class="dm-signatures" style="padding: 0 12px;">
        @foreach($tpl->getSignaturesOrDefault() as $sig)
        <div class="dm-sig-box">
            <div>{{ $sig['title'] ?? 'Jabatan' }},</div>
            <div class="dm-sig-line">{{ $sig['name'] ?: '( ........................ )' }}</div>
        </div>
        @endforeach
    </div>
    @else
        @yield('signatures')
    @endif

    {{-- Footer --}}
    <div class="dm-footer">
        {{ $tpl->footer_text ?? 'Dicetak dari AKRU' }} {{ ($tpl->show_page_number ?? true) ? '| Halaman 1' : '' }}
    </div>
</body>
</html>
