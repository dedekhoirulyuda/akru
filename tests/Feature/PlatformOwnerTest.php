<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Platform\Models\PlatformSetting;
use App\Modules\Platform\Models\SubscriptionInvoice;
use App\Modules\Subscription\Models\Plan;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformOwnerTest extends TestCase
{
    use DatabaseMigrations;

    protected User $superAdmin;
    protected User $regularUser;
    protected Company $company;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $this->superAdmin = User::where('email', 'owner@akru.id')->first();
        $this->regularUser = User::where('email', 'finance@akru.id')->first();
        $this->company = Company::first();
        $this->plan = Plan::first();

        // Ensure superadmin flag
        $this->superAdmin->update(['is_superadmin' => true]);
        $this->regularUser->update(['is_superadmin' => false]);
    }

    public function test_non_superadmin_cannot_access_platform_portal(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/platform');
        $response->assertStatus(403);
    }

    public function test_superadmin_can_access_executive_dashboard(): void
    {
        // Create sample paid and unpaid invoices
        \App\Modules\Platform\Models\SubscriptionInvoice::create([
            'invoice_number' => 'INV-TEST-001',
            'company_id' => $this->company->id,
            'plan_id' => $this->plan->id,
            'amount' => 500000,
            'status' => 'paid',
            'due_date' => now()->addDays(7),
            'paid_at' => now(),
            'payment_method' => 'bank_transfer',
        ]);

        \App\Modules\Platform\Models\SubscriptionInvoice::create([
            'invoice_number' => 'INV-TEST-002',
            'company_id' => $this->company->id,
            'plan_id' => $this->plan->id,
            'amount' => 250000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
            'payment_method' => 'bank_transfer',
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/platform');

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Eksekutif Platform');
        $response->assertSee('MRR (Monthly Recurring)');
        $response->assertSee('Perusahaan Terdaftar');

        // Verify newly requested revenue & plan summary metrics
        $response->assertSee('Total Pendapatan Bulanan');
        $response->assertSee('Rp 500.000'); // monthly paid
        $response->assertSee('Pendapatan Belum Terbayar');
        $response->assertSee('Detail Kinerja & Pendapatan per Paket Langganan', false);
        $response->assertSee('Total Seluruh Paket Langganan');
    }

    public function test_superadmin_can_manage_companies(): void
    {
        // 1. List companies
        $response = $this->actingAs($this->superAdmin)->get('/platform/companies');
        $response->assertStatus(200);
        $response->assertSee($this->company->name);

        // 2. Create new company
        $plan = Plan::first();
        $createResponse = $this->actingAs($this->superAdmin)->post('/platform/companies', [
            'name' => 'PT Solusi Finansial Global',
            'legal_name' => 'PT Solusi Finansial Global Tbk',
            'entity_type' => 'PT',
            'email' => 'contact@solusiglobal.co.id',
            'phone' => '021-99887766',
            'city' => 'Surabaya',
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $createResponse->assertRedirect(route('platform.companies.index'));
        $this->assertDatabaseHas('companies', ['name' => 'PT Solusi Finansial Global']);

        $newCompany = Company::where('name', 'PT Solusi Finansial Global')->first();

        // 3. Show company detail
        $showResponse = $this->actingAs($this->superAdmin)->get("/platform/companies/{$newCompany->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('PT Solusi Finansial Global');

        // 4. Update company status to suspended
        $statusResponse = $this->actingAs($this->superAdmin)->patch("/platform/companies/{$newCompany->id}/status", [
            'status' => 'suspended',
            'suspended_reason' => 'Menunggu verifikasi pembayaran',
        ]);
        $statusResponse->assertSessionHas('success');
        $this->assertEquals('suspended', $newCompany->fresh()->status);
        $this->assertEquals('Menunggu verifikasi pembayaran', $newCompany->fresh()->suspended_reason);

        // 5. Update quota override
        $quotaResponse = $this->actingAs($this->superAdmin)->patch("/platform/companies/{$newCompany->id}/quota", [
            'max_users_override' => 25,
            'max_branches_override' => 5,
            'max_transactions_override' => 15000,
        ]);
        $quotaResponse->assertSessionHas('success');
        $this->assertEquals(25, $newCompany->fresh()->max_users_override);
    }

    public function test_superadmin_can_manage_users_and_impersonate(): void
    {
        // 1. List users
        $response = $this->actingAs($this->superAdmin)->get('/platform/users');
        $response->assertStatus(200);
        $response->assertSee($this->regularUser->email);

        // 2. Reset user password
        $resetResponse = $this->actingAs($this->superAdmin)->post("/platform/users/{$this->regularUser->id}/reset-password", [
            'new_password' => 'newpassword123',
        ]);
        $resetResponse->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $this->regularUser->fresh()->password));

        // 3. Toggle Super Admin
        $promoteResponse = $this->actingAs($this->superAdmin)->post("/platform/users/{$this->regularUser->id}/superadmin");
        $promoteResponse->assertSessionHas('success');
        $this->assertTrue($this->regularUser->fresh()->is_superadmin);

        // 4. Impersonate user
        $impersonateResponse = $this->actingAs($this->superAdmin)
            ->post("/platform/users/{$this->regularUser->id}/impersonate");
        
        $impersonateResponse->assertRedirect(route('dashboard.index'));
        $this->assertEquals($this->regularUser->id, auth()->id());
        $this->assertTrue(session()->has('impersonated_by'));

        // 5. Stop impersonate
        $stopResponse = $this->post('/platform/impersonate/stop');
        $stopResponse->assertRedirect(route('platform.users.index'));
        $this->assertEquals($this->superAdmin->id, auth()->id());
        $this->assertFalse(session()->has('impersonated_by'));
    }

    public function test_superadmin_can_manage_plans(): void
    {
        // 1. List plans
        $response = $this->actingAs($this->superAdmin)->get('/platform/plans');
        $response->assertStatus(200);
        $response->assertSee('Katalog Paket', false);

        // 2. Create new plan
        $createPlanResponse = $this->actingAs($this->superAdmin)->post('/platform/plans', [
            'name' => 'AKRU Growth Special',
            'slug' => 'growth-special',
            'description' => 'Paket akselerasi bisnis berkembang',
            'price_per_month' => 450000,
            'price_per_year' => 4500000,
            'max_users' => 15,
            'max_branches' => 5,
            'max_transactions_per_month' => 10000,
            'has_ai' => true,
        ]);

        $createPlanResponse->assertRedirect(route('platform.plans.index'));
        $this->assertDatabaseHas('plans', ['slug' => 'growth-special']);

        $plan = Plan::where('slug', 'growth-special')->first();

        // 3. Update plan
        $updatePlanResponse = $this->actingAs($this->superAdmin)->put("/platform/plans/{$plan->id}", [
            'name' => 'AKRU Growth Premium',
            'slug' => 'growth-special',
            'description' => 'Paket akselerasi bisnis update',
            'price_per_month' => 499000,
            'price_per_year' => 4990000,
            'max_users' => 20,
            'max_branches' => 5,
            'max_transactions_per_month' => 15000,
            'has_ai' => true,
        ]);
        $updatePlanResponse->assertRedirect(route('platform.plans.index'));
        $this->assertEquals(499000, $plan->fresh()->price_per_month);
    }

    public function test_superadmin_can_manage_subscriptions_and_invoices(): void
    {
        // 1. View subscriptions page
        $response = $this->actingAs($this->superAdmin)->get('/platform/subscriptions');
        $response->assertStatus(200);
        $response->assertSee('Manajemen Langganan', false);

        $subscription = Subscription::where('company_id', $this->company->id)->first();
        $originalEnd = $subscription->ends_at;

        // 2. Extend subscription
        $extendResponse = $this->actingAs($this->superAdmin)->post("/platform/subscriptions/{$subscription->id}/extend", [
            'months' => 6,
            'status' => 'active',
        ]);
        $extendResponse->assertSessionHas('success');
        $this->assertTrue($subscription->fresh()->ends_at->isAfter($originalEnd));

        // 3. Issue subscription invoice
        $plan = Plan::first();
        $invoiceResponse = $this->actingAs($this->superAdmin)->post('/platform/invoices', [
            'company_id' => $this->company->id,
            'plan_id' => $plan->id,
            'amount' => 500000,
            'billing_cycle' => 'monthly',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'notes' => 'Tagihan langganan bulanan B2B',
        ]);
        $invoiceResponse->assertSessionHas('success');
        $invoice = SubscriptionInvoice::where('company_id', $this->company->id)->latest()->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('unpaid', $invoice->status);

        // 4. Mark invoice as paid
        $payResponse = $this->actingAs($this->superAdmin)->post("/platform/invoices/{$invoice->id}/pay", [
            'payment_method' => 'manual_transfer',
            'payment_reference' => 'BCA-TRF-998877',
        ]);
        $payResponse->assertSessionHas('success');
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals('BCA-TRF-998877', $invoice->fresh()->payment_reference);
    }

    public function test_superadmin_can_manage_modules(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/platform/modules');
        $response->assertStatus(200);
        $response->assertSee('Katalog Modul', false);

        // Toggle global module
        $toggleResponse = $this->actingAs($this->superAdmin)->post('/platform/modules/toggle/ai');
        $toggleResponse->assertSessionHas('success');
    }

    public function test_superadmin_can_manage_api_and_developer_tokens(): void
    {
        // 1. View API page
        $response = $this->actingAs($this->superAdmin)->get('/platform/api-integrations');
        $response->assertStatus(200);
        $response->assertSee('Koneksi API & Integrasi Platform');

        // 2. Update AI credentials
        $settingsResponse = $this->actingAs($this->superAdmin)->post('/platform/api-integrations/settings', [
            'group' => 'ai',
            'ai_provider' => 'gemini',
            'ai_default_model' => 'gemini-1.5-flash',
            'ai_gemini_api_key' => 'AIzaSyTestKey1234567890',
        ]);
        $settingsResponse->assertSessionHas('success');
        $this->assertEquals('AIzaSyTestKey1234567890', PlatformSetting::get('ai_gemini_api_key'));

        // 3. Generate Developer Token
        $tokenResponse = $this->actingAs($this->superAdmin)->post('/platform/api-integrations/tokens', [
            'name' => 'Mobile App Integration Key',
            'expires_days' => 90,
        ]);
        $tokenResponse->assertSessionHas('new_token_plain');
        $this->assertDatabaseHas('platform_api_tokens', ['name' => 'Mobile App Integration Key']);
    }
}
