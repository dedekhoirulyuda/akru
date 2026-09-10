<?php

namespace App\Modules\Tax\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\FiscalCorrection;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\MasterData\Models\Account;
use App\Modules\Tax\Models\TaxEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxControlController extends Controller
{
    /**
     * Rekapitulasi SPT Masa PPN (PPN Masukan vs PPN Keluaran)
     */
    public function ppnSummary(Request $request): View
    {
        $companyId = session('active_company_id');
        $taxPeriod = $request->query('tax_period', now()->format('Y-m'));

        // PPN Keluaran (Output)
        $outputEntries = TaxEntry::with(['contact', 'taxCode'])
            ->where('company_id', $companyId)
            ->where('tax_period', $taxPeriod)
            ->where('tax_type', 'PPN')
            ->where('direction', 'output')
            ->orderBy('tax_date')
            ->get();

        // PPN Masukan (Input)
        $inputEntries = TaxEntry::with(['contact', 'taxCode'])
            ->where('company_id', $companyId)
            ->where('tax_period', $taxPeriod)
            ->where('tax_type', 'PPN')
            ->where('direction', 'input')
            ->orderBy('tax_date')
            ->get();

        $dppKeluaran = $outputEntries->sum('base_amount');
        $ppnKeluaran = $outputEntries->sum('tax_amount');

        $dppMasukan = $inputEntries->sum('base_amount');
        $ppnMasukan = $inputEntries->where('is_creditable', true)->sum('tax_amount');

        $selisihPpn = $ppnKeluaran - $ppnMasukan; // > 0 = Kurang Bayar; < 0 = Lebih Bayar

        return view('tax.ppn', compact(
            'taxPeriod',
            'outputEntries',
            'inputEntries',
            'dppKeluaran',
            'ppnKeluaran',
            'dppMasukan',
            'ppnMasukan',
            'selisihPpn'
        ));
    }

    /**
     * Pemantauan PPh Pemotongan / Pemungutan (PPh 21, 23, 4 ayat 2)
     */
    public function pphWithholding(Request $request): View
    {
        $companyId = session('active_company_id');
        $taxPeriod = $request->query('tax_period', now()->format('Y-m'));
        $taxType = $request->query('tax_type', 'ALL');

        $query = TaxEntry::with(['contact', 'taxCode'])
            ->where('company_id', $companyId)
            ->where('tax_period', $taxPeriod)
            ->where('tax_type', '!=', 'PPN');

        if ($taxType !== 'ALL') {
            $query->where('tax_type', $taxType);
        }

        $entries = $query->orderBy('tax_date')->get();

        $recapByType = TaxEntry::where('company_id', $companyId)
            ->where('tax_period', $taxPeriod)
            ->where('tax_type', '!=', 'PPN')
            ->groupBy('tax_type')
            ->select('tax_type', DB::raw('SUM(base_amount) as total_base'), DB::raw('SUM(tax_amount) as total_tax'), DB::raw('COUNT(*) as count'))
            ->get();

        return view('tax.pph', compact('taxPeriod', 'taxType', 'entries', 'recapByType'));
    }

    /**
     * Rekonsiliasi Fiskal (Koreksi Fiskal Positif / Negatif)
     */
    public function fiscalReconciliation(Request $request): View
    {
        $companyId = session('active_company_id');
        $taxYear = (int) $request->query('tax_year', now()->year);

        // Commercial Net Profit for this year from GL
        $dateFrom = "{$taxYear}-01-01";
        $dateTo = "{$taxYear}-12-31";

        $lines = JournalLine::join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_sets.company_id', $companyId)
            ->where('journal_sets.status', 'posted')
            ->whereBetween('journal_sets.journal_date', [$dateFrom, $dateTo])
            ->whereIn('accounts.type', ['revenue', 'expense', 'cost_of_sales', 'other_revenue', 'other_expense'])
            ->select(
                'accounts.type',
                DB::raw('SUM(journal_lines.debit) as debit'),
                DB::raw('SUM(journal_lines.credit) as credit')
            )
            ->groupBy('accounts.type')
            ->get();

        $commercialProfit = 0;
        foreach ($lines as $l) {
            $d = (float) $l->debit;
            $c = (float) $l->credit;
            if (in_array($l->type, ['revenue', 'other_revenue'])) {
                $commercialProfit += ($c - $d);
            } else {
                $commercialProfit -= ($d - $c);
            }
        }

        // Fetch Fiscal Corrections
        $corrections = FiscalCorrection::with('account')
            ->where('company_id', $companyId)
            ->where('tax_year', $taxYear)
            ->get();

        $positiveCorrections = $corrections->where('correction_type', 'positive');
        $negativeCorrections = $corrections->where('correction_type', 'negative');

        $totalPositive = $positiveCorrections->sum('amount');
        $totalNegative = $negativeCorrections->sum('amount');

        $fiscalProfit = $commercialProfit + $totalPositive - $totalNegative;
        $corporateTaxPayable = $fiscalProfit > 0 ? ($fiscalProfit * 0.22) : 0; // Tarif 22%

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('tax.fiscal', compact(
            'taxYear',
            'commercialProfit',
            'positiveCorrections',
            'negativeCorrections',
            'totalPositive',
            'totalNegative',
            'fiscalProfit',
            'corporateTaxPayable',
            'accounts'
        ));
    }

    /**
     * Store manual fiscal correction
     */
    public function storeFiscalCorrection(Request $request): RedirectResponse
    {
        $companyId = session('active_company_id');

        $validated = $request->validate([
            'tax_year' => 'required|integer|min:2020|max:2035',
            'account_id' => 'required|exists:accounts,id',
            'correction_type' => 'required|in:positive,negative',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        FiscalCorrection::create([
            'company_id' => $companyId,
            'tax_year' => $validated['tax_year'],
            'account_id' => $validated['account_id'],
            'correction_type' => $validated['correction_type'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Koreksi fiskal berhasil dicatat.');
    }

    /**
     * Ekspor Data DJP Coretax-Ready
     */
    public function coretaxExport(Request $request): View|StreamedResponse
    {
        $companyId = session('active_company_id');
        $taxPeriod = $request->query('tax_period', now()->format('Y-m'));
        $format = $request->query('format');

        $entries = TaxEntry::with(['contact', 'taxCode'])
            ->where('company_id', $companyId)
            ->where('tax_period', $taxPeriod)
            ->orderBy('tax_date')
            ->get();

        // Stream XML / CSV download if requested
        if ($format === 'xml') {
            $response = new StreamedResponse(function () use ($entries, $taxPeriod) {
                $xml = new \SimpleXMLElement('<CoretaxTaxReturnDocument/>');
                $xml->addAttribute('taxPeriod', $taxPeriod);
                $xml->addAttribute('schemaVersion', '2026.1');

                $invoicesNode = $xml->addChild('TaxInvoices');
                foreach ($entries as $e) {
                    $inv = $invoicesNode->addChild('Invoice');
                    $inv->addChild('InvoiceNumber', htmlspecialchars($e->invoice_number ?? 'AUTO'));
                    $inv->addChild('TaxDate', $e->tax_date->toDateString());
                    $inv->addChild('Direction', $e->direction);
                    $inv->addChild('TaxType', $e->tax_type);
                    $inv->addChild('CounterpartyNPWP', htmlspecialchars($e->counterparty_npwp ?? '0000000000000000'));
                    $inv->addChild('CounterpartyName', htmlspecialchars($e->counterparty_name ?? 'UMUM'));
                    $inv->addChild('DPP', number_format($e->base_amount, 2, '.', ''));
                    $inv->addChild('TaxAmount', number_format($e->tax_amount, 2, '.', ''));
                    $inv->addChild('Rate', number_format($e->rate, 2, '.', ''));
                }

                echo $xml->asXML();
            });

            $response->headers->set('Content-Type', 'application/xml');
            $response->headers->set('Content-Disposition', 'attachment; filename="coretax_' . $taxPeriod . '.xml"');
            return $response;
        }

        return view('tax.coretax', compact('taxPeriod', 'entries'));
    }
}
