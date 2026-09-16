<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Accounting\Models\JournalSet;
use App\Modules\Ai\Models\AiAnomalyFinding;
use App\Modules\Ai\Models\AiSuggestion;
use App\Modules\Core\Models\Company;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\Sales\Models\SalesInvoice;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AkruAiModuleTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);
        Artisan::call('db:seed', ['--class' => 'AkruAiSeeder']);
        Artisan::call('db:seed', ['--class' => 'RegulationSeeder']);

        $this->user = User::where('email', 'owner@akru.id')->firstOrFail();
        $this->company = Company::firstOrFail();
    }

    protected function actingAsTenant()
    {
        return $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ]);
    }

    /**
     * Test 1: Vertical Slice 1 - User asks for omzet and laba
     */
    public function test_vertical_slice_omzet_and_profit_query()
    {
        $contact = \App\Modules\MasterData\Models\Contact::where('company_id', $this->company->id)->first();

        // 1. Create a posted sales invoice
        SalesInvoice::create([
            'company_id' => $this->company->id,
            'contact_id' => $contact->id,
            'invoice_number' => 'INV-AI-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 15000000,
            'tax_amount' => 1650000,
            'status' => 'posted',
        ]);

        $response = $this->actingAsTenant()->postJson(route('ai.chat'), [
            'message' => 'Berapa omzet penjualan dan laba bulan ini?',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['reply']);
        $this->assertEquals('sales_and_profitability', $data['structured_response']['intent']);
        $this->assertGreaterThanOrEqual(15000000, $data['metrics'][0]['value']);
        $this->assertEquals('akru_native', $data['provider']['provider']);
        $this->assertNotEmpty($data['citations']);
    }

    /**
     * Test 2: Cash & Bank query
     */
    public function test_cash_and_bank_query()
    {
        $bankAccount = BankAccount::where('company_id', $this->company->id)->firstOrFail();
        $equityAccount = Account::where('company_id', $this->company->id)->where('type', 'equity')->firstOrFail();

        $journal = JournalSet::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JV-INIT-BANK',
            'journal_date' => now()->toDateString(),
            'source_type' => 'manual',
            'status' => 'posted',
            'posted_at' => now(),
            'description' => 'Setor Modal Awal Bank',
        ]);

        \App\Modules\Accounting\Models\JournalLine::create([
            'journal_set_id' => $journal->id,
            'account_id' => $bankAccount->account_id,
            'debit' => 50000000,
            'credit' => 0,
            'description' => 'Setor Modal Bank',
        ]);

        \App\Modules\Accounting\Models\JournalLine::create([
            'journal_set_id' => $journal->id,
            'account_id' => $equityAccount->id,
            'debit' => 0,
            'credit' => 50000000,
            'description' => 'Modal Disetor',
        ]);

        $response = $this->actingAsTenant()->postJson(route('ai.chat'), [
            'message' => 'Berapa saldo kas dan bank saat ini?',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertStringContainsString('Bank Central Asia (BCA)', $data['reply']);
        $this->assertGreaterThan(50000000, $data['metrics'][0]['value']);
        $this->assertEquals('cash_position', $data['structured_response']['intent']);
    }

    /**
     * Test 3: Multi-tenant data isolation
     */
    public function test_multi_tenant_data_isolation()
    {
        // Create second company
        $company2 = Company::create([
            'name' => 'PT Entitas Tetangga',
            'legal_name' => 'PT Entitas Tetangga Sejahtera',
            'tax_id' => '02.345.678.9-012.000',
            'email' => 'finance@tetangga.com',
            'phone' => '021-998877',
            'address' => 'Jl. Tetangga No. 2',
        ]);

        $contact2 = \App\Modules\MasterData\Models\Contact::create([
            'company_id' => $company2->id,
            'type' => 'customer',
            'name' => 'Customer Tetangga',
        ]);

        // Put secret invoice in Company 2
        SalesInvoice::create([
            'company_id' => $company2->id,
            'contact_id' => $contact2->id,
            'invoice_number' => 'INV-SECRET-COMP2',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 999000000,
            'status' => 'posted',
        ]);

        // Ask from Company 1 context
        $response = $this->actingAsTenant()->postJson(route('ai.chat'), [
            'message' => 'Berapa omzet penjualan bulan ini?',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        // Company 1 must NOT see Company 2's 999.000.000 invoice!
        $this->assertStringNotContainsString('999.000.000', $reply);
    }

    /**
     * Test 4: Anomaly scan execution and finding capture
     */
    public function test_anomaly_scan_execution()
    {
        $contact = \App\Modules\MasterData\Models\Contact::where('company_id', $this->company->id)->first();

        // Create two identical sales invoices on same day to trigger duplicate rule
        SalesInvoice::create([
            'company_id' => $this->company->id,
            'contact_id' => $contact->id,
            'invoice_number' => 'INV-DUP-A',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 7750000,
            'status' => 'posted',
        ]);

        SalesInvoice::create([
            'company_id' => $this->company->id,
            'contact_id' => $contact->id,
            'invoice_number' => 'INV-DUP-B',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 7750000,
            'status' => 'posted',
        ]);

        $response = $this->actingAsTenant()->postJson(route('ai.anomalies.scan'));

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, count($data['findings']));

        $this->assertDatabaseHas('ai_anomaly_findings', [
            'company_id' => $this->company->id,
            'category' => 'duplicate',
        ]);
    }

    /**
     * Test 5: Human-in-the-Loop Suggestion conversion strictly creates DRAFT
     */
    public function test_suggestion_conversion_strictly_creates_draft_never_auto_posts()
    {
        $accExpense = Account::where('company_id', $this->company->id)->where('type', 'expense')->firstOrFail();
        $accBank = Account::where('company_id', $this->company->id)->where('type', 'asset')->firstOrFail();

        $suggestion = AiSuggestion::create([
            'company_id' => $this->company->id,
            'suggestion_type' => 'journal_draft',
            'payload_json' => [
                'journal_date' => now()->toDateString(),
                'description' => 'Penyesuaian Biaya Operasional',
                'lines' => [
                    ['account_id' => $accExpense->id, 'debit' => 500000, 'credit' => 0],
                    ['account_id' => $accBank->id, 'debit' => 0, 'credit' => 500000],
                ],
            ],
            'reason_summary' => 'Saran penyesuaian akhir bulan',
            'confidence' => 0.95,
            'risk_level' => 'low',
            'status' => 'awaiting_review',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAsTenant()->postJson(route('ai.suggestions.convert-to-draft', $suggestion->id));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);

        // CRITICAL GUARDRAIL VERIFICATION: Status must be 'draft', NEVER 'posted'!
        $journal = JournalSet::find($data['draft_id']);
        $this->assertNotNull($journal);
        $this->assertEquals('draft', $journal->status);
        $this->assertNotEquals('posted', $journal->status);
    }

    /**
     * Test 6: Import purchase taxation query and regulations catalog
     */
    public function test_import_purchase_taxation_query_and_regulations_catalog()
    {
        // 1. AI Chat query for import purchase taxes
        $response = $this->actingAsTenant()->postJson(route('ai.chat'), [
            'message' => 'jika saya pembelian impor, komponen pajak apa saja yang muncul?',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('import_taxation', $data['structured_response']['intent']);
        $this->assertStringContainsString('Bea Masuk', $data['reply']);
        $this->assertStringContainsString('PPN Impor', $data['reply']);
        $this->assertStringContainsString('PPh Pasal 22', $data['reply']);
        $this->assertStringContainsString('2,5%', $data['reply']);
        $this->assertNotEmpty($data['citations']);

        // 2. Access regulations catalog
        $catalogResponse = $this->actingAsTenant()->get(route('regulations.index'));
        $catalogResponse->assertStatus(200)
            ->assertSee('Pusat Peraturan & Standar Kepatuhan')
            ->assertSee('Akuntansi (SAK/PSAK)')
            ->assertSee('Kepabeanan & Impor');

        // 3. Search regulations catalog for 'impor'
        $searchResponse = $this->actingAsTenant()->get(route('regulations.index', ['q' => 'impor']));
        $searchResponse->assertStatus(200)
            ->assertSee('PMK No. 41/PMK.010/2022')
            ->assertSee('UU No. 17 Tahun 2006');
    }

    /**
     * Test 7: System user guide query
     */
    public function test_system_user_guide_query()
    {
        $response = $this->actingAsTenant()->postJson(route('ai.chat'), [
            'message' => 'cara penggunaan akru',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('system_user_guide', $data['structured_response']['intent']);
        $this->assertStringContainsString('Panduan Alur Kerja & Cara Penggunaan Sistem AKRU', $data['reply']);
        $this->assertStringContainsString('Setup & Master Data Awal', $data['reply']);
        $this->assertStringContainsString('Siklus Pengadaan & Pembelian', $data['reply']);
        $this->assertStringContainsString('Siklus Penjualan & Pendapatan', $data['reply']);
        $this->assertStringContainsString('Manajemen Keuangan, Kas Kecil', $data['reply']);
        $this->assertStringContainsString('Kepatuhan Pajak, Laporan Keuangan', $data['reply']);
        $this->assertNotEmpty($data['recommendations']);
    }
}

