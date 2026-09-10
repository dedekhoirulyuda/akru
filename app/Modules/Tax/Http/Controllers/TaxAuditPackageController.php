<?php

namespace App\Modules\Tax\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalSet;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Tax\Models\TaxEntry;
use Illuminate\Http\Request;

class TaxAuditPackageController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $taxPeriod = $request->get('period', date('Y-m'));

        $taxEntries = TaxEntry::with(['taxCode'])
            ->where('company_id', $companyId)
            ->where('tax_period', $taxPeriod)
            ->get();

        $journals = JournalSet::where('company_id', $companyId)
            ->where('journal_date', 'like', "{$taxPeriod}%")
            ->where('status', 'posted')
            ->count();

        $salesCount = SalesInvoice::where('company_id', $companyId)
            ->where('invoice_date', 'like', "{$taxPeriod}%")
            ->count();

        $purchaseCount = PurchaseInvoice::where('company_id', $companyId)
            ->where('invoice_date', 'like', "{$taxPeriod}%")
            ->count();

        $packageChecksum = hash('sha256', "{$companyId}-{$taxPeriod}-{$taxEntries->count()}-{$journals}");

        return view('tax.audit_package.index', compact(
            'taxPeriod',
            'taxEntries',
            'journals',
            'salesCount',
            'purchaseCount',
            'packageChecksum'
        ));
    }
}
