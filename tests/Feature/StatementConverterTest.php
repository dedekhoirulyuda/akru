<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\Core\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatementConverterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $this->user = User::where('email', 'owner@akru.id')->firstOrFail();
        $this->company = Company::firstOrFail();

        $account = \App\Modules\MasterData\Models\Account::where('company_id', $this->company->id)
            ->where('subtype', 'CASH_AND_BANK')
            ->first() ?? \App\Modules\MasterData\Models\Account::create([
                'company_id' => $this->company->id,
                'code' => '1119',
                'name' => 'Bank BCA Operasional Test',
                'type' => 'ASSET',
                'subtype' => 'CASH_AND_BANK',
                'is_active' => true,
            ]);

        $this->bankAccount = BankAccount::firstOrCreate(
            ['company_id' => $this->company->id, 'account_number' => '1234567890'],
            [
                'account_id' => $account->id,
                'bank_name' => 'BCA',
                'is_active' => true,
            ]
        );
    }

    public function test_user_can_view_converter_page(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->get(route('converter.index'));

        $response->assertStatus(200);
        $response->assertSee('Konverter Rekening Koran Bank');
        $response->assertSee('PDF to Excel Multi-Bank');
    }

    public function test_user_can_convert_bca_statement_format(): void
    {
        $bcaText = <<<TXT
        NO. REKENING : 1234567890
        SALDO AWAL : 10,000,000.00
        01/09 TRSF E-BANKING CR 0109/FTSCY/WS95011 PEMBAYARAN KLIEN PT ALFA 15,000,000.00 CR 25,000,000.00
        02/09 BIAYA ADM BULANAN REKENING 25,000.00 DB 24,975,000.00
        05/09 TARIKAN TUNAI ATM 0509 500,000.00 DB 24,475,000.00
        TXT;

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->post(route('converter.convert'), [
                'bank_account_id' => $this->bankAccount->id,
                'bank_type' => 'bca',
                'raw_text' => $bcaText,
            ]);

        $response->assertSessionHas('parsedRows');
        $response->assertSessionHas('summary');

        $rows = session('parsedRows');
        $this->assertCount(3, $rows);

        // Row 1: Credit 15,000,000
        $this->assertEquals(15000000.0, $rows[0]['credit']);
        $this->assertEquals(0.0, $rows[0]['debit']);
        $this->assertStringContainsString('PEMBAYARAN KLIEN', $rows[0]['description']);

        // Row 2: Debit 25,000
        $this->assertEquals(25000.0, $rows[1]['debit']);
        $this->assertEquals(0.0, $rows[1]['credit']);
        $this->assertStringContainsString('BIAYA ADM BULANAN', $rows[1]['description']);

        // Row 3: Debit 500,000
        $this->assertEquals(500000.0, $rows[2]['debit']);
    }

    public function test_user_can_export_converted_statement_to_excel(): void
    {
        $rowsData = [
            [
                'date' => '2026-09-01',
                'description' => 'Transfer Masuk Invoice #101',
                'debit' => 0,
                'credit' => 10000000,
                'balance' => 20000000,
                'status' => 'OK',
            ],
            [
                'date' => '2026-09-02',
                'description' => 'Pembayaran Beban Listrik & Internet',
                'debit' => 1500000,
                'credit' => 0,
                'balance' => 18500000,
                'status' => 'OK',
            ]
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->post(route('converter.export.excel'), [
                'bank_name' => 'BCA 1234567890',
                'rows_data' => json_encode($rowsData),
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));
    }

    public function test_user_can_export_converted_statement_to_csv(): void
    {
        $rowsData = [
            [
                'date' => '2026-09-01',
                'description' => 'Transfer Masuk',
                'debit' => 0,
                'credit' => 5000000,
                'balance' => 5000000,
                'status' => 'OK',
            ]
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->post(route('converter.export.csv'), [
                'bank_name' => 'BCA',
                'rows_data' => json_encode($rowsData),
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
