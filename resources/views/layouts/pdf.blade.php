<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'AKRU Report')</title>
    @php
        $tpl = $printTemplate ?? null;
        $fontFamily = $tpl->font_family ?? 'Inter';
        $fontSize = ($tpl->font_size ?? 10) . 'pt';
        $colorPrimary = $tpl->color_primary ?? '#1e293b';
        $colorAccent = $tpl->color_accent ?? '#2563eb';
        $colorText = $tpl->color_text ?? '#1e293b';
        $marginT = ($tpl->margin_top ?? 12) . 'mm';
        $marginB = ($tpl->margin_bottom ?? 15) . 'mm';
        $marginL = ($tpl->margin_left ?? 12) . 'mm';
        $marginR = ($tpl->margin_right ?? 12) . 'mm';
        $tableHeaderBg = $tpl->table_header_bg ?? '#f1f5f9';
        $tableHeaderText = $tpl->table_header_text ?? '#1e293b';
        $tableBorderColor = $tpl->table_border_color ?? '#e2e8f0';
        $showGridlines = $tpl->show_gridlines ?? true;
        $paperSize = $tpl ? $tpl->getCssPageSize() : 'A4';
    @endphp
    <style>
        /* PDF-specific styles for print-ready reports */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: '{{ $fontFamily }}', Arial, sans-serif; font-size: {{ $fontSize }}; color: {{ $colorText }}; line-height: 1.4; }

        .header { border-bottom: 2px solid {{ $colorPrimary }}; padding-bottom: 12px; margin-bottom: 16px; }
        .header .company-name { font-size: {{ ($tpl->font_size ?? 10) + 4 }}pt; font-weight: 700; color: {{ $colorPrimary }}; }
        .header .report-title { font-size: {{ ($tpl->font_size ?? 10) + 2 }}pt; font-weight: 600; margin-top: 4px; }
        .header .meta { font-size: 8pt; color: #64748b; margin-top: 4px; }

        .company-header { border-bottom: 2px solid {{ $colorPrimary }}; padding-bottom: 12px; margin-bottom: 16px; }
        .company-header .company-name { font-size: {{ ($tpl->font_size ?? 10) + 4 }}pt; font-weight: 700; color: {{ $colorPrimary }}; }
        .company-header .doc-title { font-size: {{ ($tpl->font_size ?? 10) + 2 }}pt; font-weight: 700; color: {{ $colorPrimary }}; margin-top: 4px; }
        .company-header .doc-subtitle { font-size: 8pt; color: #64748b; margin-top: 4px; }

        table, .data-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th { background: {{ $tableHeaderBg }}; color: {{ $tableHeaderText }}; text-align: left; padding: 6px 8px; font-size: {{ ($tpl->font_size ?? 10) - 1 }}pt; font-weight: 600; border-bottom: 1px solid {{ $tableBorderColor }}; }
        td { padding: 5px 8px; font-size: {{ ($tpl->font_size ?? 10) - 1 }}pt; {{ $showGridlines ? 'border-bottom: 1px solid ' . $tableBorderColor . ';' : '' }} }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .font-mono { font-family: 'Courier New', monospace; }
        .total-row { background: #f8fafc; font-weight: 700; border-top: 2px solid {{ $colorPrimary }}; }

        .footer { position: fixed; bottom: 0; width: 100%; font-size: 7pt; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px; }
        .signature-block, .signature-section { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 200px; text-align: center; }
        .signature-box .title { font-size: 8pt; color: #64748b; }
        .signature-box .line { border-top: 1px solid {{ $colorPrimary }}; margin-top: 60px; padding-top: 4px; }
        .signature-box .name { font-size: 9pt; }
        .signature-line { border-top: 1px solid {{ $colorPrimary }}; margin-top: 60px; padding-top: 4px; font-size: 9pt; }

        @page { size: {{ $paperSize }}; margin: {{ $marginT }} {{ $marginR }} {{ $marginB }} {{ $marginL }}; }
        @media print { 
            .no-print { display: none !important; } 
            body { padding-top: 0 !important; }
        }

        .no-print-bar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: #0f172a;
            color: #f8fafc;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: system-ui, -apple-system, sans-serif;
            margin-bottom: 20px;
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
            text-decoration: none;
        }
        .btn-print { background: #2563eb; color: white; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-close { background: #334155; color: #cbd5e1; }
        .btn-close:hover { background: #475569; color: white; }

        {!! $tpl->custom_css ?? '' !!}
    </style>
    @if(in_array($fontFamily, ['Inter', 'Roboto']))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $fontFamily }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endif
</head>
<body style="padding: 10px 20px;">
    {{-- Interactive Floating Print Bar (Hidden in Print/PDF output) --}}
    <div class="no-print no-print-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="font-weight: 700; font-size: 14px; letter-spacing: 0.5px; color: #38bdf8;">AKRU DOCUMENT EXPORT</div>
            <span style="font-size: 12px; color: #94a3b8;">• Format Cetak Standar Akuntansi & Perpajakan</span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <button onclick="window.print()" type="button" class="no-print-btn btn-print">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Simpan ke PDF (Ctrl+P)</span>
            </button>
            <button onclick="window.close(); history.back();" type="button" class="no-print-btn btn-close">
                <span>Tutup</span>
            </button>
        </div>
    </div>

    {{-- Logo and Header (from template settings) --}}
    @if($tpl)
        @php $headerItems = collect($tpl->getHeaderLayoutOrDefault())->where('visible', true)->sortBy('order'); @endphp
        <div class="header">
            @foreach($headerItems as $item)
                @if($item['type'] === 'logo' && $tpl->show_logo && !empty($company->logo_path ?? session('company_logo_path')))
                    <div style="text-align: {{ $tpl->logo_position }}; margin-bottom: 4px;">
                        <img src="{{ asset('storage/' . ($company->logo_path ?? session('company_logo_path'))) }}" style="height: {{ $tpl->logo_size }}px;" alt="Logo">
                    </div>
                @elseif($item['type'] === 'company_name' && $tpl->show_company_name)
                    <div class="company-name">@yield('company-name', session('active_company_name', 'PT AKRU MAJU BERSAMA'))</div>
                @elseif($item['type'] === 'address' && $tpl->show_company_address)
                    <div class="meta">{{ $company->address ?? session('company_address', '') }}</div>
                @elseif($item['type'] === 'npwp' && $tpl->show_npwp)
                    <div class="meta">NPWP: {{ $company->tax_id ?? session('company_npwp', '') }}</div>
                @elseif($item['type'] === 'phone_email' && $tpl->show_phone_email)
                    <div class="meta">Telp: {{ $company->phone ?? '' }} | Email: {{ $company->email ?? '' }}</div>
                @endif
            @endforeach
            @yield('report-header')
        </div>
    @else
        {{-- Fallback: original header --}}
        <div class="header">
            @yield('report-header')
        </div>
    @endif

    {{-- Report Body --}}
    @yield('content')

    {{-- Signature Block --}}
    @if($tpl && !empty($tpl->getSignaturesOrDefault()))
    <div class="signature-block">
        @foreach($tpl->getSignaturesOrDefault() as $sig)
        <div class="signature-box">
            <div class="title">{{ $sig['title'] ?? 'Jabatan' }},</div>
            <div class="signature-line">{{ $sig['name'] ?: '( ........................ )' }}</div>
        </div>
        @endforeach
    </div>
    @else
        @hasSection('signatures')
        <div class="signature-block">
            @yield('signatures')
        </div>
        @endif
    @endif

    {{-- Footer --}}
    <div class="footer">
        <span>{{ $tpl->footer_text ?? 'Dicetak dari AKRU' }} pada {{ now()->timezone(config('akru.defaults.timezone', 'Asia/Jakarta'))->format('d/m/Y H:i') }}</span>
        @if($tpl->show_page_number ?? true)
        <span style="float: right;">Halaman <span class="page-number"></span></span>
        @endif
    </div>
</body>
</html>
