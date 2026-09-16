<?php

namespace App\Modules\Ai\Services;

use App\Modules\Accounting\Models\JournalSet;
use App\Modules\Core\Models\Company;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Ai\Models\AiAnomalyFinding;
use App\Modules\Ai\Models\AiSuggestion;
use Illuminate\Support\Facades\DB;

class AiToolRegistry
{
    public function __construct(
        protected AiSemanticLayer $semanticLayer
    ) {}

    /**
     * Return list of all available tool definitions with JSON schemas.
     */
    public function getToolDefinitions(): array
    {
        return [
            [
                'name' => 'get_company_context',
                'description' => 'Mendapatkan profil perusahaan aktif, mata uang dasar, dan periode buku aktif.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'get_financial_summary',
                'description' => 'Mendapatkan ringkasan posisi kas & bank, omzet penjualan, HPP, margin laba, piutang, dan hutang perusahaan.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'start_date' => ['type' => 'string', 'description' => 'Format YYYY-MM-DD'],
                        'end_date' => ['type' => 'string', 'description' => 'Format YYYY-MM-DD'],
                    ],
                ],
            ],
            [
                'name' => 'get_ar_aging',
                'description' => 'Mendapatkan daftar dan status umur piutang pelanggan (Accounts Receivable) serta faktur yang telah melewati jatuh tempo.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'description' => 'Maksimal data (default 10)'],
                    ],
                ],
            ],
            [
                'name' => 'get_ap_aging',
                'description' => 'Mendapatkan daftar tagihan hutang pemasok (Accounts Payable) yang belum lunas dan status jatuh temponya.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'description' => 'Maksimal data (default 10)'],
                    ],
                ],
            ],
            [
                'name' => 'search_transactions',
                'description' => 'Mencari transaksi penjualan, pembelian, atau kas berdasarkan kata kunci, nomor dokumen, atau rentang tanggal.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Kata kunci atau nomor faktur'],
                        'type' => ['type' => 'string', 'enum' => ['sales', 'purchase', 'cash', 'all'], 'description' => 'Tipe transaksi'],
                        'limit' => ['type' => 'integer', 'description' => 'Maksimal hasil (default 5)'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'get_anomaly_finding',
                'description' => 'Mendapatkan temuan anomali atau risiko aktif yang telah terdeteksi pada pembukuan entitas.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'severity' => ['type' => 'string', 'enum' => ['all', 'high', 'critical', 'medium', 'low']],
                    ],
                ],
            ],
            [
                'name' => 'create_suggestion_draft',
                'description' => 'Mengajukan usulan draft pencatatan jurnal atau pemetaan akun untuk ditinjau oleh manusia (Human-in-the-Loop). Tidak melakukan posting otomatis.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'suggestion_type' => ['type' => 'string', 'enum' => ['account_mapping', 'journal_draft', 'tax_mapping']],
                        'payload' => ['type' => 'object', 'description' => 'Detail usulan'],
                        'reason' => ['type' => 'string', 'description' => 'Alasan akuntansi atau PSAK'],
                    ],
                    'required' => ['suggestion_type', 'payload', 'reason'],
                ],
            ],
        ];
    }

    /**
     * Execute a trusted tool safely with server-enforced company_id.
     */
    public function executeTool(int $companyId, int $userId, string $toolName, array $args = []): array
    {
        switch ($toolName) {
            case 'get_company_context':
                $company = Company::find($companyId);
                return [
                    'company_id' => $companyId,
                    'company_name' => $company?->name ?? 'Perusahaan Aktif',
                    'currency' => 'IDR',
                    'fiscal_year' => (int) now()->year,
                    'is_trial' => $company ? $company->isTrial() : false,
                ];

            case 'get_financial_summary':
                return $this->semanticLayer->getMetrics($companyId, [
                    'start_date' => $args['start_date'] ?? null,
                    'end_date' => $args['end_date'] ?? null,
                ]);

            case 'get_ar_aging':
                $limit = min(50, (int) ($args['limit'] ?? 10));
                $invoices = SalesInvoice::with('contact')
                    ->where('company_id', $companyId)
                    ->whereIn('status', ['posted', 'partially_paid'])
                    ->orderBy('due_date', 'asc')
                    ->limit($limit)
                    ->get();

                return [
                    'total_count' => $invoices->count(),
                    'invoices' => $invoices->map(fn($i) => [
                        'id' => $i->id,
                        'number' => $i->invoice_number,
                        'customer' => $i->contact?->name ?? 'Pelanggan Umum',
                        'date' => $i->invoice_date,
                        'due_date' => $i->due_date,
                        'amount' => (float) $i->total_amount,
                        'balance_due' => (float) ($i->total_amount - ($i->paid_amount ?? 0)),
                        'is_overdue' => $i->due_date && $i->due_date < date('Y-m-d'),
                    ])->toArray(),
                ];

            case 'get_ap_aging':
                $limit = min(50, (int) ($args['limit'] ?? 10));
                $bills = PurchaseInvoice::with('contact')
                    ->where('company_id', $companyId)
                    ->whereIn('status', ['posted', 'partially_paid'])
                    ->orderBy('due_date', 'asc')
                    ->limit($limit)
                    ->get();

                return [
                    'total_count' => $bills->count(),
                    'bills' => $bills->map(fn($b) => [
                        'id' => $b->id,
                        'number' => $b->invoice_number,
                        'vendor' => $b->contact?->name ?? 'Pemasok',
                        'date' => $b->invoice_date,
                        'due_date' => $b->due_date,
                        'amount' => (float) $b->total_amount,
                        'balance_due' => (float) ($b->total_amount - ($b->paid_amount ?? 0)),
                        'is_overdue' => $b->due_date && $b->due_date < date('Y-m-d'),
                    ])->toArray(),
                ];

            case 'search_transactions':
                $q = '%' . ($args['query'] ?? '') . '%';
                $limit = min(20, (int) ($args['limit'] ?? 5));
                $sales = SalesInvoice::where('company_id', $companyId)
                    ->where(function($w) use ($q) {
                        $w->where('invoice_number', 'like', $q)
                          ->orWhere('description', 'like', $q);
                    })
                    ->limit($limit)
                    ->get()
                    ->map(fn($i) => [
                        'source' => 'sales_invoice',
                        'id' => $i->id,
                        'number' => $i->invoice_number,
                        'amount' => (float) $i->total_amount,
                        'date' => $i->invoice_date,
                        'status' => $i->status,
                    ]);

                $purchases = PurchaseInvoice::where('company_id', $companyId)
                    ->where(function($w) use ($q) {
                        $w->where('invoice_number', 'like', $q)
                          ->orWhere('description', 'like', $q);
                    })
                    ->limit($limit)
                    ->get()
                    ->map(fn($b) => [
                        'source' => 'purchase_invoice',
                        'id' => $b->id,
                        'number' => $b->invoice_number,
                        'amount' => (float) $b->total_amount,
                        'date' => $b->invoice_date,
                        'status' => $b->status,
                    ]);

                return [
                    'results' => $sales->concat($purchases)->take($limit)->values()->toArray(),
                ];

            case 'get_anomaly_finding':
                $severity = $args['severity'] ?? 'all';
                $query = AiAnomalyFinding::where('company_id', $companyId)
                    ->where('status', '!=', 'resolved');

                if ($severity !== 'all') {
                    $query->where('severity', $severity);
                }

                $findings = $query->orderBy('risk_score', 'desc')->limit(10)->get();

                return [
                    'count' => $findings->count(),
                    'findings' => $findings->map(fn($f) => [
                        'id' => $f->id,
                        'title' => $f->title,
                        'category' => $f->category,
                        'severity' => $f->severity,
                        'risk_score' => $f->risk_score,
                        'explanation' => $f->explanation_summary,
                        'status' => $f->status,
                    ])->toArray(),
                ];

            case 'create_suggestion_draft':
                $suggestion = AiSuggestion::create([
                    'company_id' => $companyId,
                    'suggestion_type' => $args['suggestion_type'],
                    'payload_json' => $args['payload'] ?? [],
                    'reason_summary' => $args['reason'] ?? 'Rekomendasi otomatis AKRU AI',
                    'confidence' => 0.90,
                    'risk_level' => 'low',
                    'status' => 'awaiting_review',
                    'created_by' => $userId,
                ]);

                return [
                    'success' => true,
                    'suggestion_id' => $suggestion->id,
                    'status' => 'awaiting_review',
                    'message' => 'Usulan draft berhasil dibuat dalam antrean peninjauan manusia.',
                ];

            default:
                throw new \InvalidArgumentException("Unknown tool: {$toolName}");
        }
    }
}
