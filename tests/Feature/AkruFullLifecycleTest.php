<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Workflow\Models\ApprovalRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AkruFullLifecycleTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $this->user = User::where('email', 'owner@akru.id')->firstOrFail();
        $this->company = Company::firstOrFail();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('AKRU');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->id])
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Kontrol Bisnis');
    }

    public function test_authenticated_user_can_view_master_data_pages(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $this->actingAs($this->user)->withSession($session)->get('/coa')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/customers')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/suppliers')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/products')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/bank-accounts')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/tax-codes')->assertStatus(200);
    }

    public function test_authenticated_user_can_view_sales_pages(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $this->actingAs($this->user)->withSession($session)->get('/sales')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/sales/create')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/receipts')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/receipts/create')->assertStatus(200);
    }

    public function test_authenticated_user_can_view_purchase_pages(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $this->actingAs($this->user)->withSession($session)->get('/purchases')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/purchases/create')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/payments')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/payments/create')->assertStatus(200);
    }

    public function test_authenticated_user_can_view_finance_pages(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $this->actingAs($this->user)->withSession($session)->get('/finance/cash-bank')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/finance/cash-bank/create')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/finance/transfers')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/finance/transfers/create')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/finance/reconciliation')->assertStatus(200);
    }

    public function test_authenticated_user_can_view_inventory_page(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $response = $this->actingAs($this->user)->withSession($session)->get('/inventory');
        $response->assertStatus(200);
        $response->assertSee('Persediaan &amp; Kartu Stok', false);
    }

    public function test_authenticated_user_can_view_accounting_and_ledger_pages(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $this->actingAs($this->user)->withSession($session)->get('/journals')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/manual-journals/create')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/general-ledger')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/trial-balance')->assertStatus(200);
    }

    public function test_authenticated_user_can_view_reports(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $this->actingAs($this->user)->withSession($session)->get('/reports/profit-loss')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/reports/balance-sheet')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/reports/cash-flow')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/reports/aging')->assertStatus(200);
    }

    public function test_authenticated_user_can_view_tax_control_pages(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $this->actingAs($this->user)->withSession($session)->get('/tax/ppn')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/tax/pph')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/tax/fiscal')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/tax/coretax')->assertStatus(200);

        // Test XML export response
        $xmlResponse = $this->actingAs($this->user)->withSession($session)->get('/tax/coretax?format=xml');
        $xmlResponse->assertHeader('Content-Type', 'application/xml');
    }

    public function test_authenticated_user_can_view_audit_and_workflow_pages(): void
    {
        $session = ['active_company_id' => $this->company->id];

        $this->actingAs($this->user)->withSession($session)->get('/audit')->assertStatus(200);
        $this->actingAs($this->user)->withSession($session)->get('/workflow')->assertStatus(200);
    }

    public function test_anti_self_approval_enforcement(): void
    {
        $request = ApprovalRequest::create([
            'company_id' => $this->company->id,
            'document_type' => 'purchase_invoice',
            'document_id' => 999,
            'requester_id' => $this->user->id,
            'status' => 'pending',
            'notes' => 'Test approval',
        ]);

        // User trying to approve own request
        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->id])
            ->post("/workflow/{$request->id}/approve");

        $response->assertSessionHas('error');
        $this->assertEquals('pending', $request->fresh()->status);
    }
}
