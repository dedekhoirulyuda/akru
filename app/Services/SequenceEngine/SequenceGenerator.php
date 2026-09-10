<?php

namespace App\Services\SequenceEngine;

use Illuminate\Support\Facades\DB;

/**
 * SequenceGenerator — generates unique, sequential document numbers.
 *
 * Supports per-company, per-branch, per-document-type, per-period
 * sequences with configurable format patterns.
 */
class SequenceGenerator
{
    /**
     * Generate the next sequence number.
     *
     * @param  string  $type     Document type (e.g., 'sales_invoice', 'journal', 'purchase_invoice', 'receipt', 'payment')
     * @param  int     $companyId
     * @param  ?int    $branchId
     * @param  ?string $period   e.g., '2026-09'
     *
     * @return string  Formatted document number
     */
    public function next(
        string $type,
        int $companyId,
        ?int $branchId = null,
        ?string $period = null,
    ): string {
        $now = now();
        $year = $now->format('Y');
        $month = $now->format('m');
        $period = $period ?? "{$year}-{$month}";

        $prefixes = [
            'sales_invoice' => 'INV',
            'customer_receipt' => 'CR',
            'purchase_invoice' => 'BILL',
            'supplier_payment' => 'PAY',
            'journal' => 'JV',
            'cash_in' => 'BKM',
            'cash_out' => 'BKK',
            'bank_transfer' => 'TRF',
            'stock_adjustment' => 'ADJ',
        ];

        $prefix = $prefixes[$type] ?? strtoupper(substr($type, 0, 3));

        return DB::transaction(function () use ($companyId, $type, $period, $prefix, $year, $month) {
            $seq = DB::table('document_sequences')
                ->where('company_id', $companyId)
                ->where('document_type', $type)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::table('document_sequences')->insert([
                    'company_id' => $companyId,
                    'document_type' => $type,
                    'period' => $period,
                    'prefix' => $prefix,
                    'current_number' => 1,
                    'format_pattern' => '{PREFIX}/{YEAR}/{MONTH}/{NUMBER}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $number = 1;
            } else {
                $number = $seq->current_number + 1;
                DB::table('document_sequences')
                    ->where('id', $seq->id)
                    ->update([
                        'current_number' => $number,
                        'updated_at' => now(),
                    ]);
            }

            $paddedNumber = str_pad((string)$number, 4, '0', STR_PAD_LEFT);

            return "{$prefix}/{$year}/{$month}/{$paddedNumber}";
        });
    }
}
