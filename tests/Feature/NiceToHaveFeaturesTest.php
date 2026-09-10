<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Accounting\Models\AccrualSchedule;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\PettyCashFund;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\Purchase\Models\PurchaseOrder;
use App\Modules\Purchase\Models\PurchaseRequest;
use App\Modules\Sales\Models\Quotation;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NiceToHaveFeaturesTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;
    protected Branch $headOffice;
    protected Warehouse $warehouse1;
    protected Warehouse $warehouse2;
    protected Item $product;
    protected Contact $customer;
    protected Contact $vendor;
    protected BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $this->user = User::where('email', 'owner@akru.id')->firstOrFail();
        $this->company = Company::firstOrFail();

        $this->headOffice = Branch::where('company_id', $this->company->id)->firstOrFail();

        $this->warehouse1 = Warehouse::where('company_id', $this->company->id)->firstOrFail();
        $this->warehouse2 = Warehouse::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'GDG-SBY'],
            [
                'branch_id' => $this->headOffice->id,
                'name' => 'Gudang Cabang Surabaya',
                'address' => 'Rungkut Industri, Surabaya',
                'is_active' => true,
            ]
        );

        $this->product = Item::where('company_id', $this->company->id)
            ->where('is_stockable', true)
            ->firstOrFail();

        $this->customer = Contact::where('company_id', $this->company->id)
            ->where('type', 'customer')
            ->firstOrFail();
        $this->customer->update(['credit_limit' => 50000000]);

        $this->vendor = Contact::where('company_id', $this->company->id)
            ->where('type', 'supplier')
            ->firstOrFail();

        $this->bankAccount = BankAccount::where('company_id', $this->company->id)->firstOrFail();
    }

    protected function actingAsTenant()
    {
        return $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
            ]);
    }

    public function test_quotation_workflow_and_conversion_to_sales_order()
    {
        // 1. Create Quotation
        $response = $this->actingAsTenant()->post(route('quotations.store'), [
            'quotation_date' => '2026-09-09',
            'valid_until' => '2026-09-30',
            'contact_id' => $this->customer->id,
            'branch_id' => $this->headOffice->id,
            'notes' => 'Penawaran proyek pengadaan',
            'items' => [
                [
                    'item_id' => $this->product->id,
                    'description' => 'Unit Barang',
                    'quantity' => 2,
                    'unit_price' => 20000000,
                    'tax_rate' => 11,
                ]
            ]
        ]);

        $response->assertRedirect(route('quotations.index'));
        $this->assertDatabaseHas('quotations', [
            'company_id' => $this->company->id,
            'contact_id' => $this->customer->id,
            'status' => 'sent',
        ]);

        $quotation = Quotation::where('company_id', $this->company->id)->latest()->first();

        // 2. Convert to Sales Order
        $convertResponse = $this->actingAsTenant()->post(route('quotations.convert', $quotation->id));
        $convertResponse->assertRedirect();

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'converted',
        ]);

        $this->assertDatabaseHas('sales_orders', [
            'quotation_id' => $quotation->id,
            'contact_id' => $this->customer->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_sales_order_credit_control_hold_when_credit_limit_exceeded()
    {
        // Customer has credit limit 50,000,000
        // We attempt an order of 3 units @ 20,000,000 + 11% PPN = 66,600,000 (exceeds 50M)
        $response = $this->actingAsTenant()->post(route('sales-orders.store'), [
            'order_date' => '2026-09-09',
            'contact_id' => $this->customer->id,
            'branch_id' => $this->headOffice->id,
            'items' => [
                [
                    'item_id' => $this->product->id,
                    'description' => 'Bulk Order',
                    'quantity' => 3,
                    'unit_price' => 20000000,
                    'tax_rate' => 11,
                ]
            ]
        ]);

        $response->assertRedirect(route('sales-orders.index'));
        $this->assertDatabaseHas('sales_orders', [
            'company_id' => $this->company->id,
            'contact_id' => $this->customer->id,
            'credit_hold' => 1,
            'status' => 'on_hold',
        ]);
    }

    public function test_delivery_order_deducts_inventory_stock()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->headOffice->id,
            'contact_id' => $this->customer->id,
            'order_number' => 'SO-DELIV-01',
            'order_date' => '2026-09-09',
            'subtotal' => 20000000,
            'tax_amount' => 2200000,
            'total_amount' => 22200000,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAsTenant()->post(route('deliveries.store'), [
            'delivery_date' => '2026-09-09',
            'sales_order_id' => $so->id,
            'contact_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse1->id,
            'driver_name' => 'Bambang JNE',
            'vehicle_number' => 'B 1234 CD',
            'items' => [
                [
                    'item_id' => $this->product->id,
                    'quantity' => 1,
                    'notes' => 'Dikirim rapi dengan bubble wrap',
                ]
            ]
        ]);

        $response->assertRedirect(route('deliveries.index'));
        $this->assertDatabaseHas('delivery_orders', [
            'sales_order_id' => $so->id,
            'status' => 'dispatched',
        ]);

        // Inventory balance was updated/deducted
        $this->assertDatabaseHas('inventory_balances', [
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse1->id,
            'item_id' => $this->product->id,
        ]);
    }

    public function test_purchase_procurement_workflow_pr_to_po_to_gr()
    {
        // 1. Purchase Request (PR)
        $prResponse = $this->actingAsTenant()->post(route('purchase-requests.store'), [
            'request_date' => '2026-09-09',
            'required_date' => '2026-09-15',
            'purpose' => 'Kebutuhan penggantian peralatan kantor',
            'items' => [
                [
                    'item_id' => $this->product->id,
                    'quantity' => 5,
                    'notes' => 'Spesifikasi standard',
                ]
            ]
        ]);
        $prResponse->assertRedirect(route('purchase-requests.index'));
        $pr = PurchaseRequest::where('company_id', $this->company->id)->latest()->first();

        // Approve PR
        $this->actingAsTenant()->post(route('purchase-requests.approve', $pr->id));
        $this->assertEquals('approved', $pr->fresh()->status);

        // 2. Purchase Order (PO) referencing PR
        $poResponse = $this->actingAsTenant()->post(route('purchase-orders.store'), [
            'order_date' => '2026-09-09',
            'expected_arrival_date' => '2026-09-16',
            'purchase_request_id' => $pr->id,
            'contact_id' => $this->vendor->id,
            'items' => [
                [
                    'item_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 15000000,
                ]
            ]
        ]);
        $poResponse->assertRedirect(route('purchase-orders.index'));
        $po = PurchaseOrder::where('company_id', $this->company->id)->latest()->first();
        $this->assertEquals('sent', $po->status);

        // 3. Goods Receipt (GR) receiving the items into inventory
        $grResponse = $this->actingAsTenant()->post(route('goods-receipts.store'), [
            'receipt_date' => '2026-09-09',
            'purchase_order_id' => $po->id,
            'contact_id' => $this->vendor->id,
            'warehouse_id' => $this->warehouse1->id,
            'supplier_delivery_number' => 'SJ-SUPP-991',
            'items' => [
                [
                    'item_id' => $this->product->id,
                    'accepted_quantity' => 5,
                    'rejected_quantity' => 0,
                    'notes' => 'Semua unit diterima dalam kondisi baru & segel',
                ]
            ]
        ]);
        $grResponse->assertRedirect(route('goods-receipts.index'));

        $this->assertEquals('received', $po->fresh()->status);
    }

    public function test_inter_warehouse_stock_transfer_flow()
    {
        // 1. Dispatch Transfer from Warehouse 1 to Warehouse 2
        $response = $this->actingAsTenant()->post(route('stock-transfers.store'), [
            'transfer_date' => '2026-09-09',
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'notes' => 'Mutasi stok untuk buffer cabang Surabaya',
            'items' => [
                [
                    'item_id' => $this->product->id,
                    'quantity' => 2,
                    'notes' => 'Serial number verified',
                ]
            ]
        ]);
        $response->assertRedirect(route('stock-transfers.index'));

        $transfer = StockTransfer::where('company_id', $this->company->id)->latest()->first();
        $this->assertEquals('in_transit', $transfer->status);

        // 2. Receive Transfer at Warehouse 2
        $receiveResponse = $this->actingAsTenant()->post(route('stock-transfers.receive', $transfer->id));
        $receiveResponse->assertRedirect();

        $this->assertEquals('received', $transfer->fresh()->status);
    }

    public function test_stock_opname_and_adjustment_posting()
    {
        $response = $this->actingAsTenant()->post(route('stock-opnames.store'), [
            'opname_date' => '2026-09-09',
            'warehouse_id' => $this->warehouse1->id,
            'notes' => 'Audit fisik stok bulanan',
            'items' => [
                [
                    'item_id' => $this->product->id,
                    'physical_quantity' => 8,
                    'notes' => '2 unit rusak di gudang',
                ]
            ]
        ]);
        $response->assertRedirect(route('stock-opnames.index'));

        $opname = StockOpname::where('company_id', $this->company->id)->latest()->first();
        $this->assertEquals('pending_approval', $opname->status);

        // Approve and post discrepancy
        $approveResponse = $this->actingAsTenant()->post(route('stock-opnames.approve', $opname->id));
        $approveResponse->assertRedirect();

        $this->assertEquals('approved', $opname->fresh()->status);
    }

    public function test_petty_cash_fund_and_voucher_recording()
    {
        $expenseAccount = Account::where('company_id', $this->company->id)->where('type', 'expense')->firstOrFail();

        // 1. Create Petty Cash Fund
        $fundResponse = $this->actingAsTenant()->post(route('petty-cash.funds.store'), [
            'fund_name' => 'Kas Kecil Sekretariat',
            'bank_account_id' => $this->bankAccount->id,
            'imprest_amount' => 5000000,
        ]);
        $fundResponse->assertRedirect(route('petty-cash.index'));

        $fund = PettyCashFund::where('company_id', $this->company->id)->firstOrFail();
        $this->assertEquals(5000000, $fund->current_balance);

        // 2. Record Expense Voucher
        $voucherResponse = $this->actingAsTenant()->post(route('petty-cash.vouchers.store'), [
            'petty_cash_fund_id' => $fund->id,
            'voucher_date' => '2026-09-09',
            'recipient_name' => 'Kurir Express',
            'expense_account_id' => $expenseAccount->id,
            'description' => 'Ongkos kirim dokumen kantor',
            'amount' => 150000,
        ]);

        $voucherResponse->assertRedirect(route('petty-cash.index'));
        $this->assertEquals(4850000, $fund->fresh()->current_balance);
        $this->assertDatabaseHas('petty_cash_vouchers', [
            'petty_cash_fund_id' => $fund->id,
            'amount' => 150000,
        ]);
    }

    public function test_budget_creation_and_monitoring()
    {
        $beban = Account::where('company_id', $this->company->id)
            ->where('type', 'expense')
            ->firstOrFail();

        $response = $this->actingAsTenant()->post(route('budgets.store'), [
            'name' => 'Anggaran Operasional 2026',
            'fiscal_year' => 2026,
            'items' => [
                [
                    'account_id' => $beban->id,
                    'annual_amount' => 120000000,
                ]
            ]
        ]);

        $response->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('budgets', [
            'company_id' => $this->company->id,
            'fiscal_year' => 2026,
        ]);
    }

    public function test_dimension_and_accrual_amortization_schedule()
    {
        // 1. Dimension creation
        $dimResponse = $this->actingAsTenant()->post(route('dimensions.store'), [
            'name' => 'Departemen Operasional',
            'code' => 'DEPT',
        ]);
        $dimResponse->assertRedirect(route('dimensions.index'));
        $this->assertDatabaseHas('dimensions', ['code' => 'DEPT']);

        // 2. Accrual / Amortization schedule
        $sewaDibayarDimuka = Account::where('company_id', $this->company->id)->where('type', 'asset')->firstOrFail();
        $bebanSewa = Account::where('company_id', $this->company->id)->where('type', 'expense')->firstOrFail();

        $accrualResponse = $this->actingAsTenant()->post(route('accruals.store'), [
            'name' => 'Amortisasi Sewa Kantor 12 Bulan',
            'type' => 'prepaid_expense',
            'start_date' => '2026-01-01',
            'periods_count' => 12,
            'total_amount' => 120000000,
            'prepaid_account_id' => $sewaDibayarDimuka->id,
            'target_account_id' => $bebanSewa->id,
        ]);
        $accrualResponse->assertRedirect(route('accruals.index'));

        $schedule = AccrualSchedule::where('company_id', $this->company->id)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals(12, $schedule->lines()->count());

        // Process first period
        $firstLine = $schedule->lines()->first();
        $postPeriodResponse = $this->actingAsTenant()->post(route('accruals.process', [
            'id' => $schedule->id,
            'lineId' => $firstLine->id
        ]));
        $postPeriodResponse->assertRedirect();

        $this->assertEquals('posted', $firstLine->fresh()->status);
        $this->assertNotNull($firstLine->fresh()->journal_set_id);
    }

    public function test_tax_audit_package_generation_and_ai_categorizer()
    {
        // 1. Tax Audit Package View
        $taxPackage = $this->actingAsTenant()->get(route('tax.audit-package', ['period' => '2026-09']));
        $taxPackage->assertStatus(200);

        // 2. AI Categorizer API (GET query)
        $aiResponse = $this->actingAsTenant()->getJson(route('ai.suggest-category', [
            'query' => 'Tagihan internet kantor',
        ]));
        $aiResponse->assertStatus(200)
            ->assertJsonStructure(['success', 'suggested_account', 'confidence', 'reasoning', 'journal_hint']);
        $this->assertEquals('6300', $aiResponse->json('suggested_account.code'));

        // Test Bensin semantic lookup
        $bensinResponse = $this->actingAsTenant()->getJson(route('ai.suggest-category', [
            'query' => 'Beli bensin dinas operasional',
        ]));
        $bensinResponse->assertStatus(200);
        $this->assertEquals('6700', $bensinResponse->json('suggested_account.code'));

        // 3. AI Interactive Chat API (POST chat)
        $chatKas = $this->actingAsTenant()->postJson(route('ai.chat'), [
            'message' => 'Berapa saldo kas dan bank saat ini?',
        ]);
        $chatKas->assertStatus(200)
            ->assertJsonStructure(['success', 'reply', 'topic']);

        $chatOmzet = $this->actingAsTenant()->postJson(route('ai.chat'), [
            'message' => 'Berapa total omzet penjualan bulan ini?',
        ]);
        $chatOmzet->assertStatus(200)
            ->assertJsonStructure(['success', 'reply', 'topic']);

        $chatTax = $this->actingAsTenant()->postJson(route('ai.chat'), [
            'message' => 'berapa tarif pajak pph 21/23?',
        ]);
        $chatTax->assertStatus(200)
            ->assertJsonStructure(['success', 'reply', 'topic']);
        $this->assertEquals('tax_rates', $chatTax->json('topic'));
        $this->assertStringContainsString('Tarif PPh Pasal 21', $chatTax->json('reply'));
        $this->assertStringContainsString('Tarif PPh Pasal 23', $chatTax->json('reply'));
        $this->assertStringContainsString('2%', $chatTax->json('reply'));

        // 4. AI Assistant Dashboard View
        $aiDashboard = $this->actingAsTenant()->get(route('ai.index'));
        $aiDashboard->assertStatus(200)
            ->assertSee('AKRU AI')
            ->assertSee('Pemindaian Anomali');

        // 5. Analytics Margin & Profitability View
        $analyticsPage = $this->actingAsTenant()->get(route('reports.analytics'));
        $analyticsPage->assertStatus(200)
            ->assertSee('Profitabilitas Per Produk');
    }
}
