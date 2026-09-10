<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Models\FixedAsset;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\Identity\Models\Role;
use App\Modules\MasterData\Models\Account;
use App\Modules\Subscription\Models\Plan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MvpRemainingFeaturesTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $this->user = User::where('email', 'owner@akru.id')->first();
        $this->company = Company::first();
    }

    public function test_users_management_page_and_store(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/users');

        $response->assertStatus(200);
        $response->assertSee('Manajemen Pengguna & Hak Akses');

        $role = Role::first();
        $storeResp = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->post('/users', [
                'name' => 'Akuntan Junior',
                'email' => 'akuntan.junior@akru.id',
                'role_id' => $role->id,
                'password' => 'password123',
            ]);

        $storeResp->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'akuntan.junior@akru.id']);
    }

    public function test_subscription_and_quota_page(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/subscription');

        $response->assertStatus(200);
        $response->assertSee('Paket Langganan & Kuota SaaS');

        $plan = Plan::where('slug', 'enterprise')->first();
        if ($plan) {
            $switchResp = $this->actingAs($this->user)
                ->withSession([
                    'active_company_id' => $this->company->id,
                    'current_company_id' => $this->company->id,
                ])
                ->post('/subscription/plan', [
                    'plan_id' => $plan->id,
                ]);

            $switchResp->assertRedirect('/subscription');
        }
    }

    public function test_fixed_assets_register_and_depreciation(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/assets');

        $response->assertStatus(200);
        $response->assertSee('Buku Register Aset Tetap');

        // Register new asset
        $assetAcc = Account::where('company_id', $this->company->id)->where('code', 'like', '1%')->first();
        $expenseAcc = Account::where('company_id', $this->company->id)->where('code', 'like', '6%')->first();
        $branch = Branch::where('company_id', $this->company->id)->first();

        $createResp = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->post('/assets', [
                'asset_code' => 'AST-TEST-001',
                'name' => 'Laptop MacBook M3 Test',
                'branch_id' => $branch?->id,
                'acquisition_date' => '2026-01-01',
                'acquisition_cost' => 24000000,
                'useful_life_years' => 4,
                'salvage_value' => 0,
                'asset_account_id' => $assetAcc->id,
                'accumulated_depreciation_account_id' => $assetAcc->id,
                'depreciation_expense_account_id' => $expenseAcc->id,
            ]);

        $createResp->assertRedirect('/assets');
        $this->assertDatabaseHas('fixed_assets', [
            'company_id' => $this->company->id,
            'asset_code' => 'AST-TEST-001',
        ]);

        $asset = FixedAsset::where('asset_code', 'AST-TEST-001')->first();
        $deprecResp = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->post("/assets/{$asset->id}/depreciate");

        $deprecResp->assertRedirect('/assets');
    }

    public function test_closing_workbench_close_and_reopen(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/closing');

        $response->assertStatus(200);
        $response->assertSee('Closing Workbench');

        $period = FiscalPeriod::where('company_id', $this->company->id)->first();

        // Close period
        $closeResp = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->post("/closing/{$period->id}/close");

        $closeResp->assertRedirect();
        $this->assertTrue($period->fresh()->is_closed);

        // Reopen period
        $reopenResp = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->post("/closing/{$period->id}/reopen", [
                'reason' => 'Perbaikan penyesuaian beban sewa dari auditor eksternal',
            ]);

        $reopenResp->assertRedirect();
        $this->assertFalse($period->fresh()->is_closed);
    }

    public function test_tax_compliance_calendar(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/tax/calendar');

        $response->assertStatus(200);
        $response->assertSee('Kalender Kepatuhan Pajak');
        $response->assertSee('PPh Pasal 21 / 26');
        $response->assertSee('PPN & PPnBM');
    }
}
