<?php

namespace App\Services\Export;

use Illuminate\Http\Response;
use ZipArchive;

class DataExportService
{
    /**
     * Generate downloadable genuine Microsoft Excel (.xlsx) file with professional styling
     *
     * @param string $filename Name of the output file (e.g. Laporan_Jurnal_Umum.xlsx)
     * @param array $headers List of header strings
     * @param array $rows Array of data rows (each row is an array of cell values)
     * @param array $colWidths Optional column widths
     * @param array $meta Optional metadata displayed above the table (e.g. ['Perusahaan' => 'PT Maju', 'Periode' => 'Sep 2026'])
     * @return Response
     */
    public function exportXlsx(string $filename, array $headers, array $rows, array $colWidths = [], array $meta = []): Response
    {
        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        $filePath = $this->createStyledXlsxFile($headers, $rows, $colWidths, $meta);
        $content = file_get_contents($filePath);
        @unlink($filePath);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($content),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Create styled XLSX file on disk and return temporary path
     */
    public function createStyledXlsxFile(array $headers, array $rows, array $colWidths = [], array $meta = []): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'akru_exp_') . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        // _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        // xl/workbook.xml
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets>
</workbook>');

        // xl/styles.xml
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="4">
    <font><sz val="10"/><name val="Segoe UI"/><color rgb="0F172A"/></font>
    <font><b/><sz val="10"/><name val="Segoe UI"/><color rgb="FFFFFF"/></font>
    <font><b/><sz val="13"/><name val="Segoe UI"/><color rgb="0F172A"/></font>
    <font><i/><sz val="9"/><name val="Segoe UI"/><color rgb="64748B"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="1E293B"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="E2E8F0"/></left>
      <right style="thin"><color rgb="E2E8F0"/></right>
      <top style="thin"><color rgb="E2E8F0"/></top>
      <bottom style="thin"><color rgb="E2E8F0"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="5">
    <!-- 0: Standard data cell -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0">
      <alignment vertical="center"/>
    </xf>
    <!-- 1: Header cell -->
    <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <!-- 2: Title cell -->
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1">
      <alignment vertical="center"/>
    </xf>
    <!-- 3: Subtitle / Meta cell -->
    <xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1">
      <alignment vertical="center"/>
    </xf>
    <!-- 4: Numeric / Currency Right-aligned data cell -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0">
      <alignment horizontal="right" vertical="center"/>
    </xf>
  </cellXfs>
</styleSheet>');

        // Build worksheet xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

        // Calculate automatic column widths if not explicitly provided
        if (empty($colWidths)) {
            $colWidths = [];
            foreach ($headers as $cIdx => $h) {
                $maxLen = mb_strlen((string)$h);
                foreach (array_slice($rows, 0, 50) as $r) {
                    $vals = array_values($r);
                    if (isset($vals[$cIdx])) {
                        $maxLen = max($maxLen, mb_strlen((string)$vals[$cIdx]));
                    }
                }
                $colWidths[] = min(max($maxLen + 4, 12), 48);
            }
        }

        if (!empty($colWidths)) {
            $sheetXml .= '<cols>';
            foreach ($colWidths as $i => $w) {
                $colNum = $i + 1;
                $sheetXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $w . '" customWidth="1"/>';
            }
            $sheetXml .= '</cols>' . "\n";
        }

        $sheetXml .= '<sheetData>' . "\n";

        $currentRow = 1;

        // Meta headers (Title, Company, Date)
        if (!empty($meta)) {
            if (isset($meta['title'])) {
                $sheetXml .= '<row r="' . $currentRow . '" ht="24" customHeight="1">';
                $sheetXml .= '<c r="A' . $currentRow . '" s="2" t="inlineStr"><is><t>' . htmlspecialchars((string)$meta['title'], ENT_XML1) . '</t></is></c>';
                $sheetXml .= '</row>' . "\n";
                $currentRow++;
            }
            if (isset($meta['company']) || isset($meta['period']) || isset($meta['date'])) {
                $sub = [];
                if (isset($meta['company'])) $sub[] = $meta['company'];
                if (isset($meta['period'])) $sub[] = 'Periode: ' . $meta['period'];
                if (isset($meta['date'])) $sub[] = 'Diekspor: ' . $meta['date'];
                $sheetXml .= '<row r="' . $currentRow . '" ht="18" customHeight="1">';
                $sheetXml .= '<c r="A' . $currentRow . '" s="3" t="inlineStr"><is><t>' . htmlspecialchars(implode('  |  ', $sub), ENT_XML1) . '</t></is></c>';
                $sheetXml .= '</row>' . "\n";
                $currentRow++;
            }
            // Blank separator row
            $currentRow++;
        }

        // Table Header row
        $sheetXml .= '<row r="' . $currentRow . '" ht="28" customHeight="1">';
        foreach ($headers as $cIdx => $header) {
            $colLetter = $this->colIndexToLetter($cIdx);
            $cellRef = $colLetter . $currentRow;
            $escaped = htmlspecialchars((string)$header, ENT_XML1);
            $sheetXml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
        }
        $sheetXml .= '</row>' . "\n";
        $currentRow++;

        // Table Data rows
        foreach ($rows as $row) {
            $sheetXml .= '<row r="' . $currentRow . '" ht="20" customHeight="1">';
            foreach (array_values($row) as $cIdx => $val) {
                $colLetter = $this->colIndexToLetter($cIdx);
                $cellRef = $colLetter . $currentRow;

                // Detect if value is numeric or currency
                $isNumeric = is_numeric($val) || (is_string($val) && preg_match('/^(Rp\s?)?[-+]?[0-9.,]+$/', trim($val)));
                $styleIdx = $isNumeric ? 4 : 0;

                $escaped = htmlspecialchars((string)$val, ENT_XML1);
                $sheetXml .= '<c r="' . $cellRef . '" s="' . $styleIdx . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetXml .= '</row>' . "\n";
            $currentRow++;
        }

        $sheetXml .= '</sheetData>' . "\n";
        $sheetXml .= '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return $tmp;
    }

    /**
     * Render printable corporate PDF view extending layouts.pdf
     * Automatically loads the company's PrintTemplate for dynamic styling.
     */
    public function exportPdf(string $viewName, array $data, string $filename, string $documentType = 'general'): Response
    {
        // Load print template for this document type
        $companyId = session('current_company_id');
        $printTemplate = null;
        $company = null;

        if ($companyId) {
            $company = \App\Modules\Core\Models\Company::find($companyId);
            $printTemplate = \App\Modules\Core\Models\PrintTemplate::where('company_id', $companyId)
                ->forDocument($documentType)
                ->first();
        }

        // If no template, create a virtual default
        if (!$printTemplate) {
            $printTemplate = new \App\Modules\Core\Models\PrintTemplate();
        }

        $data['printTemplate'] = $printTemplate;
        $data['company'] = $data['company'] ?? $company;

        // Choose layout based on printer mode
        if ($printTemplate->isDotMatrix()) {
            // For dot matrix views, prefix with dot-matrix layout
            $data['printTemplate'] = $printTemplate;
        }

        $html = view($viewName, $data)->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Convert 0-based column index to Excel letter (0->A, 25->Z, 26->AA)
     */
    protected function colIndexToLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex >= 0) {
            $letter = chr($colIndex % 26 + 65) . $letter;
            $colIndex = intdiv($colIndex, 26) - 1;
        }
        return $letter;
    }
}
