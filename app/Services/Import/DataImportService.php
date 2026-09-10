<?php

namespace App\Services\Import;

use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use Illuminate\Http\Response;
use ZipArchive;

class DataImportService
{
    /**
     * Parse uploaded file (XLSX, XLS XML, or CSV) into an associative array of rows
     */
    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        // 1. Check if file is genuine XLSX (ZIP archive starting with PK\x03\x04)
        $handle = fopen($filePath, 'rb');
        $magic = fread($handle, 4);
        fclose($handle);

        if ($magic === "PK\x03\x04") {
            $xlsxRows = $this->parseXlsxFile($filePath);
            if (!empty($xlsxRows)) {
                return $xlsxRows;
            }
        }

        $content = file_get_contents($filePath);

        // Strip UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // 2. Check if it's SpreadsheetML XML (.xls / .xml)
        if (str_contains($content, '<?xml') && str_contains($content, '<Workbook')) {
            return $this->parseExcelXml($content);
        }

        // 3. Fallback: Parse as CSV / Delimited text
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [];
        }

        // Detect delimiter from first line (, ; \t |)
        $firstLine = $lines[0];
        $delimiter = ',';
        $delimiters = [',', ';', "\t", '|'];
        $maxCount = 0;
        foreach ($delimiters as $del) {
            $count = substr_count($firstLine, $del);
            if ($count > $maxCount) {
                $maxCount = $count;
                $delimiter = $del;
            }
        }

        $headers = [];
        $rows = [];

        foreach ($lines as $idx => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $cols = str_getcsv($line, $delimiter);

            if ($idx === 0) {
                $headers = $this->normalizeHeaders($cols);
            } else {
                if (count($cols) > 0) {
                    $row = [];
                    foreach ($cols as $colIdx => $val) {
                        if (isset($headers[$colIdx])) {
                            $row[$headers[$colIdx]['clean']] = trim($val);
                            $row[$headers[$colIdx]['full']] = trim($val);
                        } else {
                            $row['col_' . $colIdx] = trim($val);
                        }
                    }
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Parse native Microsoft Excel .xlsx files using pure PHP ZipArchive
     */
    protected function parseXlsxFile(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Read shared strings if present
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sXml = @simplexml_load_string($sharedXml);
            if ($sXml && isset($sXml->si)) {
                foreach ($sXml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $txt = '';
                        foreach ($si->r as $r) {
                            $txt .= (string)$r->t;
                        }
                        $sharedStrings[] = $txt;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read first sheet XML
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }

        if ($sheetXml === false) {
            $zip->close();
            return [];
        }

        $sXml = @simplexml_load_string($sheetXml);
        if (!$sXml || !isset($sXml->sheetData)) {
            $zip->close();
            return [];
        }

        $headers = [];
        $rows = [];

        foreach ($sXml->sheetData->row as $r) {
            $cells = [];
            foreach ($r->c as $c) {
                $colAttr = (string)$c['r'];
                $colLetter = preg_replace('/[0-9]/', '', $colAttr);
                $type = (string)$c['t'];

                $val = '';
                if ($type === 's') {
                    $idx = (int)$c->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string)($c->is->t ?? '');
                } else {
                    $val = (string)($c->v ?? '');
                }

                $cells[$colLetter] = trim($val);
            }

            if (empty($cells)) continue;

            if (empty($headers)) {
                // Header row
                $headers = $this->normalizeHeaders($cells);
            } else {
                $row = [];
                foreach ($cells as $colLetter => $val) {
                    if (isset($headers[$colLetter])) {
                        $row[$headers[$colLetter]['clean']] = $val;
                        $row[$headers[$colLetter]['full']] = $val;
                    } else {
                        $row['col_' . $colLetter] = $val;
                    }
                }
                $rows[] = $row;
            }
        }

        $zip->close();
        return $rows;
    }

    /**
     * Parse XML Spreadsheet (.xml / .xls)
     */
    protected function parseExcelXml(string $xmlContent): array
    {
        $rows = [];
        $headers = [];

        if (preg_match_all('/<Row[^>]*>(.*?)<\/Row>/is', $xmlContent, $rowMatches)) {
            foreach ($rowMatches[1] as $rowXml) {
                $cells = [];
                if (preg_match_all('/<Cell[^>]*>.*?<Data[^>]*>(.*?)<\/Data>.*?<\/Cell>/is', $rowXml, $cellMatches)) {
                    $cells = array_map('trim', $cellMatches[1]);
                }

                if (empty($cells)) continue;

                if (empty($headers)) {
                    $headers = $this->normalizeHeaders($cells);
                } else {
                    $row = [];
                    foreach ($cells as $cellIdx => $val) {
                        $decoded = html_entity_decode($val);
                        if (isset($headers[$cellIdx])) {
                            $row[$headers[$cellIdx]['clean']] = $decoded;
                            $row[$headers[$cellIdx]['full']] = $decoded;
                        } else {
                            $row['col_' . $cellIdx] = $decoded;
                        }
                    }
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Normalize an array of header cells to both clean and full slugs
     */
    protected function normalizeHeaders(array $cells): array
    {
        $map = [];
        foreach ($cells as $key => $headerText) {
            $cleanText = preg_replace('/\s*\(.*?\)\s*/', '', (string)$headerText);
            $cleanSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $cleanText), '_'));
            $fullSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string)$headerText), '_'));

            $map[$key] = [
                'clean' => $cleanSlug ?: ('col_' . $key),
                'full' => $fullSlug ?: ('col_' . $key),
            ];
        }
        return $map;
    }

    /* ============================================================
     * 1. CHART OF ACCOUNTS (COA)
     * ============================================================ */

    public function importCoa(string $filePath, int $companyId): array
    {
        $rows = $this->parseFile($filePath);
        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            $code = $this->findValue($row, ['kode_akun', 'kode', 'code', 'account_code', 'no_akun']);
            $name = $this->findValue($row, ['nama_akun', 'nama', 'name', 'account_name']);
            $type = strtolower($this->findValue($row, ['tipe_akun', 'kategori_akun', 'tipe', 'type', 'kategori']) ?: 'asset');
            $normalBalance = strtolower($this->findValue($row, ['saldo_normal', 'normal_balance', 'posisi_normal', 'posisi']) ?: '');
            $subType = $this->findValue($row, ['sub_tipe', 'sub_klasifikasi', 'sub_type', 'subtype', 'kelompok']);
            $desc = $this->findValue($row, ['catatan_deskripsi', 'catatan', 'keterangan', 'deskripsi', 'description', 'notes']);

            if (empty($code) || empty($name)) {
                $errors[] = "Baris " . ($idx + 2) . ": Kode Akun atau Nama Akun tidak boleh kosong.";
                continue;
            }

            // Normalize type
            $validTypes = ['asset', 'liability', 'equity', 'revenue', 'cogs', 'expense'];
            if (!in_array($type, $validTypes)) {
                if (str_contains($type, 'aktiva') || str_contains($type, 'aset')) $type = 'asset';
                elseif (str_contains($type, 'hutang') || str_contains($type, 'kewajiban') || str_contains($type, 'liabilitas')) $type = 'liability';
                elseif (str_contains($type, 'modal') || str_contains($type, 'ekuitas')) $type = 'equity';
                elseif (str_contains($type, 'hpp') || str_contains($type, 'harga pokok') || str_contains($type, 'beban pokok')) $type = 'cogs';
                elseif (str_contains($type, 'pendapatan') || str_contains($type, 'penjualan')) $type = 'revenue';
                elseif (str_contains($type, 'beban') || str_contains($type, 'biaya') || str_contains($type, 'pengeluaran')) $type = 'expense';
                else $type = 'asset';
            }

            // Normalize normal_balance
            if (empty($normalBalance)) {
                $normalBalance = in_array($type, ['asset', 'cogs', 'expense']) ? 'debit' : 'credit';
            } else {
                if (str_contains($normalBalance, 'deb')) $normalBalance = 'debit';
                elseif (str_contains($normalBalance, 'kre')) $normalBalance = 'credit';
                else $normalBalance = in_array($type, ['asset', 'cogs', 'expense']) ? 'debit' : 'credit';
            }

            $account = Account::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('code', $code)
                ->first();

            if ($account) {
                $account->update([
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normalBalance,
                    'sub_type' => $subType,
                    'description' => $desc,
                ]);
                $updated++;
            } else {
                Account::create([
                    'company_id' => $companyId,
                    'code' => $code,
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normalBalance,
                    'sub_type' => $subType,
                    'description' => $desc,
                    'is_active' => true,
                ]);
                $imported++;
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function downloadCoaTemplate(): Response
    {
        $headers = [
            'Kode Akun',
            'Nama Akun',
            'Kategori Akun (Aset/Kewajiban/Ekuitas/Pendapatan/HPP/Beban)',
            'Saldo Normal (Debit/Kredit)',
            'Sub Klasifikasi',
            'Catatan / Deskripsi',
        ];

        $sampleRows = [
            ['1110', 'Kas Besar Kantor', 'Aset', 'Debit', 'Kas & Bank', 'Kas tunai brankas kantor pusat'],
            ['1120', 'Bank BCA Operasional', 'Aset', 'Debit', 'Kas & Bank', 'Rekening giro transaksi harian'],
            ['1121', 'Bank Mandiri Payroll', 'Aset', 'Debit', 'Kas & Bank', 'Rekening khusus pembayaran gaji karyawan'],
            ['1200', 'Piutang Usaha', 'Aset', 'Debit', 'Piutang Dagang', 'Piutang invoice tempo pelanggan'],
            ['1300', 'Persediaan Barang Dagang', 'Aset', 'Debit', 'Persediaan', 'Nilai fisik stok barang dagangan'],
            ['1410', 'Sewa Dibayar Dimuka', 'Aset', 'Debit', 'Beban Dibayar Dimuka', 'Biaya sewa ruko/kantor dibayar dimuka'],
            ['2100', 'Hutang Usaha', 'Kewajiban', 'Kredit', 'Hutang Lancar', 'Kewajiban tagihan tempo ke pemasok'],
            ['2210', 'Hutang PPh 21', 'Kewajiban', 'Kredit', 'Hutang Pajak', 'Potongan PPh 21 gaji karyawan'],
            ['2220', 'Hutang PPN Keluaran', 'Kewajiban', 'Kredit', 'Hutang Pajak', 'PPN 11% faktur pajak penjualan'],
            ['3100', 'Modal Disetor', 'Ekuitas', 'Kredit', 'Modal Pemilik', 'Setoran modal awal pemilik perusahaan'],
            ['3200', 'Saldo Laba Ditahan', 'Ekuitas', 'Kredit', 'Laba Ditahan', 'Akumulasi laba bersih periode lalu'],
            ['4100', 'Pendapatan Penjualan Barang', 'Pendapatan', 'Kredit', 'Pendapatan Operasional', 'Hasil penjualan barang dagang fisik'],
            ['4200', 'Pendapatan Jasa & Konsultasi', 'Pendapatan', 'Kredit', 'Pendapatan Operasional', 'Hasil penyerahan jasa profesional'],
            ['5100', 'Beban Pokok Penjualan (HPP)', 'HPP', 'Debit', 'Harga Pokok Penjualan', 'Harga perolehan barang yang terjual'],
            ['6100', 'Beban Gaji & Tunjangan', 'Beban', 'Debit', 'Beban Operasional', 'Gaji pokok & tunjangan bulanan karyawan'],
            ['6200', 'Beban Listrik, Air & Internet', 'Beban', 'Debit', 'Beban Utilitas', 'Biaya operasional utilitas kantor'],
        ];

        $colWidths = [15, 34, 30, 18, 25, 42];

        return $this->generateXlsxDownload('Template_Impor_Bagan_Akun_COA.xlsx', $headers, $sampleRows, $colWidths);
    }

    /* ============================================================
     * 2. CUSTOMERS (PELANGGAN)
     * ============================================================ */

    public function importCustomers(string $filePath, int $companyId): array
    {
        $rows = $this->parseFile($filePath);
        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            $code = $this->findValue($row, ['kode_pelanggan', 'kode', 'code', 'customer_code']);
            $name = $this->findValue($row, ['nama_pelanggan', 'nama_rekanan_perusahaan', 'nama_rekanan', 'nama_perusahaan', 'nama_pelanggan_perusahaan', 'nama', 'name', 'customer_name', 'pelanggan']);
            $idType = strtoupper($this->findValue($row, ['jenis_identitas', 'tipe_identitas', 'identitas', 'identity_type']) ?: 'NPWP');
            $idNumber = $this->findValue($row, ['nomor_npwp_atau_nik', 'nomor_npwp_nik', 'npwp_nik', 'npwp_atau_nik', 'npwp', 'nik', 'nomor_identitas', 'identity_number', 'no_identitas']);
            $email = $this->findValue($row, ['email', 'alamat_email']);
            $phone = $this->findValue($row, ['nomor_telepon_hp', 'nomor_telepon', 'telepon', 'no_telepon', 'phone', 'whatsapp', 'no_hp', 'no_telp', 'telp']);
            $address = $this->findValue($row, ['alamat_lengkap', 'alamat', 'address']);
            $city = $this->findValue($row, ['kota', 'city']);
            $terms = (int) ($this->findValue($row, ['termin_pembayaran_hari', 'termin_pembayaran', 'termin_bayar_hari', 'termin_hari', 'termin', 'terms', 'payment_terms_days', 'hari_termin', 'top']) ?: 0);
            $limit = (float) ($this->findValue($row, ['plafon_batas_kredit_rp', 'plafon_batas_kredit', 'batas_kredit', 'limit_kredit', 'credit_limit', 'limit', 'plafon_kredit', 'plafon']) ?: 0);

            if (empty($name)) {
                $errors[] = "Baris " . ($idx + 2) . ": Nama Pelanggan tidak boleh kosong.";
                continue;
            }

            $idType = in_array($idType, ['NPWP', 'NIK']) ? $idType : 'NPWP';

            $contact = Contact::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($code, $name) {
                    if (!empty($code)) {
                        $q->where('code', $code);
                    } else {
                        $q->where('name', $name);
                    }
                })
                ->first();

            $data = [
                'company_id' => $companyId,
                'type' => 'customer',
                'code' => $code ?: ('CUST-' . str_pad((string)($imported + $updated + 1), 3, '0', STR_PAD_LEFT)),
                'name' => $name,
                'identity_type' => $idType,
                'identity_number' => $idNumber,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'payment_terms_days' => $terms,
                'credit_limit' => $limit,
                'is_active' => true,
            ];

            if ($contact) {
                $contact->update($data);
                $updated++;
            } else {
                Contact::create($data);
                $imported++;
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function downloadCustomerTemplate(): Response
    {
        $headers = [
            'Kode Pelanggan',
            'Nama Rekanan / Perusahaan',
            'Jenis Identitas (NPWP/NIK)',
            'Nomor NPWP atau NIK',
            'Email',
            'Nomor Telepon / HP',
            'Alamat Lengkap',
            'Kota',
            'Termin Pembayaran (Hari)',
            'Plafon Batas Kredit (Rp)',
        ];

        $sampleRows = [
            ['CUST-001', 'PT Sumber Rejeki Abadi', 'NPWP', '01.234.567.8-012.000', 'finance@sumberrejeki.com', '021-5551234', 'Jl. Jenderal Sudirman Kav. 52, SCBD', 'Jakarta Selatan', '30', '50000000'],
            ['CUST-002', 'CV Berkah Mandiri Sejahtera', 'NPWP', '02.345.678.9-023.000', 'purchasing@berkahmandiri.co.id', '031-8971234', 'Kawasan Industri Rungkut Blok B-5', 'Surabaya', '14', '25000000'],
            ['CUST-003', 'Toko Pojok Elektronik', 'NIK', '3201234567890001', 'ahmad.fauzi@gmail.com', '081234567890', 'Jl. Pajajaran No. 8, Babakan', 'Bogor', '0', '0'],
        ];

        $colWidths = [18, 36, 18, 25, 28, 22, 40, 18, 18, 24];

        return $this->generateXlsxDownload('Template_Impor_Pelanggan.xlsx', $headers, $sampleRows, $colWidths);
    }

    /* ============================================================
     * 3. SUPPLIERS (PEMASOK)
     * ============================================================ */

    public function importSuppliers(string $filePath, int $companyId): array
    {
        $rows = $this->parseFile($filePath);
        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            $code = $this->findValue($row, ['kode_pemasok', 'kode', 'code', 'supplier_code']);
            $name = $this->findValue($row, ['nama_pemasok', 'nama_vendor', 'nama_rekanan', 'nama_pemasok_vendor', 'nama', 'name', 'supplier_name']);
            $idType = strtoupper($this->findValue($row, ['jenis_identitas', 'tipe_identitas', 'identitas', 'identity_type']) ?: 'NPWP');
            $idNumber = $this->findValue($row, ['nomor_npwp_atau_nik', 'nomor_npwp_nik', 'npwp_nik', 'npwp_atau_nik', 'npwp', 'nik', 'nomor_identitas', 'identity_number', 'no_identitas']);
            $email = $this->findValue($row, ['email', 'alamat_email']);
            $phone = $this->findValue($row, ['nomor_telepon_hp', 'nomor_telepon', 'telepon', 'no_telepon', 'phone', 'whatsapp', 'no_hp', 'no_telp', 'telp']);
            $address = $this->findValue($row, ['alamat_lengkap', 'alamat', 'address']);
            $city = $this->findValue($row, ['kota', 'city']);
            $terms = (int) ($this->findValue($row, ['termin_pembayaran_hari', 'termin_pembayaran', 'termin_bayar_hari', 'termin_hari', 'termin', 'terms', 'payment_terms_days', 'hari_termin', 'top']) ?: 0);

            if (empty($name)) {
                $errors[] = "Baris " . ($idx + 2) . ": Nama Pemasok tidak boleh kosong.";
                continue;
            }

            $idType = in_array($idType, ['NPWP', 'NIK']) ? $idType : 'NPWP';

            $contact = Contact::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($code, $name) {
                    if (!empty($code)) {
                        $q->where('code', $code);
                    } else {
                        $q->where('name', $name);
                    }
                })
                ->first();

            $data = [
                'company_id' => $companyId,
                'type' => 'supplier',
                'code' => $code ?: ('SUPP-' . str_pad((string)($imported + $updated + 1), 3, '0', STR_PAD_LEFT)),
                'name' => $name,
                'identity_type' => $idType,
                'identity_number' => $idNumber,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'payment_terms_days' => $terms,
                'is_active' => true,
            ];

            if ($contact) {
                $contact->update($data);
                $updated++;
            } else {
                Contact::create($data);
                $imported++;
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function downloadSupplierTemplate(): Response
    {
        $headers = [
            'Kode Pemasok',
            'Nama Pemasok / Vendor',
            'Jenis Identitas (NPWP/NIK)',
            'Nomor NPWP atau NIK',
            'Email',
            'Nomor Telepon / HP',
            'Alamat Lengkap',
            'Kota',
            'Termin Pembayaran (Hari)',
        ];

        $sampleRows = [
            ['SUPP-001', 'PT Distributor Teknologi Utama', 'NPWP', '03.456.789.0-034.000', 'order@distributortekno.co.id', '021-7788990', 'Jl. Gatot Subroto No. 88', 'Jakarta Pusat', '45'],
            ['SUPP-002', 'CV Sentosa Paper & Logistik', 'NPWP', '04.567.890.1-045.000', 'sales@sentosapaper.id', '022-8765432', 'Jl. Soekarno Hatta No. 120', 'Bandung', '30'],
            ['SUPP-003', 'Bengkel Sparepart Makmur', 'NIK', '3172012345670002', 'sparepartmakmur@gmail.com', '081399887766', 'Jl. Daan Mogot Km 11', 'Jakarta Barat', '14'],
        ];

        $colWidths = [18, 36, 18, 25, 28, 22, 40, 18, 18];

        return $this->generateXlsxDownload('Template_Impor_Pemasok.xlsx', $headers, $sampleRows, $colWidths);
    }

    /* ============================================================
     * 4. PRODUCTS & SERVICES (PRODUK & JASA)
     * ============================================================ */

    public function importProducts(string $filePath, int $companyId): array
    {
        $rows = $this->parseFile($filePath);
        $imported = 0;
        $updated = 0;
        $errors = [];

        // Preload company accounts for matching
        $accounts = Account::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->get();

        foreach ($rows as $idx => $row) {
            $sku = $this->findValue($row, ['kode_sku', 'sku', 'kode_item', 'kode_produk', 'kode']);
            $name = $this->findValue($row, ['nama_produk_jasa', 'nama_produk', 'nama_item', 'nama_barang', 'nama', 'name']);
            $type = strtolower($this->findValue($row, ['jenis_item_barang_jasa', 'jenis_item', 'tipe_item', 'tipe', 'type']) ?: 'goods');
            $buyPrice = (float) ($this->findValue($row, ['harga_beli_rp', 'harga_beli', 'harga_pokok', 'buy_price', 'beli']) ?: 0);
            $sellPrice = (float) ($this->findValue($row, ['harga_jual_rp', 'harga_jual', 'sell_price', 'jual']) ?: 0);
            $stockableRaw = strtolower($this->findValue($row, ['kelola_stok_ya_tidak', 'kelola_stok', 'lacak_stok', 'stok', 'is_stockable']) ?? '1');
            $salesAccountCode = $this->findValue($row, ['kode_akun_penjualan', 'akun_penjualan', 'sales_account_code', 'sales_account']);
            $cogsAccountCode = $this->findValue($row, ['kode_akun_hpp', 'akun_hpp', 'cogs_account_code', 'cogs_account']);
            $description = $this->findValue($row, ['deskripsi_catatan', 'deskripsi', 'catatan', 'keterangan', 'description', 'notes']);

            if (empty($sku) || empty($name)) {
                $errors[] = "Baris " . ($idx + 2) . ": SKU atau Nama Produk/Jasa tidak boleh kosong.";
                continue;
            }

            // Normalize type
            if (str_contains($type, 'jasa') || str_contains($type, 'service')) {
                $type = 'service';
                $isStockable = false;
            } else {
                $type = 'goods';
                $isStockable = !in_array($stockableRaw, ['0', 'tidak', 'no', 'false']);
            }

            // Map account IDs
            $salesAccountId = null;
            if (!empty($salesAccountCode)) {
                $found = $accounts->first(function ($a) use ($salesAccountCode) {
                    return $a->code == $salesAccountCode || str_contains(strtolower($a->name), strtolower($salesAccountCode));
                });
                $salesAccountId = $found ? $found->id : null;
            }

            $cogsAccountId = null;
            if (!empty($cogsAccountCode)) {
                $found = $accounts->first(function ($a) use ($cogsAccountCode) {
                    return $a->code == $cogsAccountCode || str_contains(strtolower($a->name), strtolower($cogsAccountCode));
                });
                $cogsAccountId = $found ? $found->id : null;
            }

            $item = Item::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('sku', $sku)
                ->first();

            $data = [
                'company_id' => $companyId,
                'sku' => $sku,
                'name' => $name,
                'type' => $type,
                'buy_price' => $buyPrice,
                'sell_price' => $sellPrice,
                'is_stockable' => $isStockable,
                'sales_account_id' => $salesAccountId,
                'cogs_account_id' => $cogsAccountId,
                'description' => $description,
                'is_active' => true,
            ];

            if ($item) {
                $item->update($data);
                $updated++;
            } else {
                Item::create($data);
                $imported++;
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function downloadProductTemplate(): Response
    {
        $headers = [
            'Kode / SKU',
            'Nama Produk / Jasa',
            'Jenis Item (Barang/Jasa)',
            'Harga Beli (Rp)',
            'Harga Jual (Rp)',
            'Kelola Stok (Ya/Tidak)',
            'Kode Akun Penjualan',
            'Kode Akun HPP',
            'Deskripsi / Catatan',
        ];

        $sampleRows = [
            ['PRD-001', 'Laptop Bisnis Ultra 14 inch Core i7', 'Barang', '12500000', '15000000', 'Ya', '4100', '5100', 'Unit laptop garansi resmi 2 tahun distributor'],
            ['PRD-002', 'Monitor LED 27 inch 4K UHD IPS', 'Barang', '3200000', '4150000', 'Ya', '4100', '5100', 'Monitor resolusi tinggi HDMI & DisplayPort'],
            ['PRD-003', 'Kabel HDMI Premium Braided 2 Meter', 'Barang', '45000', '85000', 'Ya', '4100', '5100', 'Aksesoris kabel pendukung display video'],
            ['SRV-001', 'Jasa Maintenance & Support IT Bulanan', 'Jasa', '0', '3500000', 'Tidak', '4200', '', 'Kontrak SLA perawatan sistem dan jaringan kantor'],
            ['SRV-002', 'Jasa Instalasi Jaringan LAN & Wi-Fi', 'Jasa', '0', '1500000', 'Tidak', '4200', '', 'Jasa konfigurasi router, switch & access point'],
        ];

        $colWidths = [18, 38, 18, 18, 18, 16, 20, 20, 42];

        return $this->generateXlsxDownload('Template_Impor_Produk_Jasa.xlsx', $headers, $sampleRows, $colWidths);
    }

    /* ============================================================
     * HELPER METHODS
     * ============================================================ */

    /**
     * Search row for matching column names flexibly
     */
    protected function findValue(array $row, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $pk) {
            if (isset($row[$pk]) && trim((string)$row[$pk]) !== '') {
                return trim((string)$row[$pk]);
            }
        }

        // Case-insensitive / slug-based fallback search
        $normalizedRowKeys = [];
        foreach ($row as $k => $v) {
            $normalizedRowKeys[strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$k)))] = $v;
        }

        foreach ($possibleKeys as $pk) {
            $cleanPk = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$pk)));
            if (isset($normalizedRowKeys[$cleanPk]) && trim((string)$normalizedRowKeys[$cleanPk]) !== '') {
                return trim((string)$normalizedRowKeys[$cleanPk]);
            }
        }

        return null;
    }

    /**
     * Generate downloadable genuine Microsoft Excel (.xlsx) file with styling
     */
    protected function generateXlsxDownload(string $filename, array $headers, array $rows, array $colWidths = []): Response
    {
        $filePath = $this->createStyledXlsxFile($headers, $rows, $colWidths);
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
     * Create styled XLSX file on disk and return absolute path
     */
    public function createStyledXlsxFile(array $headers, array $rows, array $colWidths = []): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'akru_xlsx_') . '.xlsx';
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
  <fonts count="2">
    <font><sz val="11"/><name val="Calibri"/><color rgb="000000"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFF"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="1E293B"/></patternFill></fill>
  </fills>
  <borders count="1">
    <border><left/><right/><top/><bottom/><diagonal/></border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
  </cellXfs>
</styleSheet>');

        // Build worksheet xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

        // Column widths
        if (!empty($colWidths)) {
            $sheetXml .= '<cols>';
            foreach ($colWidths as $i => $w) {
                $colNum = $i + 1;
                $sheetXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $w . '" customWidth="1"/>';
            }
            $sheetXml .= '</cols>' . "\n";
        }

        $sheetXml .= '<sheetData>' . "\n";

        // Header row (Style 1)
        $sheetXml .= '<row r="1" ht="28" customHeight="1">';
        foreach ($headers as $cIdx => $header) {
            $colLetter = $this->colIndexToLetter($cIdx);
            $cellRef = $colLetter . '1';
            $escaped = htmlspecialchars((string)$header, ENT_XML1);
            $sheetXml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
        }
        $sheetXml .= '</row>' . "\n";

        // Data rows (Style 0)
        foreach ($rows as $rIdx => $row) {
            $rowNum = $rIdx + 2;
            $sheetXml .= '<row r="' . $rowNum . '">';
            foreach (array_values($row) as $cIdx => $val) {
                $colLetter = $this->colIndexToLetter($cIdx);
                $cellRef = $colLetter . $rowNum;
                $escaped = htmlspecialchars((string)$val, ENT_XML1);
                $sheetXml .= '<c r="' . $cellRef . '" s="0" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetXml .= '</row>' . "\n";
        }

        $sheetXml .= '</sheetData>' . "\n";
        $sheetXml .= '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return $tmp;
    }

    /**
     * Convert zero-based column index to Excel column letter (0->A, 25->Z, 26->AA)
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
