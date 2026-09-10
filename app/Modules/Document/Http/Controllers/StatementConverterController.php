<?php

namespace App\Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\BankAccount;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatementConverterController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $bankAccounts = BankAccount::where('company_id', $companyId)->where('is_active', true)->get();

        return view('document.converter.index', compact('bankAccounts'));
    }

    public function convert(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'bank_type' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf,txt,csv|max:20480',
            'raw_text' => 'nullable|string',
            'parsed_json' => 'nullable|string',
        ]);

        $bankType = $request->input('bank_type', 'auto');
        $bankAccountId = $request->input('bank_account_id');
        $selectedBank = $bankAccountId ? BankAccount::find($bankAccountId) : null;

        $parsedRows = [];
        $rawText = '';

        // 1. If client-side PDF.js already extracted and structured the rows into JSON
        if ($request->filled('parsed_json')) {
            $decoded = json_decode($request->input('parsed_json'), true);
            if (is_array($decoded) && count($decoded) > 0) {
                $parsedRows = $decoded;
            }
        }

        // 2. If PDF file was uploaded directly to server
        if (empty($parsedRows) && $request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            $extension = strtolower($file->getClientOriginalExtension());

            if ($extension === 'pdf') {
                $rawText = $this->extractTextFromPdf($file->getRealPath());
            } else {
                $rawText = file_get_contents($file->getRealPath());
            }
        }

        // 3. If raw text was pasted
        if (empty($parsedRows) && empty($rawText) && $request->filled('raw_text')) {
            $rawText = $request->input('raw_text');
        }

        // 4. Parse the raw text if parsedRows is not yet populated
        if (empty($parsedRows) && !empty($rawText)) {
            $parsedRows = $this->parseBankStatementText($rawText, $bankType);
        }

        if (empty($parsedRows)) {
            return back()->withInput()->with('error', 'Tidak dapat mendeteksi baris transaksi rekening koran. Pastikan dokumen PDF berisi teks mutasi rekening yang valid atau gunakan opsi tempel teks.');
        }

        // Calculate statistics
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($parsedRows as &$row) {
            $debit = (float) ($row['debit'] ?? 0);
            $credit = (float) ($row['credit'] ?? 0);
            $row['debit'] = $debit;
            $row['credit'] = $credit;
            $totalDebit += $debit;
            $totalCredit += $credit;
        }
        unset($row);

        $netMovement = $totalCredit - $totalDebit;

        return back()->withInput()->with([
            'parsedRows' => $parsedRows,
            'summary' => [
                'total_transactions' => count($parsedRows),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'net_movement' => $netMovement,
                'bank_type' => $bankType,
                'bank_name' => $selectedBank ? ($selectedBank->name . ' — ' . $selectedBank->account_number) : 'Rekening Koran Umum',
            ],
            'success' => 'Berhasil mengekstrak dan memvalidasi ' . count($parsedRows) . ' baris mutasi rekening koran.',
        ]);
    }

    /**
     * Parse raw text of bank statement with multi-bank layout intelligence
     */
    public function parseBankStatementText(string $text, string $bankType = 'auto'): array
    {
        // Auto-detect bank if auto
        if ($bankType === 'auto') {
            $upper = strtoupper($text);
            if (str_contains($upper, 'BCA') || str_contains($upper, 'BANK CENTRAL ASIA') || (str_contains($upper, 'CR') && str_contains($upper, 'DB') && str_contains($upper, 'SALDO'))) {
                $bankType = 'bca';
            } elseif (str_contains($upper, 'MANDIRI') || str_contains($upper, 'BANK MANDIRI')) {
                $bankType = 'mandiri';
            } elseif (str_contains($upper, 'BNI') || str_contains($upper, 'BANK NEGARA INDONESIA')) {
                $bankType = 'bni';
            } elseif (str_contains($upper, 'BRI') || str_contains($upper, 'BANK RAKYAT INDONESIA')) {
                $bankType = 'bri';
            } elseif (str_contains($upper, 'BSI') || str_contains($upper, 'BANK SYARIAH INDONESIA')) {
                $bankType = 'bsi';
            } elseif (str_contains($upper, 'CIMB') || str_contains($upper, 'NIAGA')) {
                $bankType = 'cimb';
            } else {
                $bankType = 'universal';
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $rows = [];
        $currentDate = '';
        $currentDesc = [];
        $currentDebit = 0;
        $currentCredit = 0;
        $currentBalance = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Ignore header/footer noise
            if ($this->isNoiseLine($line)) continue;

            // Check if line starts with a date (e.g. DD/MM, DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD, DD/MM/YY)
            if (preg_match('/^(\d{1,2}[\/\-\.]\d{1,2}(?:[\/\-\.]\d{2,4})?|\d{4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,2})\b/i', $line, $matches)) {
                // If we had a previous pending transaction, push it
                if (!empty($currentDate) && (!empty($currentDesc) || $currentDebit > 0 || $currentCredit > 0)) {
                    $rows[] = [
                        'date' => $this->normalizeDate($currentDate),
                        'description' => implode(' ', $currentDesc),
                        'debit' => $currentDebit,
                        'credit' => $currentCredit,
                        'balance' => $currentBalance,
                        'bank_detected' => strtoupper($bankType),
                        'status' => 'OK',
                    ];
                }

                $currentDate = $matches[1];
                $currentDesc = [];
                $currentDebit = 0;
                $currentCredit = 0;
                $currentBalance = 0;

                // Remove date from the line
                $remaining = trim(substr($line, strlen($matches[0])));

                // Parse the rest of this transaction line
                $this->extractAmountsAndDescription($remaining, $bankType, $currentDesc, $currentDebit, $currentCredit, $currentBalance);
            } else {
                // Continuation line of previous transaction (multi-line description or trailing numbers)
                if (!empty($currentDate)) {
                    $this->extractAmountsAndDescription($line, $bankType, $currentDesc, $currentDebit, $currentCredit, $currentBalance, true);
                }
            }
        }

        // Push last item
        if (!empty($currentDate)) {
            $rows[] = [
                'date' => $this->normalizeDate($currentDate),
                'description' => implode(' ', $currentDesc),
                'debit' => $currentDebit,
                'credit' => $currentCredit,
                'balance' => $currentBalance,
                'bank_detected' => strtoupper($bankType),
                'status' => 'OK',
            ];
        }

        // If standard line-by-line regex found nothing, try delimited parsing (CSV/Tab/Pipe)
        if (empty($rows)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $this->isNoiseLine($line)) continue;

                $parts = preg_split('/[\t,;|]+/', $line);
                if (count($parts) >= 3) {
                    $date = trim($parts[0]);
                    $desc = trim($parts[1]);
                    $num1 = $this->parseMoney($parts[2] ?? '0');
                    $num2 = isset($parts[3]) ? $this->parseMoney($parts[3]) : 0;
                    $balance = isset($parts[4]) ? $this->parseMoney($parts[4]) : 0;

                    $rows[] = [
                        'date' => $this->normalizeDate($date),
                        'description' => $desc,
                        'debit' => $num1,
                        'credit' => $num2,
                        'balance' => $balance,
                        'bank_detected' => 'TABULAR',
                        'status' => 'OK',
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * Extract amounts (Debit, Credit, Balance) and Description from transaction segment
     */
    protected function extractAmountsAndDescription(string $text, string $bankType, array &$descList, float &$debit, float &$credit, float &$balance, bool $isContinuation = false): void
    {
        // Check for BCA format: contains "CR" or "DB" indicator
        // e.g. "15,000,000.00 CR 52,430,100.00" or "50,000.00 DB 37,430,100.00"
        if (preg_match('/([\d\.,]+)\s+(CR|DB)(?:\s+([\d\.,]+))?/i', $text, $bcaMatch)) {
            $amount = $this->parseMoney($bcaMatch[1]);
            $type = strtoupper($bcaMatch[2]);
            if ($type === 'CR') {
                $credit = $amount;
            } else {
                $debit = $amount;
            }

            if (isset($bcaMatch[3])) {
                $balance = $this->parseMoney($bcaMatch[3]);
            }

            // The description is whatever text comes before the amount
            $descPart = trim(substr($text, 0, strpos($text, $bcaMatch[0])));
            if (!empty($descPart)) {
                $descList[] = $descPart;
            }
            return;
        }

        // Check for 2 or 3 trailing money numbers (Debit, Credit, Balance)
        // e.g. "BIAYA ADM BULANAN 25.000,00 0,00 1.250.000,00"
        if (preg_match('/([\d\.,]+)\s+([\d\.,]+)(?:\s+([\d\.,]+))?$/', $text, $numMatches)) {
            $val1 = $this->parseMoney($numMatches[1]);
            $val2 = $this->parseMoney($numMatches[2]);
            $val3 = isset($numMatches[3]) ? $this->parseMoney($numMatches[3]) : null;

            if ($val1 > 0 || $val2 > 0) {
                if ($val3 !== null) {
                    $debit = $val1;
                    $credit = $val2;
                    $balance = $val3;
                } else {
                    // Check context or assume debit/credit
                    if ($debit == 0 && $credit == 0) {
                        $debit = $val1;
                        $credit = $val2;
                    }
                }

                $descPart = trim(substr($text, 0, strpos($text, $numMatches[0])));
                if (!empty($descPart)) {
                    $descList[] = $descPart;
                }
                return;
            }
        }

        // Check single trailing money number
        if (preg_match('/([\d\.,]{4,})$/', $text, $singleMatch)) {
            $val = $this->parseMoney($singleMatch[1]);
            if ($val > 0 && ($debit == 0 && $credit == 0)) {
                // If description indicates income/interest/bunga/transfer masuk -> credit
                $upper = strtoupper($text);
                if (str_contains($upper, 'MASUK') || str_contains($upper, 'CR') || str_contains($upper, 'SETORAN') || str_contains($upper, 'BUNGA')) {
                    $credit = $val;
                } else {
                    $debit = $val;
                }
                $descPart = trim(substr($text, 0, strpos($text, $singleMatch[0])));
                if (!empty($descPart)) {
                    $descList[] = $descPart;
                }
                return;
            }
        }

        // Pure description text
        if (!empty($text)) {
            $descList[] = $text;
        }
    }

    /**
     * Filter out bank statement noise lines
     */
    protected function isNoiseLine(string $line): bool
    {
        $upper = strtoupper($line);
        $noisePhrases = [
            'HALAMAN', 'PAGE', 'NO. REKENING', 'NOMOR REKENING', 'SALDO AWAL', 'SALDO AKHIR',
            'MUTASI KREDIT', 'MUTASI DEBET', 'TOTAL MUTASI', 'TANGGAL TRANSAKSI', 'KETERANGAN',
            'CATATAN:', 'PERHATIAN:', 'TERDAFTAR DAN DIAWASI OLEH OTORITAS JASA KEUANGAN',
            'PT BANK', 'REKENING KORAN', 'STATEMENT OF ACCOUNT', 'PERIOD', 'PERIODE CETAK',
            'MATA UANG', 'CURRENCY', 'CABANG', 'TELLER', 'BEGINNING BALANCE', 'CLOSING BALANCE'
        ];

        foreach ($noisePhrases as $noise) {
            if (str_starts_with($upper, $noise)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize Indonesian or international currency text to float
     */
    protected function parseMoney(string $value): float
    {
        $value = trim($value);
        if (empty($value) || $value === '-' || $value === '0,00' || $value === '0.00') {
            return 0.0;
        }

        // Remove currency symbols & whitespace
        $value = preg_replace('/[^\d\.,]/', '', $value);

        // If it has both dot and comma
        if (str_contains($value, '.') && str_contains($value, ',')) {
            // Check which comes last: e.g. "1.250.000,00" (Indonesian) vs "1,250,000.00" (International)
            if (strrpos($value, ',') > strrpos($value, '.')) {
                // Indonesian: remove dots, replace comma with dot
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // International: remove commas
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            // Only comma: could be decimal ("50000,50") or thousand separator ("1,000,000")
            if (preg_match('/,\d{2}$/', $value)) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, '.')) {
            // Only dots: could be thousand separator ("1.500.000") or decimal ("1500000.50")
            if (preg_match('/\.\d{3}$/', $value) && !preg_match('/\.\d{2}$/', $value)) {
                $value = str_replace('.', '', $value);
            }
        }

        return (float) $value;
    }

    /**
     * Normalize various date formats to YYYY-MM-DD
     */
    protected function normalizeDate(string $date): string
    {
        $date = trim($date);
        $year = date('Y');

        // DD/MM e.g. 01/09 -> YYYY-09-01
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $year, $m[2], $m[1]);
        }

        // DD/MM/YYYY or DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // DD/MM/YY
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})$/', $date, $m)) {
            return sprintf('20%02d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // YYYY-MM-DD
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        return $date;
    }

    /**
     * Pure PHP PDF stream extractor (handles uncompressed & FlateDecode streams)
     */
    protected function extractTextFromPdf(string $filePath): string
    {
        if (!file_exists($filePath)) return '';

        $content = file_get_contents($filePath);
        $text = '';

        // Extract all stream objects
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $streamMatches);

        foreach ($streamMatches[1] as $stream) {
            $uncompressed = '';
            // Check if gzipped/FlateDecode
            $decompressed = @gzuncompress($stream);
            if ($decompressed !== false) {
                $uncompressed = $decompressed;
            } else {
                $uncompressed = $stream;
            }

            // Extract text enclosed in parenthesis within Tj or TJ operators
            if (preg_match_all('/\[(.*?)\]\s*TJ/s', $uncompressed, $tjArrayMatches)) {
                foreach ($tjArrayMatches[1] as $tjContent) {
                    if (preg_match_all('/\((.*?)\)/s', $tjContent, $strMatches)) {
                        $text .= implode('', $strMatches[1]) . "\n";
                    }
                }
            }

            if (preg_match_all('/\((.*?)\)\s*Tj/s', $uncompressed, $tjMatches)) {
                $text .= implode("\n", $tjMatches[1]) . "\n";
            }

            // Also check for plain text lines
            if (empty($text) && !empty($uncompressed)) {
                $clean = preg_replace('/[^\x20-\x7E\r\n\t]/', '', $uncompressed);
                if (strlen($clean) > 20) {
                    $text .= $clean . "\n";
                }
            }
        }

        return $text;
    }

    /**
     * Export to Excel Spreadsheet (.xlsx / SpreadsheetML XML)
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $rows = json_decode($request->input('rows_data', '[]'), true) ?: [];
        $bankName = $request->input('bank_name', 'Rekening Koran Bank');
        $fileName = 'Mutasi_' . preg_replace('/[^A-Za-z0-9_]/', '_', $bankName) . '_' . date('Ymd_His') . '.xls';

        return new StreamedResponse(function () use ($rows, $bankName) {
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ' .
                 'xmlns:o="urn:schemas-microsoft-com:office:office" ' .
                 'xmlns:x="urn:schemas-microsoft-com:office:excel" ' .
                 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" ' .
                 'xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

            // Styles
            echo '<Styles>' . "\n";
            echo '<Style ss:ID="Default" ss:Name="Normal"><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000"/></Style>' . "\n";
            echo '<Style ss:ID="HeaderTitle"><Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#0F172A"/></Style>' . "\n";
            echo '<Style ss:ID="HeaderSub"><Font ss:FontName="Calibri" ss:Size="10" ss:Italic="1" ss:Color="#64748B"/></Style>' . "\n";
            echo '<Style ss:ID="TableHead"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1E293B" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>' . "\n";
            echo '<Style ss:ID="CellDate"><Alignment ss:Horizontal="Center"/></Style>' . "\n";
            echo '<Style ss:ID="CellText"><Alignment ss:Horizontal="Left"/></Style>' . "\n";
            echo '<Style ss:ID="CellDebit"><Font ss:FontName="Calibri" ss:Color="#E11D48"/><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right"/></Style>' . "\n";
            echo '<Style ss:ID="CellCredit"><Font ss:FontName="Calibri" ss:Color="#059669"/><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right"/></Style>' . "\n";
            echo '<Style ss:ID="CellBalance"><Font ss:FontName="Calibri" ss:Bold="1" ss:Color="#1E293B"/><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right"/></Style>' . "\n";
            echo '<Style ss:ID="TotalRow"><Font ss:FontName="Calibri" ss:Bold="1" ss:Size="11" ss:Color="#0F172A"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right"/></Style>' . "\n";
            echo '</Styles>' . "\n";

            // Worksheet
            echo '<Worksheet ss:Name="Mutasi Rekening">' . "\n";
            echo '<Table ss:DefaultColumnWidth="80">' . "\n";
            echo '<Column ss:Width="40"/>' . "\n"; // No
            echo '<Column ss:Width="90"/>' . "\n"; // Tanggal
            echo '<Column ss:Width="300"/>' . "\n"; // Keterangan
            echo '<Column ss:Width="120"/>' . "\n"; // Debet
            echo '<Column ss:Width="120"/>' . "\n"; // Kredit
            echo '<Column ss:Width="130"/>' . "\n"; // Saldo
            echo '<Column ss:Width="80"/>' . "\n"; // Status

            // Title Rows
            echo '<Row><Cell ss:StyleID="HeaderTitle"><Data ss:Type="String">HASIL KONVERSI REKENING KORAN BANK — AKRU ERP</Data></Cell></Row>' . "\n";
            echo '<Row><Cell ss:StyleID="HeaderSub"><Data ss:Type="String">Akun: ' . htmlspecialchars($bankName, ENT_XML1) . ' | Tanggal Ekspor: ' . date('d F Y H:i') . '</Data></Cell></Row>' . "\n";
            echo '<Row/>' . "\n"; // Blank line

            // Table Header
            echo '<Row ss:Height="25">' . "\n";
            echo '<Cell ss:StyleID="TableHead"><Data ss:Type="String">NO</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TableHead"><Data ss:Type="String">TANGGAL</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TableHead"><Data ss:Type="String">URAIAN / KETERANGAN TRANSAKSI</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TableHead"><Data ss:Type="String">DEBET (KELUAR)</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TableHead"><Data ss:Type="String">KREDIT (MASUK)</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TableHead"><Data ss:Type="String">SALDO AKHIR</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TableHead"><Data ss:Type="String">STATUS</Data></Cell>' . "\n";
            echo '</Row>' . "\n";

            $totalDebit = 0;
            $totalCredit = 0;
            $index = 1;

            foreach ($rows as $row) {
                $debit = (float) ($row['debit'] ?? 0);
                $credit = (float) ($row['credit'] ?? 0);
                $balance = (float) ($row['balance'] ?? 0);
                $totalDebit += $debit;
                $totalCredit += $credit;

                echo '<Row>' . "\n";
                echo '<Cell ss:StyleID="CellDate"><Data ss:Type="Number">' . $index++ . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="CellDate"><Data ss:Type="String">' . htmlspecialchars($row['date'] ?? '', ENT_XML1) . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="CellText"><Data ss:Type="String">' . htmlspecialchars($row['description'] ?? '', ENT_XML1) . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="CellDebit"><Data ss:Type="Number">' . $debit . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="CellCredit"><Data ss:Type="Number">' . $credit . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="CellBalance"><Data ss:Type="Number">' . $balance . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="CellDate"><Data ss:Type="String">' . htmlspecialchars($row['status'] ?? 'OK', ENT_XML1) . '</Data></Cell>' . "\n";
                echo '</Row>' . "\n";
            }

            // Summary row
            echo '<Row ss:Height="22">' . "\n";
            echo '<Cell ss:StyleID="TotalRow"><Data ss:Type="String"></Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TotalRow"><Data ss:Type="String"></Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TotalRow" ss:MergeAcross="0"><Data ss:Type="String">TOTAL MUTASI</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TotalRow"><Data ss:Type="Number">' . $totalDebit . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TotalRow"><Data ss:Type="Number">' . $totalCredit . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TotalRow"><Data ss:Type="Number">' . ($totalCredit - $totalDebit) . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="TotalRow"><Data ss:Type="String">SELESAI</Data></Cell>' . "\n";
            echo '</Row>' . "\n";

            echo '</Table>' . "\n";
            echo '</Worksheet>' . "\n";
            echo '</Workbook>' . "\n";
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export to CSV with UTF-8 BOM
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $rows = json_decode($request->input('rows_data', '[]'), true) ?: [];
        $bankName = $request->input('bank_name', 'Rekening Koran Bank');
        $fileName = 'Mutasi_' . preg_replace('/[^A-Za-z0-9_]/', '_', $bankName) . '_' . date('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Headers
            fputcsv($handle, ['No', 'Tanggal', 'Deskripsi / Uraian', 'Debet (Keluar)', 'Kredit (Masuk)', 'Saldo Akhir', 'Status']);

            $i = 1;
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $i++,
                    $row['date'] ?? '',
                    $row['description'] ?? '',
                    (float) ($row['debit'] ?? 0),
                    (float) ($row['credit'] ?? 0),
                    (float) ($row['balance'] ?? 0),
                    $row['status'] ?? 'OK',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
