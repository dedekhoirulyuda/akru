<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataExportTest extends TestCase
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

    private function actingAsActiveCompany()
    {
        return $this->actingAs($this->user)
            ->withSession([
                'active_company_id' => $this->company->id,
                'current_company_id' => $this->company->id,
                'active_company_name' => $this->company->name,
            ]);
    }

    public function test_accounting_exports_excel_and_pdf(): void
    {
        $endpoints = [
            'journals_excel' => ['/journals/export/excel', 'excel'],
            'journals_pdf' => ['/journals/export/pdf', 'pdf'],
            'ledger_excel' => ['/general-ledger/export/excel', 'excel'],
            'ledger_pdf' => ['/general-ledger/export/pdf', 'pdf'],
            'trial_balance_excel' => ['/trial-balance/export/excel', 'excel'],
            'trial_balance_pdf' => ['/trial-balance/export/pdf', 'pdf'],
        ];

        foreach ($endpoints as $name => [$uri, $type]) {
            $resp = $this->actingAsActiveCompany()->get($uri);
            $resp->assertStatus(200);

            if ($type === 'excel') {
                $resp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $content = $resp->getContent();
                $this->assertStringStartsWith("PK", $content, "Export {$name} must be a valid OpenXML zip archive");
            } else {
                $resp->assertSee('Cetak / Simpan ke PDF');
            }
        }
    }

    public function test_financial_reports_exports_excel_and_pdf(): void
    {
        $endpoints = [
            'profit_loss_excel' => ['/reports/profit-loss/export/excel', 'excel'],
            'profit_loss_pdf' => ['/reports/profit-loss/export/pdf', 'pdf'],
            'balance_sheet_excel' => ['/reports/balance-sheet/export/excel', 'excel'],
            'balance_sheet_pdf' => ['/reports/balance-sheet/export/pdf', 'pdf'],
            'cash_flow_excel' => ['/reports/cash-flow/export/excel', 'excel'],
            'cash_flow_pdf' => ['/reports/cash-flow/export/pdf', 'pdf'],
            'aging_excel' => ['/reports/aging/export/excel', 'excel'],
            'aging_pdf' => ['/reports/aging/export/pdf', 'pdf'],
        ];

        foreach ($endpoints as $name => [$uri, $type]) {
            $resp = $this->actingAsActiveCompany()->get($uri);
            $resp->assertStatus(200);

            if ($type === 'excel') {
                $resp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $content = $resp->getContent();
                $this->assertStringStartsWith("PK", $content, "Export {$name} must be a valid OpenXML zip archive");
            } else {
                $resp->assertSee('Cetak / Simpan ke PDF');
            }
        }
    }

    public function test_sales_and_purchase_exports_excel_and_pdf(): void
    {
        $endpoints = [
            'sales_invoices_excel' => ['/sales/export/excel', 'excel'],
            'sales_invoices_pdf' => ['/sales/export/pdf', 'pdf'],
            'customer_receipts_excel' => ['/receipts/export/excel', 'excel'],
            'customer_receipts_pdf' => ['/receipts/export/pdf', 'pdf'],
            'purchase_invoices_excel' => ['/purchases/export/excel', 'excel'],
            'purchase_invoices_pdf' => ['/purchases/export/pdf', 'pdf'],
            'supplier_payments_excel' => ['/payments/export/excel', 'excel'],
            'supplier_payments_pdf' => ['/payments/export/pdf', 'pdf'],
        ];

        foreach ($endpoints as $name => [$uri, $type]) {
            $resp = $this->actingAsActiveCompany()->get($uri);
            $resp->assertStatus(200);

            if ($type === 'excel') {
                $resp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $content = $resp->getContent();
                $this->assertStringStartsWith("PK", $content, "Export {$name} must be a valid OpenXML zip archive");
            } else {
                $resp->assertSee('Cetak / Simpan ke PDF');
            }
        }
    }

    public function test_inventory_and_finance_exports_excel_and_pdf(): void
    {
        $endpoints = [
            'inventory_excel' => ['/inventory/export/excel', 'excel'],
            'inventory_pdf' => ['/inventory/export/pdf', 'pdf'],
            'stock_opnames_excel' => ['/stock-opnames/export/excel', 'excel'],
            'stock_opnames_pdf' => ['/stock-opnames/export/pdf', 'pdf'],
            'cash_bank_excel' => ['/finance/cash-bank/export/excel', 'excel'],
            'cash_bank_pdf' => ['/finance/cash-bank/export/pdf', 'pdf'],
            'reconciliation_excel' => ['/finance/reconciliation/export/excel', 'excel'],
            'reconciliation_pdf' => ['/finance/reconciliation/export/pdf', 'pdf'],
        ];

        foreach ($endpoints as $name => [$uri, $type]) {
            $resp = $this->actingAsActiveCompany()->get($uri);
            $resp->assertStatus(200);

            if ($type === 'excel') {
                $resp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $content = $resp->getContent();
                $this->assertStringStartsWith("PK", $content, "Export {$name} must be a valid OpenXML zip archive");
            } else {
                $resp->assertSee('Cetak / Simpan ke PDF');
            }
        }
    }
}
