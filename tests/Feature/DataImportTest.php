<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use App\Services\Import\DataImportService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use ZipArchive;

class DataImportTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;
    protected DataImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $this->user = User::where('email', 'owner@akru.id')->firstOrFail();
        $this->company = Company::firstOrFail();
        $this->importService = new DataImportService();
    }

    public function test_can_download_all_import_templates_as_xlsx(): void
    {
        $session = [
            'active_company_id' => $this->company->id,
            'current_company_id' => $this->company->id,
        ];

        // 1. COA Template (.xlsx)
        $resCoa = $this->actingAs($this->user)->withSession($session)->get(route('coa.template'));
        $resCoa->assertStatus(200);
        $resCoa->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertValidXlsxWithContent($resCoa->getContent(), 'Kode Akun');

        // 2. Customers Template (.xlsx)
        $resCust = $this->actingAs($this->user)->withSession($session)->get(route('customers.template'));
        $resCust->assertStatus(200);
        $resCust->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertValidXlsxWithContent($resCust->getContent(), 'Kode Pelanggan');

        // 3. Suppliers Template (.xlsx)
        $resSupp = $this->actingAs($this->user)->withSession($session)->get(route('suppliers.template'));
        $resSupp->assertStatus(200);
        $resSupp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertValidXlsxWithContent($resSupp->getContent(), 'Kode Pemasok');

        // 4. Products Template (.xlsx)
        $resProd = $this->actingAs($this->user)->withSession($session)->get(route('products.template'));
        $resProd->assertStatus(200);
        $resProd->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertValidXlsxWithContent($resProd->getContent(), 'SKU');
    }

    public function test_can_import_coa_from_excel_xlsx(): void
    {
        $session = [
            'active_company_id' => $this->company->id,
            'current_company_id' => $this->company->id,
        ];

        $headers = [
            'Kode Akun',
            'Nama Akun',
            'Kategori Akun (Aset/Kewajiban/Ekuitas/Pendapatan/HPP/Beban)',
            'Saldo Normal (Debit/Kredit)',
            'Sub Klasifikasi',
            'Catatan / Deskripsi',
        ];

        $rows = [
            ['1150', 'Kas Kecil Cabang Barat', 'Aset', 'Debit', 'Kas & Bank', 'Dana operasional kas kecil'],
            ['2130', 'Utang Gaji Karyawan', 'Kewajiban', 'Kredit', 'Kewajiban Lancar', 'Akrual beban gaji bulanan'],
            ['6210', 'Beban Internet & Cloud', 'Beban', 'Debit', 'Beban Operasional', 'Biaya langganan server'],
        ];

        $filePath = $this->importService->createStyledXlsxFile($headers, $rows);
        $file = new UploadedFile($filePath, 'coa_template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->user)
            ->withSession($session)
            ->post(route('coa.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('coa.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'code' => '1150',
            'name' => 'Kas Kecil Cabang Barat',
            'type' => 'asset',
            'normal_balance' => 'debit',
        ]);

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'code' => '2130',
            'name' => 'Utang Gaji Karyawan',
            'type' => 'liability',
            'normal_balance' => 'credit',
        ]);

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'code' => '6210',
            'name' => 'Beban Internet & Cloud',
            'type' => 'expense',
        ]);

        @unlink($filePath);
    }

    public function test_can_import_customers_from_excel_xlsx(): void
    {
        $session = [
            'active_company_id' => $this->company->id,
            'current_company_id' => $this->company->id,
        ];

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

        $rows = [
            ['CUST-XLS-01', 'PT Cipta Kreasi Mandiri', 'NPWP', '01.234.567.8-012.000', 'info@ciptakreasi.co.id', '021-88997766', 'Jl. Sudirman No 45', 'Jakarta Selatan', '30', '50000000'],
            ['CUST-XLS-02', 'Ibu Siti Rahmawati', 'NIK', '3271012345670001', 'siti.r@gmail.com', '081234567890', 'Jl. Pajajaran No 12', 'Bogor', '0', '0'],
        ];

        $filePath = $this->importService->createStyledXlsxFile($headers, $rows);
        $file = new UploadedFile($filePath, 'customers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->user)
            ->withSession($session)
            ->post(route('customers.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contacts', [
            'company_id' => $this->company->id,
            'code' => 'CUST-XLS-01',
            'name' => 'PT Cipta Kreasi Mandiri',
            'type' => 'customer',
            'payment_terms_days' => 30,
            'credit_limit' => 50000000,
        ]);

        $this->assertDatabaseHas('contacts', [
            'company_id' => $this->company->id,
            'code' => 'CUST-XLS-02',
            'name' => 'Ibu Siti Rahmawati',
            'type' => 'customer',
            'city' => 'Bogor',
        ]);

        @unlink($filePath);
    }

    public function test_can_import_suppliers_from_excel_xlsx(): void
    {
        $session = [
            'active_company_id' => $this->company->id,
            'current_company_id' => $this->company->id,
        ];

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

        $rows = [
            ['SUPP-XLS-01', 'PT Mitra Logistik Nusantara', 'NPWP', '02.345.678.9-023.000', 'procurement@mitralog.id', '021-77889900', 'Kawasan Industri MM2100', 'Bekasi', '45'],
            ['SUPP-XLS-02', 'CV Sumber Kertas Sejahtera', 'NIK', '3172023456780002', 'sumberkertas@yahoo.com', '081399887766', 'Jl. Daan Mogot Km 11', 'Jakarta Barat', '14'],
        ];

        $filePath = $this->importService->createStyledXlsxFile($headers, $rows);
        $file = new UploadedFile($filePath, 'suppliers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->user)
            ->withSession($session)
            ->post(route('suppliers.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('suppliers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contacts', [
            'company_id' => $this->company->id,
            'code' => 'SUPP-XLS-01',
            'name' => 'PT Mitra Logistik Nusantara',
            'type' => 'supplier',
            'payment_terms_days' => 45,
        ]);

        $this->assertDatabaseHas('contacts', [
            'company_id' => $this->company->id,
            'code' => 'SUPP-XLS-02',
            'name' => 'CV Sumber Kertas Sejahtera',
            'type' => 'supplier',
            'city' => 'Jakarta Barat',
        ]);

        @unlink($filePath);
    }

    public function test_can_import_products_from_excel_xlsx(): void
    {
        $session = [
            'active_company_id' => $this->company->id,
            'current_company_id' => $this->company->id,
        ];

        $salesAcc = Account::where('company_id', $this->company->id)->where('type', 'revenue')->first();
        $cogsAcc = Account::where('company_id', $this->company->id)->where('type', 'cogs')->first();

        $salesCode = $salesAcc ? $salesAcc->code : '4100';
        $cogsCode = $cogsAcc ? $cogsAcc->code : '5100';

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

        $rows = [
            ['PRD-XLS-01', 'Monitor Ultra HD 27 inch', 'Barang', '2500000', '3500000', 'Ya', $salesCode, $cogsCode, 'Monitor 4K IPS'],
            ['SRV-XLS-01', 'Jasa Audit Sistem Keuangan', 'Jasa', '0', '15000000', 'Tidak', $salesCode, '', 'Audit keuangan akuntansi'],
        ];

        $filePath = $this->importService->createStyledXlsxFile($headers, $rows);
        $file = new UploadedFile($filePath, 'products.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->user)
            ->withSession($session)
            ->post(route('products.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('items', [
            'company_id' => $this->company->id,
            'sku' => 'PRD-XLS-01',
            'name' => 'Monitor Ultra HD 27 inch',
            'type' => 'goods',
            'buy_price' => 2500000,
            'sell_price' => 3500000,
            'is_stockable' => true,
        ]);

        $this->assertDatabaseHas('items', [
            'company_id' => $this->company->id,
            'sku' => 'SRV-XLS-01',
            'name' => 'Jasa Audit Sistem Keuangan',
            'type' => 'service',
            'sell_price' => 15000000,
            'is_stockable' => false,
        ]);

        @unlink($filePath);
    }

    public function test_can_still_import_from_csv_for_backward_compatibility(): void
    {
        $session = [
            'active_company_id' => $this->company->id,
            'current_company_id' => $this->company->id,
        ];

        $csvContent = "Kode Akun,Nama Akun,Kategori Akun,Saldo Normal,Sub Klasifikasi,Catatan\n" .
                      "1160,Kas Kasir Cabang Timur,Aset,Debit,Kas & Bank,Kasir operasional shift";

        $file = UploadedFile::fake()->createWithContent('coa_import.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->withSession($session)
            ->post(route('coa.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('coa.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'code' => '1160',
            'name' => 'Kas Kasir Cabang Timur',
            'type' => 'asset',
        ]);
    }

    /**
     * Helper to verify binary content is a valid XLSX archive containing a specific string
     */
    protected function assertValidXlsxWithContent(string $binaryContent, string $expectedString): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_verify_xlsx');
        file_put_contents($tmp, $binaryContent);

        $zip = new ZipArchive();
        $isOpen = $zip->open($tmp);
        $this->assertTrue($isOpen === true, 'Failed to open downloaded file as a valid XLSX ZIP archive.');

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml, 'Worksheet xl/worksheets/sheet1.xml was not found in the XLSX archive.');
        $this->assertStringContainsString($expectedString, $sheetXml);

        $zip->close();
        @unlink($tmp);
    }
}
