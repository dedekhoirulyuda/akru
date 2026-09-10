<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ComprehensiveApplicationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_all_primary_blueprint_routes_and_speed(): void
    {
        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $user = User::where('email', 'owner@akru.id')->first();
        $company = Company::first();

        // 35+ core blueprint feature routes
        $routes = [
            // Core & Administration
            'Dashboard' => '/dashboard',
            'Companies' => '/companies',
            'Branches & Warehouses' => '/branches',
            'Settings' => '/settings',
            'Print Layout Settings' => '/settings/print-layout',
            'Workflow Governance' => '/workflow',
            'Audit Trail' => '/audit',
            'Subscription & Quota' => '/subscription',

            // Master Data
            'COA Accounts' => '/coa',
            'Customers' => '/customers',
            'Suppliers' => '/suppliers',
            'Products & Items' => '/products',
            'Tax Codes' => '/tax-codes',
            'Bank Accounts' => '/bank-accounts',
            'Dimensions & Cost Centers' => '/dimensions',

            // Sales & Invoicing
            'Sales Invoices' => '/sales',
            'Customer Receipts' => '/receipts',
            'Quotations' => '/quotations',
            'Sales Orders' => '/sales-orders',
            'Delivery Orders' => '/deliveries',

            // Purchase & Payables
            'Purchase Invoices' => '/purchases',
            'Supplier Payments' => '/payments',
            'Purchase Requests' => '/purchase-requests',
            'Purchase Orders' => '/purchase-orders',
            'Goods Receipts' => '/goods-receipts',

            // Finance & Cash/Bank
            'Cash & Bank Transactions' => '/finance/cash-bank',
            'Bank Transfers' => '/finance/transfers',
            'Bank Reconciliation' => '/finance/reconciliation',
            'Petty Cash Fund' => '/finance/petty-cash',
            'Budgeting' => '/finance/budgets',
            'Cash Forecast' => '/finance/forecast',

            // Inventory
            'Stock Levels' => '/inventory',
            'Stock Transfers' => '/stock-transfers',
            'Stock Opname' => '/stock-opnames',

            // Accounting & Financial Statements
            'Journal Sets' => '/journals',
            'General Ledger' => '/general-ledger',
            'Trial Balance' => '/trial-balance',
            'Profit & Loss Statement' => '/reports/profit-loss',
            'Balance Sheet' => '/reports/balance-sheet',
            'Cash Flow Statement' => '/reports/cash-flow',
            'Aging Report' => '/reports/aging',
            'Fixed Assets Register' => '/assets',
            'Period Closing Workbench' => '/closing',
            'Accruals & Schedules' => '/accruals',

            // Tax & Coretax
            'PPN Output/Input' => '/tax/ppn',
            'PPh Withholding' => '/tax/pph',
            'Fiscal Reconciliation' => '/tax/fiscal',
            'Coretax Export' => '/tax/coretax',
            'Tax Compliance Calendar' => '/tax/calendar',
            'Tax Audit Package' => '/tax/audit-package',

            // AI & Smart Tools
            'AKRU AI Assistant' => '/ai/assistant',
            'Bank Statement Converter' => '/document/converter',
        ];

        echo "\n\n  🚀 --- BLUEPRINT FEATURE SPEED & HEALTH BENCHMARK ---\n";
        printf("  %-30s %-28s %-8s %-10s %s\n", "FEATURE", "ROUTE", "STATUS", "LATENCY", "HEALTH");
        echo "  " . str_repeat("-", 86) . "\n";

        foreach ($routes as $featureName => $uri) {
            $start = microtime(true);

            $response = $this->actingAs($user)
                ->withSession([
                    'active_company_id' => $company->id,
                    'current_company_id' => $company->id,
                ])
                ->get($uri);

            $durationMs = round((microtime(true) - $start) * 1000, 1);
            $statusCode = $response->getStatusCode();
            $health = $durationMs < 1000 ? "⚡ FAST (<1s)" : ($durationMs < 3000 ? "✓ OK (<3s)" : "⚠️ SLOW (>3s)");

            printf("  %-30s %-28s %-8s %-10s %s\n", $featureName, $uri, $statusCode . " OK", $durationMs . "ms", $health);

            // 1. Status Assertion: 200 OK
            $this->assertEquals(
                200, 
                $statusCode, 
                "Feature '{$featureName}' on {$uri} failed with status {$statusCode}"
            );

            // 2. Speed Assertion: Must load in under 3000ms (Requirement 3: load under 3 seconds)
            $this->assertLessThan(
                3000, 
                $durationMs, 
                "Feature '{$featureName}' on {$uri} took {$durationMs}ms which exceeds 3000ms threshold!"
            );
        }
        echo "  " . str_repeat("-", 86) . "\n\n";
    }

    public function test_superadmin_platform_routes_and_speed(): void
    {
        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $superadmin = User::where('is_superadmin', true)->first();
        if (!$superadmin) {
            $superadmin = User::first();
            $superadmin->update(['is_superadmin' => true]);
        }

        $platformRoutes = [
            'Platform Dashboard' => '/platform',
            'Platform Companies' => '/platform/companies',
            'Platform Users' => '/platform/users',
            'Platform Plans' => '/platform/plans',
            'Platform Subscriptions' => '/platform/subscriptions',
            'Platform Modules' => '/platform/modules',
            'Platform API Integrations' => '/platform/api-integrations',
        ];

        foreach ($platformRoutes as $name => $uri) {
            $start = microtime(true);

            $response = $this->actingAs($superadmin)->get($uri);
            $durationMs = (microtime(true) - $start) * 1000;

            $this->assertEquals(200, $response->getStatusCode(), "Platform '{$name}' on {$uri} failed with status {$response->getStatusCode()}");
            $this->assertLessThan(3000, $durationMs, "Platform '{$name}' took {$durationMs}ms (exceeds 3000ms)");
        }
    }
}
