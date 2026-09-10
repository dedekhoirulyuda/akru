<?php

namespace App\Services\TaxEngine;

use Illuminate\Support\Facades\DB;

/**
 * TaxCalculator — resolves tax rules and calculates tax amounts.
 *
 * Blueprint §2.8: Tax rules have versions and effective dates.
 * Tax entries must link back to source transaction lines.
 * Blueprint invariant: AI does NOT post tax. Calculation is deterministic.
 */
class TaxCalculator
{
    /**
     * Calculate tax for a transaction line.
     *
     * @param  int|string  $taxCodeId       ID or Code of tax_codes
     * @param  float|string $baseAmount     DPP (Dasar Pengenaan Pajak)
     * @param  ?string      $transactionDate
     * @param  ?int         $companyId
     *
     * @return array{tax_code_id: int, code: string, base: float, rate: float, amount: float, tax_type: string, direction: string}
     */
    public function calculate(
        int|string $taxCodeId,
        float|string $baseAmount,
        ?string $transactionDate = null,
        ?int $companyId = null,
    ): array {
        $companyId = $companyId ?? session('current_company_id');
        $base = (float) $baseAmount;

        $query = DB::table('tax_codes')->where('company_id', $companyId);
        if (is_numeric($taxCodeId)) {
            $query->where('id', $taxCodeId);
        } else {
            $query->where('code', $taxCodeId);
        }
        $taxCode = $query->first();

        if (!$taxCode) {
            return [
                'tax_code_id' => 0,
                'code' => 'NONE',
                'base' => $base,
                'rate' => 0.0,
                'amount' => 0.0,
                'tax_type' => 'NONE',
                'direction' => 'output',
            ];
        }

        $rate = (float) $taxCode->rate;
        $amount = round(($base * $rate) / 100, 2);

        return [
            'tax_code_id' => $taxCode->id,
            'code' => $taxCode->code,
            'base' => $base,
            'rate' => $rate,
            'amount' => $amount,
            'tax_type' => $taxCode->tax_type,
            'direction' => str_contains($taxCode->code, 'MASUKAN') ? 'input' : 'output',
        ];
    }

    /**
     * Record a tax entry in the tax subledger.
     */
    public function recordEntry(
        int $companyId,
        int $taxCodeId,
        string $sourceType,
        int $sourceId,
        float $baseAmount,
        float $taxAmount,
        string $taxDate,
        string $direction = 'output',
        ?int $contactId = null,
        ?string $invoiceNumber = null,
        ?string $counterpartyNpwp = null,
        ?string $counterpartyName = null,
        ?int $accountId = null,
    ): int {
        $taxCode = DB::table('tax_codes')->where('id', $taxCodeId)->first();
        $rate = $taxCode ? (float) $taxCode->rate : 0.0;
        $taxType = $taxCode ? $taxCode->tax_type : 'PPN';
        $taxPeriod = substr($taxDate, 0, 7);

        return DB::table('tax_entries')->insertGetId([
            'company_id' => $companyId,
            'tax_code_id' => $taxCodeId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'contact_id' => $contactId,
            'tax_date' => $taxDate,
            'tax_period' => $taxPeriod,
            'tax_type' => $taxType,
            'base_amount' => $baseAmount,
            'rate' => $rate,
            'tax_amount' => $taxAmount,
            'direction' => $direction,
            'invoice_number' => $invoiceNumber,
            'counterparty_npwp' => $counterpartyNpwp,
            'counterparty_name' => $counterpartyName,
            'is_creditable' => true,
            'status' => 'draft',
            'account_id' => $accountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
