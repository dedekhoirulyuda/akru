<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Models\AiAnomalyEvidence;
use App\Modules\Ai\Models\AiAnomalyFinding;
use App\Modules\Ai\Models\AiAnomalyRule;
use App\Modules\Ai\Models\AiAnomalyRun;
use App\Modules\Finance\Models\CashTransaction;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Sales\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiAnomalyService
{
    /**
     * Run comprehensive anomaly detection scan for a company.
     */
    public function runScan(int $companyId, string $triggerType = 'manual'): AiAnomalyRun
    {
        $run = AiAnomalyRun::create([
            'company_id' => $companyId,
            'rule_set_version' => '1.0',
            'trigger_type' => $triggerType,
            'started_at' => now(),
            'status' => 'running',
            'records_scanned' => 0,
            'findings_count' => 0,
        ]);

        $recordsScanned = 0;
        $findingsCount = 0;

        try {
            // Rule 1: Duplicate Sales Invoices (Same date and amount)
            $ruleDup = AiAnomalyRule::where('code', 'DUP_SALES_INVOICE')->first();
            $duplicates = SalesInvoice::where('company_id', $companyId)
                ->select('invoice_date', 'total_amount', DB::raw('COUNT(*) as total_count'))
                ->groupBy('invoice_date', 'total_amount')
                ->having('total_count', '>', 1)
                ->get();

            $recordsScanned += SalesInvoice::where('company_id', $companyId)->count();

            foreach ($duplicates as $dup) {
                $fingerprint = md5("{$companyId}_dup_sales_{$dup->invoice_date}_{$dup->total_amount}");
                $finding = AiAnomalyFinding::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'fingerprint' => $fingerprint,
                    ],
                    [
                        'run_id' => $run->id,
                        'rule_id' => $ruleDup?->id,
                        'category' => 'duplicate',
                        'severity' => 'medium',
                        'risk_score' => 55,
                        'confidence' => 0.95,
                        'materiality_value' => (float) $dup->total_amount,
                        'title' => 'Potensi Duplikasi Faktur Penjualan',
                        'explanation_summary' => "Ditemukan {$dup->total_count} faktur bernilai identik (Rp " . number_format($dup->total_amount, 0, ',', '.') . ") pada tanggal {$dup->invoice_date}.",
                        'status' => 'open',
                    ]
                );

                $relatedInvoices = SalesInvoice::where('company_id', $companyId)
                    ->where('invoice_date', $dup->invoice_date)
                    ->where('total_amount', $dup->total_amount)
                    ->get();

                foreach ($relatedInvoices as $inv) {
                    AiAnomalyEvidence::firstOrCreate([
                        'company_id' => $companyId,
                        'finding_id' => $finding->id,
                        'source_type' => 'sales_invoice',
                        'source_id' => $inv->id,
                    ], [
                        'observed_json' => ['invoice_number' => $inv->invoice_number, 'amount' => $inv->total_amount],
                        'snapshot_at' => now(),
                    ]);
                }

                $findingsCount++;
            }

            // Rule 2: Large Round Cash Payments
            $ruleRound = AiAnomalyRule::where('code', 'LARGE_ROUND_CASH_EXPENSE')->first();
            $roundCash = CashTransaction::where('company_id', $companyId)
                ->where('total_amount', '>=', 5000000)
                ->get()
                ->filter(fn($ct) => (int)$ct->total_amount % 1000000 === 0);

            $recordsScanned += CashTransaction::where('company_id', $companyId)->count();

            foreach ($roundCash as $rc) {
                $fingerprint = md5("{$companyId}_round_cash_{$rc->id}");
                $finding = AiAnomalyFinding::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'fingerprint' => $fingerprint,
                    ],
                    [
                        'run_id' => $run->id,
                        'rule_id' => $ruleRound?->id,
                        'category' => 'threshold',
                        'severity' => 'low',
                        'risk_score' => 35,
                        'confidence' => 1.0,
                        'materiality_value' => (float) $rc->total_amount,
                        'title' => "Pengeluaran Kas Angka Bulat [{$rc->transaction_number}]",
                        'explanation_summary' => "Pengeluaran kas tunai bernilai bulat sempurna Rp " . number_format($rc->total_amount, 0, ',', '.') . ". Pastikan bukti fisik/kuitansi terlampir.",
                        'status' => 'open',
                    ]
                );

                AiAnomalyEvidence::firstOrCreate([
                    'company_id' => $companyId,
                    'finding_id' => $finding->id,
                    'source_type' => 'cash_transaction',
                    'source_id' => $rc->id,
                ], [
                    'observed_json' => ['number' => $rc->transaction_number, 'amount' => $rc->total_amount],
                    'snapshot_at' => now(),
                ]);

                $findingsCount++;
            }

            // Rule 3: Negative Inventory Balances
            $ruleNegStock = AiAnomalyRule::where('code', 'NEGATIVE_STOCK_ALERT')->first();
            $negativeStocks = InventoryBalance::with('item')
                ->where('company_id', $companyId)
                ->where('quantity', '<', 0)
                ->get();

            $recordsScanned += InventoryBalance::where('company_id', $companyId)->count();

            foreach ($negativeStocks as $ns) {
                $fingerprint = md5("{$companyId}_neg_stock_{$ns->item_id}");
                $finding = AiAnomalyFinding::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'fingerprint' => $fingerprint,
                    ],
                    [
                        'run_id' => $run->id,
                        'rule_id' => $ruleNegStock?->id,
                        'category' => 'reconciliation',
                        'severity' => 'critical',
                        'risk_score' => 85,
                        'confidence' => 1.0,
                        'materiality_value' => abs((float)$ns->total_value),
                        'title' => "Stok Barang Negatif: {$ns->item->name}",
                        'explanation_summary' => "Kuantitas saldo barang '{$ns->item->name}' ({$ns->item->sku}) tercatat {$ns->quantity}. Diperlukan Stock Opname untuk sinkronisasi fisik.",
                        'status' => 'open',
                    ]
                );

                AiAnomalyEvidence::firstOrCreate([
                    'company_id' => $companyId,
                    'finding_id' => $finding->id,
                    'source_type' => 'inventory_balance',
                    'source_id' => $ns->id,
                ], [
                    'observed_json' => ['sku' => $ns->item->sku, 'quantity' => $ns->quantity],
                    'snapshot_at' => now(),
                ]);

                $findingsCount++;
            }

            $run->update([
                'status' => 'completed',
                'finished_at' => now(),
                'records_scanned' => $recordsScanned,
                'findings_count' => $findingsCount,
            ]);

            return $run;
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_summary' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
