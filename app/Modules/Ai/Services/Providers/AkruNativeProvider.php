<?php

namespace App\Modules\Ai\Services\Providers;

use App\Modules\Ai\Services\Contracts\AiProviderContract;
use App\Modules\Ai\Services\DTOs\AiRequest;
use App\Modules\Ai\Services\DTOs\AiResponse;
use App\Modules\Ai\Services\DTOs\ProviderCapabilities;
use App\Modules\Ai\Services\DTOs\ProviderHealth;
use App\Modules\Ai\Services\AiSemanticLayer;
use App\Modules\Ai\Services\AiToolRegistry;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Purchase\Models\PurchaseInvoice;

class AkruNativeProvider implements AiProviderContract
{
    public function __construct(
        protected AiSemanticLayer $semanticLayer,
        protected AiToolRegistry $toolRegistry
    ) {}

    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(
            supportsTools: true,
            supportsStreaming: false,
            supportsMultimodal: false,
            isFree: true,
            contextLimit: 16384,
            outputLimit: 4096
        );
    }

    public function healthCheck(?string $apiKey = null): ProviderHealth
    {
        return new ProviderHealth(
            isHealthy: true,
            status: 'operational',
            message: 'AKRU Native Engine siap melayani konsultasi dan metrik deterministik.',
            latencyMs: 5
        );
    }

    public function chat(AiRequest $request): AiResponse
    {
        $companyId = $request->companyId;
        $userId = $request->userId;
        $msg = trim($request->userMessage);
        $lowerMsg = strtolower($msg);

        $toolCallsExecuted = [];

        // 0. Panduan & Cara Penggunaan Sistem AKRU
        if (
            str_contains($lowerMsg, 'cara penggun') ||
            str_contains($lowerMsg, 'cara pakai') ||
            str_contains($lowerMsg, 'cara menggun') ||
            str_contains($lowerMsg, 'panduan') ||
            str_contains($lowerMsg, 'alur kerja') ||
            str_contains($lowerMsg, 'workflow') ||
            str_contains($lowerMsg, 'tutorial') ||
            str_contains($lowerMsg, 'langkah awal') ||
            str_contains($lowerMsg, 'memulai akru') ||
            str_contains($lowerMsg, 'sop') ||
            $lowerMsg === 'help' ||
            $lowerMsg === 'bantuan'
        ) {
            $answer = "### 📘 Panduan Alur Kerja & Cara Penggunaan Sistem AKRU\n\n"
                    . "Selamat datang di **AKRU** — Sistem ERP Akuntansi, Keuangan, dan Kepatuhan Pajak Indonesia. Berikut adalah 5 tahapan alur kerja operasional standar (SOP) dari setup hingga pelaporan:\n\n"
                    . "---\n\n"
                    . "#### 1️⃣ Setup & Master Data Awal (Fondasi Sistem)\n"
                    . "Sebelum mulai mencatat transaksi harian, lengkapi data master perusahaan:\n"
                    . "• **Bagan Akun (COA):** Sesuaikan struktur kode akun standar PSAK di menu [Bagan Akun](" . route('coa.index') . ").\n"
                    . "• **Rekening Kas & Bank:** Daftarkan akun kas fisik dan rekening bank operasional di menu [Rekening Bank](" . route('bank-accounts.index') . ").\n"
                    . "• **Mitra Bisnis (Kontak):** Daftarkan [Data Pelanggan](" . route('customers.index') . ") dan [Data Pemasok](" . route('suppliers.index') . ") beserta NPWP/NIK untuk validasi e-Faktur dan e-Bupot.\n"
                    . "• **Katalog Produk & Persediaan:** Tambahkan master barang dan jasa di menu [Produk & Jasa](" . route('products.index') . ") lengkap dengan satuan dan harga jual/beli standar.\n\n"
                    . "#### 2️⃣ Siklus Pengadaan & Pembelian (Procure-to-Pay)\n"
                    . "Catat seluruh aktivitas pengeluaran dan belanja operasional / stok:\n"
                    . "• **Permintaan & Pesanan Beli:** Buat [Permintaan Beli (PR)](" . route('purchase-requests.index') . ") dan terbitkan [Pesanan Pembelian (PO)](" . route('purchase-orders.index') . ") ke supplier.\n"
                    . "• **Penerimaan Barang:** Verifikasi fisik barang masuk melalui [Penerimaan Gudang (GR)](" . route('goods-receipts.index') . ") (menambah stok otomatis).\n"
                    . "• **Faktur Pembelian (Billing):** Catat tagihan tagihan supplier di [Faktur Pembelian](" . route('purchases.index') . "). Sistem otomatis mengakui hutang usaha dan PPN Masukan.\n"
                    . "• **Pelunasan Hutang:** Lakukan pembayaran melalui menu [Pembayaran Hutang](" . route('payments.index') . ").\n\n"
                    . "#### 3️⃣ Siklus Penjualan & Pendapatan (Order-to-Cash)\n"
                    . "Kelola siklus penjualan barang atau jasa ke pelanggan secara terstruktur:\n"
                    . "• **Penawaran & Pesanan Jual:** Buat [Penawaran (Quotation)](" . route('quotations.index') . ") dan konfirmasi pesanan di [Pesanan Penjualan (SO)](" . route('sales-orders.index') . ").\n"
                    . "• **Pengiriman Barang:** Terbitkan dokumen [Surat Jalan (DO)](" . route('deliveries.index') . ") saat barang dikirim ke customer.\n"
                    . "• **Faktur Penjualan (Invoicing):** Terbitkan invoice di [Faktur Penjualan](" . route('sales.index') . "). Jurnal pendapatan, piutang, HPP, dan PPN Keluaran terbentuk secara otomatis.\n"
                    . "• **Penerimaan Pembayaran:** Catat pelunasan piutang customer di menu [Penerimaan Piutang](" . route('receipts.index') . ").\n\n"
                    . "#### 4️⃣ Manajemen Keuangan, Kas Kecil & Rekonsiliasi Bank\n"
                    . "• **Kas Kecil (Petty Cash):** Catat pengeluaran operasional harian kantor di [Kas Kecil](" . route('petty-cash.index') . ") dengan metode dana tetap (imprest) atau fluktuatif.\n"
                    . "• **Mutasi & Transfer Dana:** Kelola penerimaan non-penjualan atau transfer antarrekening di menu [Transaksi Kas & Bank](" . route('cash-bank.index') . ").\n"
                    . "• **Rekonsiliasi Bank:** Lakukan pencocokan otomatis/manual antara mutasi rekening koran fisik dan buku besar bank di [Rekonsiliasi Bank](" . route('reconciliation.index') . ").\n\n"
                    . "#### 5️⃣ Kepatuhan Pajak, Laporan Keuangan & Tutup Buku\n"
                    . "• **Tax Control & Coretax:** Pantau PPN di [SPT Masa PPN 1111](" . route('tax.ppn') . "), bukti potong di [PPh Withholding](" . route('tax.pph') . "), serta ekspor data siap lapor ke [Ekspor XML Coretax](" . route('tax.coretax') . ").\n"
                    . "• **Pusat Regulasi Terkini:** Rujuk dasar hukum perpajakan (PMK/UU), kepabeanan impor, dan SAK di [Pusat Peraturan](" . route('regulations.index') . ").\n"
                    . "• **Laporan Keuangan Eksekutif:** Pantau kinerja laba dan posisi aset di [Laba Rugi](" . route('reports.profit-loss') . "), [Neraca](" . route('reports.balance-sheet') . "), dan [Arus Kas](" . route('reports.cash-flow') . ").\n"
                    . "• **Tutup Buku Akhir Periode:** Kunci saldo dan perhitungkan saldo laba berjalan di menu [Penutupan Buku (Closing)](" . route('closing.index') . ").\n\n"
                    . "> 🤖 **Memanfaatkan Asisten AKRU AI:** Kapan pun Anda butuh informasi cepat, cukup tanyakan di sini seperti *\"Berapa saldo kas dan bank?\"*, *\"Berapa laba kotor bulan ini?\"*, *\"Rekomendasi akun COA untuk beli laptop kantor\"*, atau *\"Perhitungan pajak pembelian impor\"*.";

            return new AiResponse(
                answer: $answer,
                summary: "Panduan alur operasional sistem AKRU (Setup Master Data -> Pengadaan -> Penjualan -> Kas/Bank -> Pajak & Laporan)",
                intent: 'system_user_guide',
                scope: ['company_id' => $companyId, 'period' => 'general', 'data_as_of' => now()->toIso8601String()],
                metrics: [],
                findings: [],
                recommendations: [
                    ['title' => 'Buka Bagan Akun (COA)', 'action_type' => 'navigate', 'deep_link' => route('coa.index')],
                    ['title' => 'Kelola Master Pelanggan', 'action_type' => 'navigate', 'deep_link' => route('customers.index')],
                    ['title' => 'Kelola Master Pemasok', 'action_type' => 'navigate', 'deep_link' => route('suppliers.index')],
                    ['title' => 'Pusat Peraturan', 'action_type' => 'navigate', 'deep_link' => route('regulations.index')],
                ],
                citations: [
                    ['source_type' => 'documentation', 'label' => 'Standard Operating Procedure (SOP) AKRU ERP', 'deep_link' => route('coa.index')]
                ],
                confidence: 1.0,
                riskLevel: 'low',
                requiresHumanReview: false,
                providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                toolCalls: []
            );
        }

        // 1. Kas & Bank
        if (str_contains($lowerMsg, 'kas') || str_contains($lowerMsg, 'bank') || str_contains($lowerMsg, 'saldo') || str_contains($lowerMsg, 'likuiditas')) {
            $toolData = $this->toolRegistry->executeTool($companyId, $userId, 'get_financial_summary');
            $toolCallsExecuted[] = ['tool' => 'get_financial_summary', 'status' => 'success'];

            $cash = $toolData['cash_and_bank'];
            $totalSaldo = $cash['total_balance'];

            $accLines = [];
            foreach ($cash['accounts'] as $acc) {
                $accLines[] = "• **{$acc['name']}** ({$acc['number']}): **Rp " . number_format($acc['balance'], 0, ',', '.') . "**";
            }
            $accText = !empty($accLines) ? implode("\n", $accLines) : "• Belum ada rekening bank yang terdaftar.";

            $answer = "### 💰 Posisi Saldo Kas & Bank Entitas\n\n"
                    . "Total saldo kas dan rekening bank aktif saat ini adalah **Rp " . number_format($totalSaldo, 0, ',', '.') . "**.\n\n"
                    . "Rincian saldo per rekening:\n{$accText}\n\n"
                    . "> **Rekomendasi:** Lakukan rekonsiliasi berkala dengan mutasi rekening koran fisik untuk mencegah selisih pencatatan buku besar.";

            return new AiResponse(
                answer: $answer,
                summary: "Total kas & bank: Rp " . number_format($totalSaldo, 0, ',', '.'),
                intent: 'cash_position',
                scope: ['company_id' => $companyId, 'period' => 'current', 'data_as_of' => now()->toIso8601String()],
                metrics: [
                    ['code' => 'cash_balance', 'label' => 'Total Kas & Bank', 'value' => $totalSaldo, 'unit' => 'IDR'],
                ],
                findings: [],
                recommendations: [
                    ['title' => 'Buka Rekonsiliasi Bank', 'action_type' => 'navigate', 'deep_link' => route('reconciliation.index')]
                ],
                citations: [
                    ['source_type' => 'bank_accounts', 'label' => 'Buku Kas & Rekening Bank', 'deep_link' => route('bank-accounts.index')]
                ],
                confidence: 1.0,
                riskLevel: 'low',
                requiresHumanReview: false,
                providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                toolCalls: $toolCallsExecuted
            );
        }

        // 2. Omzet, Penjualan & Laba
        if (str_contains($lowerMsg, 'omzet') || str_contains($lowerMsg, 'penjualan') || str_contains($lowerMsg, 'laba') || str_contains($lowerMsg, 'revenue') || str_contains($lowerMsg, 'profit')) {
            $toolData = $this->toolRegistry->executeTool($companyId, $userId, 'get_financial_summary');
            $toolCallsExecuted[] = ['tool' => 'get_financial_summary', 'status' => 'success'];

            $p = $toolData['profitability'];
            $revenue = $p['revenue'];
            $cogs = $p['cogs'];
            $grossProfit = $p['gross_profit'];
            $marginPct = $p['gross_margin_pct'];

            $answer = "### 📈 Kinerja Penjualan & Laba Periode Berjalan\n\n"
                    . "• **Total Omzet Penjualan:** **Rp " . number_format($revenue, 0, ',', '.') . "** ({$p['sales_count']} faktur terposting)\n"
                    . "• **Total Pembelian / HPP:** **Rp " . number_format($cogs, 0, ',', '.') . "**\n"
                    . "• **Estimasi Laba Kotor:** **Rp " . number_format($grossProfit, 0, ',', '.') . "**\n"
                    . "• **Margin Laba Kotor:** **{$marginPct}%**\n"
                    . "• **PPN Keluaran Terbit:** **Rp " . number_format($p['sales_tax_collected'], 0, ',', '.') . "**\n\n"
                    . "> Data ini dihitung dari seluruh faktur penjualan dan pembelian berstatus *posted* pada buku besar.";

            return new AiResponse(
                answer: $answer,
                summary: "Omzet: Rp " . number_format($revenue, 0, ',', '.') . ", Laba Kotor: Rp " . number_format($grossProfit, 0, ',', '.'),
                intent: 'sales_and_profitability',
                scope: ['company_id' => $companyId, 'period' => 'current_month', 'data_as_of' => now()->toIso8601String()],
                metrics: [
                    ['code' => 'revenue', 'label' => 'Omzet Penjualan', 'value' => $revenue, 'unit' => 'IDR'],
                    ['code' => 'gross_profit', 'label' => 'Laba Kotor', 'value' => $grossProfit, 'unit' => 'IDR'],
                    ['code' => 'gross_margin_pct', 'label' => 'Gross Margin', 'value' => $marginPct, 'unit' => 'percent'],
                ],
                findings: [],
                recommendations: [
                    ['title' => 'Buka Laporan Laba Rugi', 'action_type' => 'navigate', 'deep_link' => route('reports.profit-loss')]
                ],
                citations: [
                    ['source_type' => 'sales_invoices', 'label' => 'Daftar Faktur Penjualan', 'deep_link' => route('sales.index')]
                ],
                confidence: 1.0,
                riskLevel: 'low',
                requiresHumanReview: false,
                providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                toolCalls: $toolCallsExecuted
            );
        }

        // 3. Piutang (AR) & Jatuh Tempo
        if (str_contains($lowerMsg, 'piutang') || str_contains($lowerMsg, 'tempo') || str_contains($lowerMsg, 'receivable') || str_contains($lowerMsg, 'aging')) {
            $agingData = $this->toolRegistry->executeTool($companyId, $userId, 'get_ar_aging', ['limit' => 5]);
            $toolCallsExecuted[] = ['tool' => 'get_ar_aging', 'status' => 'success'];

            $summaryData = $this->toolRegistry->executeTool($companyId, $userId, 'get_financial_summary');
            $ar = $summaryData['receivables'];

            $lines = [];
            foreach ($agingData['invoices'] as $inv) {
                $status = $inv['is_overdue'] ? "⚠️ **JATUH TEMPO** ({$inv['due_date']})" : "Hingga {$inv['due_date']}";
                $lines[] = "• **{$inv['number']}** - {$inv['customer']}: **Rp " . number_format($inv['balance_due'], 0, ',', '.') . "** ({$status})";
            }
            $listText = !empty($lines) ? implode("\n", $lines) : "• Tidak ada faktur piutang yang tertunda saat ini.";

            $answer = "### ⏳ Status Piutang Usaha (Accounts Receivable)\n\n"
                    . "• **Total Piutang Belum Tertagih:** **Rp " . number_format($ar['total_ar'], 0, ',', '.') . "**\n"
                    . "• **Piutang Telah Melewati Jatuh Tempo:** **Rp " . number_format($ar['overdue_ar'], 0, ',', '.') . "**\n"
                    . "• **Jumlah Faktur Belum Lunas:** **{$ar['unpaid_invoices_count']} faktur**\n\n"
                    . "Faktur terbaru yang perlu diperhatikan:\n{$listText}\n\n"
                    . "> **Saran:** Tinjau laporan umur piutang (*aging report*) untuk memprioritaskan penagihan ke pelanggan.";

            return new AiResponse(
                answer: $answer,
                summary: "Total Piutang: Rp " . number_format($ar['total_ar'], 0, ',', '.') . " (Jatuh Tempo: Rp " . number_format($ar['overdue_ar'], 0, ',', '.') . ")",
                intent: 'ar_aging',
                scope: ['company_id' => $companyId, 'period' => 'all_open', 'data_as_of' => now()->toIso8601String()],
                metrics: [
                    ['code' => 'total_ar', 'label' => 'Total Piutang', 'value' => $ar['total_ar'], 'unit' => 'IDR'],
                    ['code' => 'overdue_ar', 'label' => 'Piutang Jatuh Tempo', 'value' => $ar['overdue_ar'], 'unit' => 'IDR'],
                ],
                findings: $ar['overdue_ar'] > 0 ? [
                    ['title' => 'Piutang Jatuh Tempo', 'severity' => 'medium', 'statement' => 'Terdapat piutang belum tertagih melewati tanggal jatuh tempo.']
                ] : [],
                recommendations: [
                    ['title' => 'Buka Laporan Umur Piutang', 'action_type' => 'navigate', 'deep_link' => route('reports.aging')]
                ],
                citations: [
                    ['source_type' => 'sales_invoices', 'label' => 'Faktur Penjualan Tertagih', 'deep_link' => route('sales.index')]
                ],
                confidence: 1.0,
                riskLevel: 'low',
                requiresHumanReview: false,
                providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                toolCalls: $toolCallsExecuted
            );
        }

        // 4. Hutang (AP)
        if (str_contains($lowerMsg, 'hutang') || str_contains($lowerMsg, 'vendor') || str_contains($lowerMsg, 'supplier') || str_contains($lowerMsg, 'payable')) {
            $apData = $this->toolRegistry->executeTool($companyId, $userId, 'get_ap_aging', ['limit' => 5]);
            $toolCallsExecuted[] = ['tool' => 'get_ap_aging', 'status' => 'success'];

            $summaryData = $this->toolRegistry->executeTool($companyId, $userId, 'get_financial_summary');
            $ap = $summaryData['payables'];

            $lines = [];
            foreach ($apData['bills'] as $b) {
                $status = $b['is_overdue'] ? "⚠️ **LEWAT TEMPO** ({$b['due_date']})" : "Jatuh tempo {$b['due_date']}";
                $lines[] = "• **{$b['number']}** - {$b['vendor']}: **Rp " . number_format($b['balance_due'], 0, ',', '.') . "** ({$status})";
            }
            $listText = !empty($lines) ? implode("\n", $lines) : "• Tidak ada tagihan hutang vendor yang belum lunas.";

            $answer = "### 📋 Status Hutang Usaha Pemasok (Accounts Payable)\n\n"
                    . "• **Total Hutang Aktif:** **Rp " . number_format($ap['total_ap'], 0, ',', '.') . "**\n"
                    . "• **Hutang Melewati Jatuh Tempo:** **Rp " . number_format($ap['overdue_ap'], 0, ',', '.') . "**\n"
                    . "• **Tagihan Belum Lunas:** **{$ap['unpaid_bills_count']} tagihan**\n\n"
                    . "Daftar tagihan pemasok:\n{$listText}";

            return new AiResponse(
                answer: $answer,
                summary: "Total Hutang: Rp " . number_format($ap['total_ap'], 0, ',', '.'),
                intent: 'ap_aging',
                scope: ['company_id' => $companyId, 'period' => 'all_open', 'data_as_of' => now()->toIso8601String()],
                metrics: [
                    ['code' => 'total_ap', 'label' => 'Total Hutang', 'value' => $ap['total_ap'], 'unit' => 'IDR'],
                ],
                findings: [],
                recommendations: [
                    ['title' => 'Buka Faktur Pembelian', 'action_type' => 'navigate', 'deep_link' => route('purchases.index')]
                ],
                citations: [
                    ['source_type' => 'purchase_invoices', 'label' => 'Faktur Pembelian Pemasok', 'deep_link' => route('purchases.index')]
                ],
                confidence: 1.0,
                riskLevel: 'low',
                requiresHumanReview: false,
                providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                toolCalls: $toolCallsExecuted
            );
        }

        // 5. Rekomendasi Akun COA (COA Suggestion)
        if (str_contains($lowerMsg, 'akun') || str_contains($lowerMsg, 'coa') || str_contains($lowerMsg, 'masuk apa') || str_contains($lowerMsg, 'jurnalnya')) {
            $account = Account::where('company_id', $companyId)->where('is_active', true)
                ->where(function($q) use ($lowerMsg) {
                    if (str_contains($lowerMsg, 'bensin') || str_contains($lowerMsg, 'bbm') || str_contains($lowerMsg, 'kendaraan')) {
                        $q->where('name', 'like', '%kendaraan%')->orWhere('name', 'like', '%bensin%')->orWhere('code', '6300');
                    } elseif (str_contains($lowerMsg, 'internet') || str_contains($lowerMsg, 'wifi') || str_contains($lowerMsg, 'listrik')) {
                        $q->where('name', 'like', '%utilitas%')->orWhere('name', 'like', '%listrik%')->orWhere('code', '6100');
                    } elseif (str_contains($lowerMsg, 'sewa')) {
                        $q->where('name', 'like', '%sewa%')->orWhere('code', '6200');
                    } elseif (str_contains($lowerMsg, 'gaji') || str_contains($lowerMsg, 'upah')) {
                        $q->where('name', 'like', '%gaji%')->orWhere('code', '6000');
                    } else {
                        $q->where('type', 'expense');
                    }
                })->first();

            if (!$account) {
                $account = Account::where('company_id', $companyId)->where('type', 'expense')->first();
            }

            if ($account) {
                $answer = "### 🎯 Rekomendasi Akun COA & Jurnal\n\n"
                        . "Berdasarkan uraian **\"{$msg}\"**, akun yang direkomendasikan adalah:\n\n"
                        . "• **Akun Terpilih:** **{$account->code} — {$account->name}** (Tipe: " . strtoupper($account->type) . ")\n"
                        . "• **Saran Posisi Jurnal:**\n"
                        . "  - **[Debit]** `{$account->code} - {$account->name}`\n"
                        . "  - **[Kredit]** `1110 - Kas / Bank Operasional`\n"
                        . "• **Ketentuan Standar (SAK):** Diakui sebagai beban operasional periode berjalan sesuai basis akrual (*matching principle*).";

                return new AiResponse(
                    answer: $answer,
                    summary: "Rekomendasi akun: {$account->code} - {$account->name}",
                    intent: 'account_suggestion',
                    scope: ['company_id' => $companyId, 'period' => 'current', 'data_as_of' => now()->toIso8601String()],
                    metrics: [],
                    findings: [],
                    recommendations: [
                        ['title' => 'Catat Pengeluaran Kas', 'action_type' => 'navigate', 'deep_link' => route('cash-transactions.create')]
                    ],
                    citations: [
                        ['source_type' => 'accounts', 'label' => 'Bagan Akun (COA)', 'deep_link' => route('accounts.index')]
                    ],
                    confidence: 0.92,
                    riskLevel: 'low',
                    requiresHumanReview: true,
                    providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                    toolCalls: []
                );
            }
        }

        // 5.4 Pembelian Impor & Komponen Pajak Kepabeanan (Import Purchases & Customs Taxes)
        if (str_contains($lowerMsg, 'impor') || str_contains($lowerMsg, 'import') || str_contains($lowerMsg, 'bea masuk') || str_contains($lowerMsg, 'pabean') || str_contains($lowerMsg, 'cif') || str_contains($lowerMsg, 'pib')) {
            $answer = "### 🚢 Komponen Pajak & Pungutan Resmi atas Pembelian Impor Barang\n\n"
                    . "Berdasarkan ketentuan kepabeanan (**UU No. 17/2006**) dan perpajakan Indonesia (**UU HPP & PMK No. 41/2022**), saat Anda melakukan pembelian impor (impor untuk dipakai), komponen pungutan resmi yang timbul adalah:\n\n"
                    . "1. **Bea Masuk (Import Duty):**\n"
                    . "   • Dihitung dari Nilai Pabean / **CIF** (*Cost + Insurance + Freight*) dikonversi ke Rupiah menggunakan Kurs Pajak/Kepabeanan Menkeu saat pendaftaran PIB.\n"
                    . "   • Tarif bervariasi (0%, 5%, 10%, 15%+) sesuai klasifikasi kode pos tarif **HS Code** pada BTKI (*Buku Tarif Kepabeanan Indonesia*).\n"
                    . "   • **Perlakuan Akuntansi:** Dikapitalisasi sebagai penambah harga perolehan **Persediaan / HPP** (atau Aset Tetap).\n\n"
                    . "2. **PPN Impor (Pajak Pertambahan Nilai):**\n"
                    . "   • Tarif **11%** (dan 12% sesuai regulasi) dari **Nilai Impor**.\n"
                    . "   • *Rumus Nilai Impor:* `CIF + Bea Masuk (+ Bea Masuk Tambahan jika ada)`.\n"
                    . "   • **Perlakuan Akuntansi:** Merupakan **Pajak Masukan (PPN Masukan Impor)** yang dapat dikreditkan pada SPT Masa PPN menggunakan dokumen PIB dan Bukti Penerimaan Negara (BPN/Billing SSPCP) ber-NTPN sah.\n\n"
                    . "3. **PPh Pasal 22 Impor (Pajak Penghasilan Impor):**\n"
                    . "   • Dipungut oleh Ditjen Bea Cukai dari **Nilai Impor**.\n"
                    . "   • **Tarif:**\n"
                    . "     - **2,5%** dari Nilai Impor bagi importir yang memiliki **API (Angka Pengenal Importir)**.\n"
                    . "     - **7,5%** dari Nilai Impor bagi importir yang **TIDAK memiliki API**.\n"
                    . "     - **0,5%** untuk komoditas tertentu (kedelai, gandum, tepung terigu) bagi pemilik API.\n"
                    . "     - **10%** untuk barang konsumsi tertentu (Lampiran I PMK 34/2017 jo PMK 41/2022).\n"
                    . "   • **Perlakuan Akuntansi:** Dicatat di sisi DEBIT sebagai **Uang Muka PPh Pasal 22 (Aset Lancar / Prepaid Tax)**. Pajak ini menjadi kredit pajak resmi yang mengurangi PPh Terutang di SPT Tahunan Badan (Form 1771).\n\n"
                    . "4. **PPnBM Impor (Pajak Penjualan atas Barang Mewah)** *(Jika Barang Tergolong Mewah)*:\n"
                    . "   • Tarif 10% s.d 125% dari Nilai Impor. Tidak dapat dikreditkan (menambah HPP/biaya perolehan).\n\n"
                    . "---\n\n"
                    . "### 📝 Simulasi Pencatatan Jurnal Akuntansi (Double-Entry)\n"
                    . "Saat membayar billing SSPCP / BPN di kas negara:\n"
                    . "• **[DEBIT] Persediaan / HPP (Bea Masuk):** `Rp [Nilai Bea Masuk]` *(Kapitalisasi)*\n"
                    . "• **[DEBIT] PPN Masukan Impor:** `Rp [Nilai PPN Impor]` *(Dapat Dikreditkan)*\n"
                    . "• **[DEBIT] Uang Muka PPh Pasal 22:** `Rp [Nilai PPh 22]` *(Kredit Pajak Tahunan)*\n"
                    . "• **[KREDIT] Kas / Rekening Bank:** `Rp [Total Billing SSPCP]`\n\n"
                    . "> **Dokumen Wajib:** Pemberitahuan Impor Barang (**PIB**) berstatus Surat Persetujuan Pengeluaran Barang (**SPPB**) dan Bukti Penerimaan Negara (**BPN**) dengan **NTPN**.";

            return new AiResponse(
                answer: $answer,
                summary: "Komponen impor: Bea Masuk (HS Code), PPN Impor 11%, PPh 22 Impor (2.5% API / 7.5% Non-API), dan PPnBM",
                intent: 'import_taxation',
                scope: ['company_id' => $companyId, 'period' => 'current', 'data_as_of' => now()->toIso8601String()],
                metrics: [
                    ['code' => 'ppn_impor_rate', 'label' => 'PPN Impor', 'value' => 11, 'unit' => 'percent'],
                    ['code' => 'pph_22_api_rate', 'label' => 'PPh 22 (Ber-API)', 'value' => 2.5, 'unit' => 'percent'],
                    ['code' => 'pph_22_non_api_rate', 'label' => 'PPh 22 (Non-API)', 'value' => 7.5, 'unit' => 'percent'],
                ],
                findings: [],
                recommendations: [
                    ['title' => 'Lihat Regulasi Impor & Kepabeanan', 'action_type' => 'navigate', 'deep_link' => route('regulations.index', ['category' => 'kepabeanan', 'q' => 'impor'])],
                    ['title' => 'Buka Rekonsiliasi Fiskal (SPT 1771)', 'action_type' => 'navigate', 'deep_link' => route('tax.fiscal')]
                ],
                citations: [
                    ['source_type' => 'regulations', 'label' => 'PMK No. 41/PMK.010/2022 (PPh 22 Impor)', 'deep_link' => route('regulations.index', ['q' => '41/PMK.010/2022'])],
                    ['source_type' => 'regulations', 'label' => 'UU No. 17 Tahun 2006 (Kepabeanan & Nilai Pabean)', 'deep_link' => route('regulations.index', ['q' => '17 Tahun 2006'])]
                ],
                confidence: 1.0,
                riskLevel: 'low',
                requiresHumanReview: false,
                providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                toolCalls: []
            );
        }

        // 5.5 Pencarian Pengetahuan Regulasi Terintegrasi
        if (str_contains($lowerMsg, 'peraturan') || str_contains($lowerMsg, 'aturan') || str_contains($lowerMsg, 'psak') || str_contains($lowerMsg, 'pmk') || str_contains($lowerMsg, 'sewa') || str_contains($lowerMsg, 'natura') || str_contains($lowerMsg, 'coretax')) {
            $matchingReg = \App\Models\Regulation::search($msg)->first();
            if ($matchingReg) {
                $points = "";
                if (!empty($matchingReg->key_points)) {
                    foreach ($matchingReg->key_points as $kp) {
                        $points .= "• {$kp}\n";
                    }
                }

                $answer = "### 📜 {$matchingReg->number}: {$matchingReg->title}\n\n"
                        . "**Tentang:** {$matchingReg->about}\n"
                        . "**Kategori:** " . strtoupper($matchingReg->category) . " (" . $matchingReg->level . " Tahun {$matchingReg->year})\n\n"
                        . "#### 💡 Ringkasan Ketentuan:\n{$matchingReg->summary}\n\n";

                if (!empty($points)) {
                    $answer .= "#### 🔍 Poin-Poin Kunci:\n{$points}\n";
                }

                if (!empty($matchingReg->accounting_implications)) {
                    $answer .= "#### 📘 Panduan Perlakuan Akuntansi (SAK):\n{$matchingReg->accounting_implications}\n\n";
                }

                if (!empty($matchingReg->tax_implications)) {
                    $answer .= "#### ⚖️ Kepatuhan & Implikasi Perpajakan:\n{$matchingReg->tax_implications}\n\n";
                }

                return new AiResponse(
                    answer: $answer,
                    summary: "Regulasi: {$matchingReg->number} ({$matchingReg->title})",
                    intent: 'regulation_guidance',
                    scope: ['company_id' => $companyId, 'period' => 'current', 'data_as_of' => now()->toIso8601String()],
                    metrics: [],
                    findings: [],
                    recommendations: [
                        ['title' => 'Buka Pusat Peraturan', 'action_type' => 'navigate', 'deep_link' => route('regulations.index', ['q' => $matchingReg->code])]
                    ],
                    citations: [
                        ['source_type' => 'regulations', 'label' => $matchingReg->number, 'deep_link' => route('regulations.index', ['q' => $matchingReg->code])]
                    ],
                    confidence: 0.98,
                    riskLevel: 'low',
                    requiresHumanReview: false,
                    providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                    toolCalls: []
                );
            }
        }

        // 5.6 Kepatuhan & Tarif Pajak Umum (Tax Rates & Rules)
        if (str_contains($lowerMsg, 'pajak') || str_contains($lowerMsg, 'pph') || str_contains($lowerMsg, 'ppn') || str_contains($lowerMsg, 'tarif')) {
            $answer = "### 🏛️ Ketentuan Tarif Pajak Indonesia Terkini\n\n"
                    . "Berikut ringkasan ketentuan perpajakan yang terintegrasi pada sistem AKRU:\n\n"
                    . "1. **Tarif PPh Pasal 21 (PP 58/2023 - Skema TER):**\n"
                    . "   • Kategori TER Bulanan (A, B, C) untuk pegawai tetap berdasarkan PTKP.\n"
                    . "   • TER Harian untuk pegawai tidak tetap.\n"
                    . "   • Tarif Pasal 17 ayat (1) huruf a UU HPP untuk perhitungan masa pajak terakhir (Desember): 5% (s.d 60jt), 15% (>60-250jt), 25% (>250-500jt), 30% (>500jt-5M), 35% (>5M).\n\n"
                    . "2. **Tarif PPh Pasal 23:**\n"
                    . "   • **2%** atas imbalan jasa manajemen, jasa konsultan, jasa teknik, serta sewa aktiva selain tanah/bangunan.\n"
                    . "   • **15%** atas deviden, bunga, royalti, dan hadiah/penghargaan.\n"
                    . "   • Pemotong wajib memiliki NPWP; bagi pihak yang tidak ber-NPWP dikenakan tarif 100% lebih tinggi.\n\n"
                    . "3. **Tarif PPN (UU Harmonisasi Peraturan Perpajakan):**\n"
                    . "   • Tarif umum 11% (berlaku saat ini) dan 12% sesuai regulasi nasional.\n\n"
                    . "> **Catatan Kepatuhan:** Seluruh bukti potong dan faktur pajak dapat diekspor langsung ke format Coretax DJP pada menu Tax Control.";

            return new AiResponse(
                answer: $answer,
                summary: "Panduan tarif PPh 21, PPh 23 (2%), dan PPN",
                intent: 'tax_rates',
                scope: ['company_id' => $companyId, 'period' => 'current', 'data_as_of' => now()->toIso8601String()],
                metrics: [],
                findings: [],
                recommendations: [
                    ['title' => 'Buka Modul PPh Unifikasi', 'action_type' => 'navigate', 'deep_link' => route('tax.pph')]
                ],
                citations: [
                    ['source_type' => 'tax_codes', 'label' => 'Master Kode Pajak', 'deep_link' => route('tax.pph')]
                ],
                confidence: 0.98,
                riskLevel: 'low',
                requiresHumanReview: false,
                providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
                toolCalls: []
            );
        }

        // 6. Default Fallback Guidance
        $answer = "### 🏛️ Asisten & Konsultasi Cerdas AKRU AI (Native Engine)\n\n"
                . "Pertanyaan Anda mengenai **\"{$msg}\"** telah diproses oleh modul **AKRU Native AI**.\n\n"
                . "Sebagai asisten keuangan dan kepatuhan bisnis, Anda dapat menanyakan:\n"
                . "1. **Posisi Kas & Bank:** *\"Berapa saldo kas dan bank saat ini?\"*\n"
                . "2. **Omzet & Laba:** *\"Berapa total penjualan dan laba kotor bulan ini?\"*\n"
                . "3. **Piutang & Hutang:** *\"Apakah ada piutang jatuh tempo?\"* atau *\"Berapa hutang ke supplier?\"*\n"
                . "4. **Rekomendasi Akun COA:** *\"Beli bensin dan servis mobil dinas masuk akun apa?\"*\n"
                . "5. **Kepatuhan Pajak:** *\"Berapa tarif PPh 21 TER dan PPh 23?\"*\n\n"
                . "> **Prinsip Human-in-the-Loop:** Seluruh hasil analisis dan saran AKRU AI disajikan sebagai bahan pertimbangan keputusan. Otorisasi posting jurnal dan pelaporan pajak final tetap berada di bawah kendali manajemen pengguna.";

        return new AiResponse(
            answer: $answer,
            summary: "Bantuan navigasi dan konsultasi AKRU AI",
            intent: 'general_guidance',
            scope: ['company_id' => $companyId, 'period' => 'current', 'data_as_of' => now()->toIso8601String()],
            metrics: [],
            findings: [],
            recommendations: [],
            citations: [],
            confidence: 0.95,
            riskLevel: 'low',
            requiresHumanReview: false,
            providerDisclosure: ['provider' => 'akru_native', 'model' => 'native-rules-v1', 'fallback_used' => false],
            toolCalls: []
        );
    }
}
