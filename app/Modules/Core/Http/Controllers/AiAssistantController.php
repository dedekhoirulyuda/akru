<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Models\JournalSet;
use App\Modules\Finance\Models\CashTransaction;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Modules\MasterData\Models\TaxCode;
use App\Modules\Platform\Models\PlatformSetting;
use App\Modules\Purchase\Models\PurchaseInvoice;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\AiChatUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AiAssistantController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        // 1. Anomaly Detection Scan
        $anomalies = [];

        // Check for duplicate invoice amounts on the same day
        $duplicateInvoices = SalesInvoice::where('company_id', $companyId)
            ->select('invoice_date', 'total_amount', DB::raw('COUNT(*) as count'))
            ->groupBy('invoice_date', 'total_amount')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateInvoices as $dup) {
            $anomalies[] = [
                'type' => 'Potensi Duplikasi Faktur',
                'severity' => 'Sedang',
                'description' => "Ditemukan {$dup->count} faktur dengan nominal identik (Rp " . number_format($dup->total_amount, 0, ',', '.') . ") pada tanggal {$dup->invoice_date}.",
                'recommendation' => 'Periksa apakah terjadi double-entry transaksi penjualan.',
            ];
        }

        // Check for round number payments (potential cash withdrawal anomaly)
        $roundPayments = CashTransaction::where('company_id', $companyId)
            ->where('total_amount', '>=', 5000000)
            ->get()
            ->filter(function ($rp) {
                return (int) $rp->total_amount > 0 && ((int) $rp->total_amount % 1000000 === 0);
            });

        foreach ($roundPayments as $rp) {
            $anomalies[] = [
                'type' => 'Transaksi Angka Bulat Besar',
                'severity' => 'Rendah',
                'description' => "Pengeluaran kas bernilai bulat sempurna: Rp " . number_format($rp->total_amount, 0, ',', '.') . " [{$rp->transaction_number}].",
                'recommendation' => 'Pastikan kuitansi fisik atau bukti transfer terlampir lengkap.',
            ];
        }

        if (empty($anomalies)) {
            $anomalies[] = [
                'type' => 'Kesehatan Data Baik',
                'severity' => 'Info',
                'description' => 'Tidak ditemukan anomali kritis atau transaksi duplikat pada periode aktif.',
                'recommendation' => 'Sistem pencatatan berjalan normal dan terkendali.',
            ];
        }

        // 2. Summary stats for the AI context
        $bankBalance = BankAccount::where('company_id', $companyId)->where('is_active', true)->sum('current_balance');
        $salesCount = SalesInvoice::where('company_id', $companyId)->count();
        $totalRevenue = SalesInvoice::where('company_id', $companyId)->whereIn('status', ['posted', 'paid'])->sum('total_amount');

        // 3. AI Chat Quota tracking (Trial limit enforcement)
        $company = Company::find($companyId);
        $chatLimit = $company ? $company->getAiChatLimit() : null;
        $todayUsage = AiChatUsage::getTodayUsage($companyId);
        $remainingQuota = $chatLimit !== null ? max(0, $chatLimit - $todayUsage) : null;

        $aiQuota = [
            'limit' => $chatLimit,
            'used' => $todayUsage,
            'remaining' => $remainingQuota,
            'is_unlimited' => ($chatLimit === null),
            'plan_name' => $company?->subscription?->plan?->name ?? 'Free Trial',
            'is_trial' => $company ? $company->isTrial() : true,
            'engine_name' => ($company && !$company->isTrial()) ? 'Gemini / OpenAI Pro Hybrid' : 'AKRU FinLogic Core (Logic Engine)',
        ];

        return view('core.ai.index', compact('anomalies', 'bankBalance', 'salesCount', 'totalRevenue', 'aiQuota'));
    }

    /**
     * Smart COA Suggestion Endpoint
     */
    public function suggestCategory(Request $request)
    {
        $companyId = session('current_company_id');
        $query = trim($request->get('query', ''));

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan masukkan deskripsi atau kata kunci transaksi.',
            ]);
        }

        $match = $this->resolveAccountSuggestion($companyId, $query);

        return response()->json([
            'success' => true,
            'suggested_account' => $match['account'] ? [
                'id' => $match['account']->id,
                'name' => $match['account']->name,
                'code' => $match['account']->code,
                'type' => $match['account']->type,
                'normal_balance' => $match['account']->normal_balance,
            ] : null,
            'confidence' => $match['confidence'],
            'reasoning' => $match['reasoning'],
            'journal_hint' => $match['journal_hint'],
        ]);
    }

    /**
     * Interactive Financial Q&A Chatbot Endpoint
     * Equipped with Strict Domain Guardrails (Accounting, Finance & Tax only)
     * Enforces Free Trial & Limited Plan Daily Quotas
     */
    public function chat(Request $request)
    {
        $companyId = session('current_company_id') ?? auth()->user()->current_company_id ?? 1;
        $company = Company::find($companyId);
        $chatLimit = $company ? $company->getAiChatLimit() : null;
        $todayUsage = AiChatUsage::getTodayUsage($companyId);

        // Enforce daily quota limit for Free Trial / Limited plans
        if ($chatLimit !== null && $todayUsage >= $chatLimit) {
            $planName = $company?->subscription?->plan?->name ?? 'Free Trial';
            return response()->json([
                'success' => true,
                'quota_exceeded' => true,
                'topic' => 'trial_quota_exceeded',
                'quota' => [
                    'limit' => $chatLimit,
                    'used' => $todayUsage,
                    'remaining' => 0,
                    'is_unlimited' => false,
                ],
                'reply' => "### ⏳ Batas Kuota Chat Harian Tercapai ({$todayUsage}/{$chatLimit})\n\n"
                    . "Halo! Akun entitas Anda saat ini menggunakan paket **{$planName}** dengan alokasi **{$chatLimit} sesi konsultasi AI per hari**.\n\n"
                    . "Penggunaan chat AI hari ini telah mencapai batas kuota maksimum ({$todayUsage}/{$chatLimit}). Kuota harian Anda akan otomatis di-reset pada **pukul 00:00 WIB tengah malam nanti**.\n\n"
                    . "🚀 **Ingin akses AKRU AI tanpa batas (Unlimited)?**\n"
                    . "Tingkatkan ke paket **AKRU Enterprise** atau **AKRU Professional** untuk menikmati konsultasi akuntansi, deteksi anomali, kalkulasi pajak, dan integrasi penuh tanpa batasan kuota harian.\n\n"
                    . "[👉 Klik di Sini untuk Upgrade Paket Langganan](" . route('subscription.index') . ")",
            ]);
        }

        $response = $this->generateChatResponse($request, $companyId);

        // Attach real-time quota data to response & record usage
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            if (!empty($data['success']) && ($data['topic'] ?? '') !== 'out_of_domain_guardrail') {
                $used = AiChatUsage::recordUsage($companyId, auth()->id());
            } else {
                $used = AiChatUsage::getTodayUsage($companyId);
            }

            $remaining = $chatLimit !== null ? max(0, $chatLimit - $used) : null;
            $data['quota'] = [
                'limit' => $chatLimit,
                'used' => $used,
                'remaining' => $remaining,
                'is_unlimited' => ($chatLimit === null),
            ];
            $response->setData($data);
        }

        return $response;
    }

    protected function generateChatResponse(Request $request, int $companyId)
    {
        $message = trim($request->input('message', ''));

        if (empty($message)) {
            return response()->json([
                'success' => false,
                'reply' => 'Silakan sampaikan pertanyaan atau instruksi seputar keuangan, akuntansi, atau perpajakan entitas Anda.',
            ]);
        }

        $lowerMsg = strtolower($message);

        // 1. DOMAIN GUARDRAILS: Check if prompt is completely outside the 5 business pillars
        if ($this->isOutOfDomainQuery($lowerMsg)) {
            $reply = "### 🛡️ Batasan Ranah Keahlian AKRU AI\n\n"
                   . "Saya adalah **AKRU AI**, asisten kecerdasan buatan terpadu untuk **Tata Kelola Finansial & Bisnis Enterprise**.\n\n"
                   . "Pertanyaan Anda mengenai subjek di luar ranah bisnis dan tata kelola keuangan berada di luar batasan kewenangan sistem saya.\n\n"
                   . "Sesuai mandat operasional, Anda memiliki kebebasan penuh untuk berkonsultasi, menganalisis, dan bertanya pada **5 Pilar Keahlian Utama**:\n\n"
                   . "1. **Akuntansi & Pembukuan:** Standar SAK/PSAK, penataan bagan akun (COA), rekomendasi jurnal Debit/Kredit, penyusutan aset tetap, dan penutupan buku periode.\n"
                   . "2. **Manajemen Keuangan & Likuiditas:** Posisi kas & bank riil, piutang usaha (aging & penagihan), hutang vendor, modal kerja, dan rasio likuiditas.\n"
                   . "3. **Kepatuhan & Perencanaan Perpajakan:** Ketentuan tarif PPh 21 (TER PP 58/2023), PPh 22, PPh 23, PPh 4(2), PPh 25/Badan, Pajak Dividen (0% reinvestasi vs 10% final), PPN 11%/12%, dan integrasi Coretax DJP.\n"
                   . "4. **Ekspor & Impor (Kepabeanan):** Bea Masuk, PPN Impor, PPh 22 Impor, nilai CIF, dokumen PIB & PEB, PPN Ekspor 0%, Bea Keluar, Incoterms, dan perlakuan kurs valas (PSAK 10).\n"
                   . "5. **Pengambilan Keputusan Bisnis:** Analisis Titik Impas (BEP), evaluasi Sewa vs Beli (Lease vs Buy), penetapan harga jual & margin (Pricing Strategy), analisis CAPEX vs OPEX, dan kelayakan investasi usaha.\n\n"
                   . "*Silakan ajukan pertanyaan atau instruksi analisis yang berkaitan dengan salah satu pilar bisnis di atas.*";

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'topic' => 'out_of_domain_guardrail',
            ]);
        }


        $history = $request->input('history', []);

        // 2. OPTIONAL DEVELOPER LLM PASS-THROUGH (PAID PLANS ONLY)
        // Free Trial strictly uses AKRU FinLogic Core to protect Gemini & OpenAI API quotas!
        $company = Company::find($companyId);
        $isTrial = $company ? $company->isTrial() : true;

        if (!$isTrial) {
            $geminiKey = PlatformSetting::get('ai_gemini_api_key') ?: env('GEMINI_API_KEY');
            $geminiModel = PlatformSetting::get('ai_default_model') ?: env('GEMINI_MODEL', 'gemini-1.5-flash');
            $openaiKey = PlatformSetting::get('ai_openai_api_key') ?: env('OPENAI_API_KEY');
            $openaiModel = env('OPENAI_MODEL', 'gpt-4o-mini');

            $aiProvider = PlatformSetting::get('ai_provider') ?: env('AI_DEFAULT_PROVIDER', ($geminiKey ? 'gemini' : ($openaiKey ? 'openai' : null)));

            if ($aiProvider === 'gemini' && $geminiKey) {
                $llmResponse = $this->callGeminiApi($companyId, $message, $history);
                if ($llmResponse) {
                    return response()->json([
                        'success' => true,
                        'reply' => $llmResponse,
                        'topic' => 'llm_generative_gemini',
                        'engine' => 'Google Gemini (' . $geminiModel . ')',
                    ]);
                }
                // Auto-fallback to OpenAI if Gemini fails or rate limits
                if ($openaiKey) {
                    $llmResponse = $this->callOpenAiApi($companyId, $message, $history);
                    if ($llmResponse) {
                        return response()->json([
                            'success' => true,
                            'reply' => $llmResponse,
                            'topic' => 'llm_generative_openai_fallback',
                            'engine' => 'ChatGPT (' . $openaiModel . ')',
                        ]);
                    }
                }
            } elseif ($aiProvider === 'openai' && $openaiKey) {
                $llmResponse = $this->callOpenAiApi($companyId, $message, $history);
                if ($llmResponse) {
                    return response()->json([
                        'success' => true,
                        'reply' => $llmResponse,
                        'topic' => 'llm_generative_openai',
                        'engine' => 'ChatGPT (' . $openaiModel . ')',
                    ]);
                }
                // Auto-fallback to Gemini if OpenAI fails
                if ($geminiKey) {
                    $llmResponse = $this->callGeminiApi($companyId, $message, $history);
                    if ($llmResponse) {
                        return response()->json([
                            'success' => true,
                            'reply' => $llmResponse,
                            'topic' => 'llm_generative_gemini_fallback',
                            'engine' => 'Google Gemini (' . $geminiModel . ')',
                        ]);
                    }
                }
            }
        }

        // 3. AUTONOMOUS FINANCIAL MATH & TAX SIMULATOR (Calculators for PPh 21, PPh 23, Penyusutan, BEP)
        $calcReply = $this->resolveFinancialCalculation($lowerMsg, $companyId);
        if ($calcReply) {
            return response()->json([
                'success' => true,
                'reply' => $calcReply,
                'topic' => 'financial_math_calculator',
                'engine' => 'AKRU FinLogic Engine v1.2',
            ]);
        }

        // 4. DEEP DOMAIN KNOWLEDGE ENGINE (Covering SAK, Taxes, Export/Import, and Business Decision Making)
        $domainReply = $this->resolveGeneralDomainKnowledge($lowerMsg);
        if ($domainReply) {
            return response()->json([
                'success' => true,
                'reply' => $domainReply,
                'topic' => 'business_domain_expert',
                'engine' => 'AKRU Domain Logic Engine v2.0',
            ]);
        }

        // 5. KAS & BANK QUERIES (Live Ledger Grounding)
        if (str_contains($lowerMsg, 'kas') || str_contains($lowerMsg, 'bank') || str_contains($lowerMsg, 'saldo') || str_contains($lowerMsg, 'uang') || str_contains($lowerMsg, 'likuiditas')) {
            $bankAccounts = BankAccount::where('company_id', $companyId)->where('is_active', true)->get();
            $totalSaldo = $bankAccounts->sum('current_balance');

            $breakdown = [];
            foreach ($bankAccounts as $acc) {
                $breakdown[] = "• **{$acc->bank_name}** ({$acc->account_number}): **Rp " . number_format($acc->current_balance, 0, ',', '.') . "**";
            }

            $listStr = !empty($breakdown) ? implode("\n", $breakdown) : "• Belum ada rekening bank yang terdaftar.";

            $reply = "### Posisi Saldo Kas & Bank Saat Ini\n\n"
                   . "Total likuiditas kas & bank terdaftar adalah **Rp " . number_format($totalSaldo, 0, ',', '.') . "**.\n\n"
                   . "Rincian per rekening:\n" . $listStr . "\n\n"
                   . "> **Saran Manajemen Likuiditas:** Pastikan melakukan rekonsiliasi bank secara berkala pada menu **Kas & Bank > Rekonsiliasi Bank** untuk mencocokkan mutasi fisik rekening koran dengan buku besar.";

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'topic' => 'cash_bank',
            ]);
        }

        // 4. OMZET & PENJUALAN QUERIES (Live Ledger Grounding)
        if (str_contains($lowerMsg, 'omzet') || str_contains($lowerMsg, 'penjualan') || str_contains($lowerMsg, 'sales') || str_contains($lowerMsg, 'pendapatan') || str_contains($lowerMsg, 'revenue')) {
            $currentMonth = date('Y-m');
            $invoices = SalesInvoice::where('company_id', $companyId)
                ->where('invoice_date', 'like', "{$currentMonth}%")
                ->whereIn('status', ['posted', 'paid', 'partially_paid'])
                ->get();

            $totalOmzet = $invoices->sum('total_amount');
            $totalPpn = $invoices->sum('tax_amount');
            $fakturCount = $invoices->count();

            $reply = "### Kinerja Penjualan & Omzet (Bulan Berjalan: " . date('F Y') . ")\n\n"
                   . "• **Total Omzet Penjualan Bersih:** Rp " . number_format($totalOmzet, 0, ',', '.') . "\n"
                   . "• **PPN Keluaran (11%) Terbit:** Rp " . number_format($totalPpn, 0, ',', '.') . "\n"
                   . "• **Jumlah Faktur Penjualan Terbit:** {$fakturCount} transaksi\n\n"
                   . "Seluruh transaksi penjualan berstatus *posted* otomatis memperbarui buku besar akun Pendapatan (4100) dan subledger piutang.";

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'topic' => 'sales',
            ]);
        }

        // 5. PIUTANG & STATUS JATUH TEMPO (Live Ledger Grounding)
        if (str_contains($lowerMsg, 'piutang') || str_contains($lowerMsg, 'tempo') || str_contains($lowerMsg, 'tagihan pelanggan') || str_contains($lowerMsg, 'receivable') || str_contains($lowerMsg, 'dso')) {
            $unpaidInvoices = SalesInvoice::with('contact')
                ->where('company_id', $companyId)
                ->whereIn('status', ['posted', 'partially_paid'])
                ->get();

            $totalPiutang = $unpaidInvoices->sum(fn($i) => $i->total_amount - ($i->paid_amount ?? 0));
            $overdueCount = $unpaidInvoices->filter(fn($i) => $i->due_date && $i->due_date < date('Y-m-d'))->count();

            $reply = "### Status Piutang Usaha (Accounts Receivable)\n\n"
                   . "• **Total Piutang Belum Tertagih:** Rp " . number_format($totalPiutang, 0, ',', '.') . "\n"
                   . "• **Faktur Menunggu Pelunasan:** " . $unpaidInvoices->count() . " faktur\n"
                   . "• **Faktur Telah Melewati Jatuh Tempo:** {$overdueCount} faktur\n\n"
                   . "> **Rekomendasi Penagihan:** Periksa riwayat umur piutang pada menu **Laporan > Umur Piutang (Aging Report)** untuk mengidentifikasi piutang macet yang memerlukan surat pengingat (*dunning letter*).";

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'topic' => 'receivables',
            ]);
        }

        // 6. HUTANG USAHA & VENDOR (Live Ledger Grounding)
        if (str_contains($lowerMsg, 'hutang') || str_contains($lowerMsg, 'supplier') || str_contains($lowerMsg, 'pemasok') || str_contains($lowerMsg, 'payable') || str_contains($lowerMsg, 'tagihan vendor')) {
            $unpaidBills = PurchaseInvoice::with('contact')
                ->where('company_id', $companyId)
                ->whereIn('status', ['posted', 'partially_paid'])
                ->get();

            $totalHutang = $unpaidBills->sum(fn($i) => $i->total_amount - ($i->paid_amount ?? 0));

            $reply = "### Status Hutang Usaha Pemasok (Accounts Payable)\n\n"
                   . "• **Total Hutang Aktif:** Rp " . number_format($totalHutang, 0, ',', '.') . "\n"
                   . "• **Jumlah Tagihan Pemasok Belum Lunas:** " . $unpaidBills->count() . " tagihan\n\n"
                   . "> **Saran Aliran Kas:** Rencanakan skedul pembayaran hutang pada menu **Keuangan > Proyeksi Arus Kas** agar saldo kas operasional tetap mencukupi saat jatuh tempo.";

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'topic' => 'payables',
            ]);
        }

        // 7. LABA RUGI & PROFITABILITAS (Live Ledger Grounding)
        if (str_contains($lowerMsg, 'laba') || str_contains($lowerMsg, 'rugi') || str_contains($lowerMsg, 'profit') || str_contains($lowerMsg, 'margin') || str_contains($lowerMsg, 'hpp')) {
            $totalRevenue = SalesInvoice::where('company_id', $companyId)->whereIn('status', ['posted', 'paid'])->sum('total_amount');
            $totalPurchase = PurchaseInvoice::where('company_id', $companyId)->whereIn('status', ['posted', 'paid'])->sum('total_amount');
            $estProfit = $totalRevenue - $totalPurchase;
            $marginPct = $totalRevenue > 0 ? round(($estProfit / $totalRevenue) * 100, 1) : 0;

            $reply = "### Estimasi Kinerja Laba Rugi & Margin Usaha\n\n"
                   . "• **Total Pendapatan Terposting:** Rp " . number_format($totalRevenue, 0, ',', '.') . "\n"
                   . "• **Total Beban Pembelian / HPP:** Rp " . number_format($totalPurchase, 0, ',', '.') . "\n"
                   . "• **Estimasi Laba Kotor:** Rp " . number_format($estProfit, 0, ',', '.') . "\n"
                   . "• **Margin Keuntungan:** {$marginPct}%\n\n"
                   . "Analisis mendalam per lini produk dan perbandingan margin dapat Anda lihat di menu **Laporan > Analisis Margin & Profit**.";

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'topic' => 'profitability',
            ]);
        }

        // 8. PERTANYAAN TARIF PAJAK SPESIFIK (PPh 21, PPh 23, PPN, PPh 4(2), PPh 22, PPh 25, PPh Badan)
        $isAskingTaxRate = str_contains($lowerMsg, 'tarif') || str_contains($lowerMsg, 'rate') || str_contains($lowerMsg, 'persen') || str_contains($lowerMsg, 'berapa pph') || str_contains($lowerMsg, 'berapa ppn') || (str_contains($lowerMsg, 'berapa') && str_contains($lowerMsg, 'pajak')) || str_contains($lowerMsg, '21') || str_contains($lowerMsg, '23') || str_contains($lowerMsg, 'pph final');

        if ($isAskingTaxRate && (str_contains($lowerMsg, 'pph') || str_contains($lowerMsg, 'pajak') || str_contains($lowerMsg, 'ppn') || str_contains($lowerMsg, '21') || str_contains($lowerMsg, '23') || str_contains($lowerMsg, 'final'))) {
            $has21 = str_contains($lowerMsg, '21');
            $has23 = str_contains($lowerMsg, '23');
            $hasPpn = str_contains($lowerMsg, 'ppn');
            $has42 = str_contains($lowerMsg, '4(2)') || str_contains($lowerMsg, 'final') || str_contains($lowerMsg, 'sewa');

            $sections = [];

            if ($has21 || (!$has23 && !$hasPpn && !$has42)) {
                $sections[] = "### 📋 Tarif PPh Pasal 21 (Karyawan & Tenaga Kerja)\n"
                            . "Mengacu pada **PP No. 58 Tahun 2023** dan **UU Harmonisasi Peraturan Perpajakan (UU HPP)**:\n\n"
                            . "1. **Masa Bulanan (Januari – November):**\n"
                            . "   Menggunakan mekanisme **Tarif Efektif Rata-Rata (TER)** bulanan sesuai status PTKP:\n"
                            . "   • **TER Kategori A** (TK/0: PTKP 54 jt, TK/1: 58,5 jt, K/0: 58,5 jt): Mulai **0%** (bruto s.d. Rp 5,4 jt/bln) hingga **34%**.\n"
                            . "   • **TER Kategori B** (TK/2, TK/3, K/1, K/2): Mulai **0%** (bruto s.d. Rp 6,2 jt/bln) hingga **34%**.\n"
                            . "   • **TER Kategori C** (K/3): Mulai **0%** (bruto s.d. Rp 6,6 jt/bln) hingga **34%**.\n\n"
                            . "2. **Masa Pajak Terakhir (Desember) / Perhitungan Tahunan — Lapisan Tarif Pasal 17 UU HPP:**\n"
                            . "   • Penghasilan Kena Pajak (PKP) s.d. Rp 60.000.000: **5%**\n"
                            . "   • PKP di atas Rp 60.000.000 s.d. Rp 250.000.000: **15%**\n"
                            . "   • PKP di atas Rp 250.000.000 s.d. Rp 500.000.000: **25%**\n"
                            . "   • PKP di atas Rp 500.000.000 s.d. Rp 5.000.000.000: **30%**\n"
                            . "   • PKP di atas Rp 5.000.000.000: **35%**";
            }

            if ($has23 || (!$has21 && !$hasPpn && !$has42)) {
                $sections[] = "### 📋 Tarif PPh Pasal 23 (Jasa, Sewa, Royalti, Dividen)\n"
                            . "Mengacu pada ketentuan **UU PPh** dan peraturan pelaksana perpajakan Indonesia:\n\n"
                            . "1. **Tarif 2% (dari Nilai Bruto tidak termasuk PPN):**\n"
                            . "   • **Jasa Teknik, Jasa Manajemen, Jasa Konsultan**.\n"
                            . "   • **Jasa Lainnya** (jasa katering, jasa kebersihan/cleaning service, jasa perbaikan/servis, jasa hukum, audit, dll. sesuai PMK 141/2015).\n"
                            . "   • **Sewa dan penghasilan lain** sehubungan dengan penggunaan harta (selain sewa tanah dan/atau bangunan).\n"
                            . "   > ⚠️ **Catatan Penting:** Wajib Pajak rekanan/penyedia jasa yang **tidak memiliki NPWP** dipotong tarif 100% lebih tinggi, yaitu menjadi **4%**.\n\n"
                            . "2. **Tarif 15% (dari Nilai Bruto):**\n"
                            . "   • **Dividen** (yang tidak memenuhi syarat pengecualian dividen bebas pajak UU Cipta Kerja).\n"
                            . "   • **Bunga pinjaman** termasuk premium, diskonto, dan imbalan penjaminan hutang.\n"
                            . "   • **Royalti** atas penggunaan hak cipta/paten.\n"
                            . "   • **Hadiah, penghargaan, dan bonus** selain yang telah dipotong PPh 21.";
            }

            if ($hasPpn) {
                $sections[] = "### 📋 Tarif PPN (Pajak Pertambahan Nilai)\n"
                            . "• **Tarif Standar Saat Ini:** **11%** (berlaku efektif sejak 1 April 2022 sesuai UU HPP No. 7/2021).\n"
                            . "• **Tarif PPN Lanjutan:** **12%** (ditetapkan dalam amanat UU HPP).\n"
                            . "• **Ekspor BKP Berwujud / Tidak Berwujud / JKP:** **0%**.";
            }

            if ($has42) {
                $sections[] = "### 📋 Tarif PPh Final Pasal 4 Ayat (2)\n"
                            . "• **Sewa Tanah dan/atau Bangunan:** **10%** dari jumlah bruto nilai persewaan.\n"
                            . "• **Jasa Konstruksi:** **1,75% s.d. 4%** (tergantung kualifikasi sertifikat badan usaha LPJK).";
            }

            $companyTaxCodes = TaxCode::where('company_id', $companyId)->where('is_active', true)->get();
            $tcList = [];
            foreach ($companyTaxCodes as $tc) {
                $tcList[] = "• **{$tc->code}**: {$tc->name} — **Tarif: " . floatval($tc->rate) . "%**";
            }
            if (!empty($tcList)) {
                $sections[] = "### ⚙️ Master Tarif Pajak Aktif di Sistem AKRU Entitas Anda:\n" . implode("\n", $tcList);
            }

            $reply = implode("\n\n---\n\n", $sections) . "\n\n"
                   . "> **Kesiapan Coretax DJP:** Setiap transaksi pemotongan PPh 21/23 dan faktur PPN di AKRU otomatis siap diekspor ke format XML resmi DJP pada menu **Pajak > Ekspor XML Coretax**.";

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'topic' => 'tax_rates',
            ]);
        }

        // 11. KONSULTASI PEMILIHAN AKUN COA & PENJURNALAN (Only when specifically asking for account/journal mapping)
        $isAskingAccount = preg_match('/\b(akun|coa|rekening apa|masuk apa|masuk akun|akunnya apa|posisi jurnal|jurnalnya apa)\b/i', $lowerMsg)
            || (str_contains($lowerMsg, 'jurnal') && (str_contains($lowerMsg, 'debit') || str_contains($lowerMsg, 'kredit') || str_contains($lowerMsg, 'bagaimana jurnal')))
            || str_contains($lowerMsg, 'masuk ke mana') || str_contains($lowerMsg, 'dicatat ke akun');

        if ($isAskingAccount) {
            $suggestion = $this->resolveAccountSuggestion($companyId, $message);
            if ($suggestion['account']) {
                $acc = $suggestion['account'];
                $reply = "### Rekomendasi Akun COA & Jurnal Otomatis\n\n"
                       . "Berdasarkan uraian transaksi **\"{$message}\"**, akun yang direkomendasikan adalah:\n\n"
                       . "🎯 **{$acc->code} — {$acc->name}** (Tipe: " . strtoupper($acc->type) . ")\n"
                       . "• **Tingkat Keyakinan (Confidence):** {$suggestion['confidence']}\n"
                       . "• **Alasan Akuntansi (PSAK/SAK):** {$suggestion['reasoning']}\n"
                       . "• **Saran Posisi Jurnal:** {$suggestion['journal_hint']}";

                return response()->json([
                    'success' => true,
                    'reply' => $reply,
                    'topic' => 'account_matching',
                ]);
            }
        }

        // 12. GENERAL BUSINESS & FINANCIAL CONSULTATION ADVISORY
        $reply = "### 🏛️ Konsultasi & Asistensi Bisnis Terpadu AKRU AI\n\n"
               . "Pertanyaan Anda mengenai **\"{$message}\"** telah diproses dalam korpus bisnis, akuntansi, dan keuangan Indonesia.\n\n"
               . "Sebagai asisten tata kelola dan penasihat bisnis enterprise, Anda dapat berkonsultasi secara bebas pada **5 Pilar Utama**:\n\n"
               . "1. **Akuntansi & Pembukuan:** Standar SAK/PSAK, penataan bagan akun, buku besar, penyusutan aset, dan penutupan buku.\n"
               . "2. **Manajemen Keuangan:** Posisi kas & likuiditas bank, manajemen piutang/hutang, perputaran modal kerja, dan arus kas.\n"
               . "3. **Kepatuhan & Perencanaan Pajak:** PPh 21 (TER PP 58/2023), PPh 22, PPh 23, PPh 4(2), Dividen, PPN 11%, dan integrasi Coretax DJP.\n"
               . "4. **Ekspor & Impor (Kepabeanan):** Bea Masuk, PPN Impor, PPh 22 Impor, PIB, PEB, PPN Ekspor 0%, Bea Keluar, Incoterms, dan kurs valas.\n"
               . "5. **Pengambilan Keputusan Bisnis:** Titik Impas (BEP), analisis Sewa vs Beli, strategi penetapan harga (Pricing), CAPEX vs OPEX, dan kelayakan investasi.\n\n"
               . "> **Prinsip Human-in-the-Loop (Blueprint §1.1A):** Seluruh analisis dan rekomendasi AKRU AI dirancang untuk mempercepat keputusan manajerial. Otorisasi final posting transaksi dan pelaporan pajak tetap berada di bawah kendali manajemen Anda.";

        return response()->json([
            'success' => true,
            'reply' => $reply,
            'topic' => 'general',
        ]);
    }

    /**
     * Domain Guardrail Classifier:
     * Strictly allows free consultation and analysis on 5 Pillars:
     * 1. Akuntansi, 2. Keuangan, 3. Perpajakan, 4. Ekspor & Impor, 5. Pengambilan Keputusan Bisnis.
     * Strictly limits/restricts any queries outside these domains.
     */
    protected function isOutOfDomainQuery(string $msg): bool
    {
        // 1. Explicit banned subjects (Immediate reject if no tax/financial keywords)
        $bannedTerms = [
            'sepak bola', 'liga inggris', 'pemain bola', 'ronaldo', 'messi', 'timnas', 'world cup', 'piala dunia',
            'film', 'sinetron', 'artis', 'selebritis', 'selebriti', 'aktor', 'aktris', 'bioskop', 'netflix',
            'resep', 'memasak', 'masakan', 'bumbu dapur', 'rendang', 'gorengan', 'masak ayam', 'kue',
            'ramalan bintang', 'zodiak', 'horoskop', 'shio', 'astrologi',
            'game online', 'mobile legends', 'playstation', 'gameplay', 'dota', 'pubg', 'free fire',
            'lirik lagu', 'chord gitar', 'lagu pop', 'lagu dangdut', 'kpop',
            'wisata liburan', 'pantai terindah', 'hotel termurah', 'jadwal kereta', 'tiket pesawat murah',
            'presiden', 'pemilu', 'partai politik', 'pilkada', 'kampanye', 'caleg', 'capres'
        ];

        foreach ($bannedTerms as $term) {
            if (str_contains($msg, $term)) {
                // If it asks about tax or accounting aspects of that entity, allow it; otherwise strictly block
                if (!str_contains($msg, 'pajak') && !str_contains($msg, 'laporan') && !str_contains($msg, 'jurnal') && !str_contains($msg, 'biaya') && !str_contains($msg, 'keuangan') && !str_contains($msg, 'bisnis')) {
                    return true;
                }
            }
        }

        // 2. Greetings & conversational intros (allowed)
        $greetings = ['halo', 'hai', 'hello', 'selamat', 'pagi', 'siang', 'sore', 'malam', 'assalamualaikum', 'bantuan', 'menu', 'panduan', 'siapa kamu', 'apa itu akru'];
        $tokens = preg_split('/[\s,\.\?!]+/', trim($msg));
        if (count($tokens) <= 4) {
            foreach ($greetings as $g) {
                if (str_contains($msg, $g)) {
                    return false;
                }
            }
        }

        // 3. Whitelisted 5 Pillars Keywords (free and unrestricted consultation & analysis)
        $allowedKeywords = [
            // Pilar 1: Akuntansi & Pembukuan
            'akuntansi', 'pembukuan', 'jurnal', 'debit', 'kredit', 'buku besar', 'neraca', 'laba rugi', 'arus kas',
            'penyusutan', 'depresiasi', 'amortisasi', 'sak', 'psak', 'etap', 'rekon', 'rekonsiliasi', 'audit',
            'posting', 'faktur', 'coa', 'akun', 'rekening', 'neraca saldo', 'aktiva', 'pasiva', 'ekuitas',
            'subledger', 'tutup buku', 'penyesuaian', 'akrual', 'kas masuk', 'kas keluar', 'harta', 'aset',
            // Pilar 2: Keuangan & Likuiditas
            'keuangan', 'kas', 'bank', 'saldo', 'likuiditas', 'piutang', 'hutang', 'modal', 'finansial',
            'rasio', 'current ratio', 'quick ratio', 'cash flow', 'working capital', 'solvabilitas', 'rentabilitas',
            'tagihan', 'pemasok', 'supplier', 'vendor', 'pelanggan', 'customer', 'rekening koran', 'giro', 'deposito',
            // Pilar 3: Perpajakan Indonesia
            'pajak', 'pph', 'ppn', 'ter', 'ptkp', 'djp', 'coretax', 'ebupot', 'bupot', 'efaktur', 'dividen',
            'deviden', 'fiskal', 'npwp', 'pkp', 'kredit pajak', 'tax', 'spt', 'bupot unifikasi', 'ppnbm',
            'potong pajak', 'setor', 'billing', 'ebilling', 'e-billing', 'restitusi', 'skb', 'pajak penghasilan',
            // Pilar 4: Ekspor & Impor (Kepabeanan)
            'ekspor', 'export', 'impor', 'import', 'bea cukai', 'bea masuk', 'bea keluar', 'cif', 'fob',
            'peb', 'pib', 'hs code', 'incoterm', 'incoterms', 'customs', 'valas', 'kurs', 'surat setoran pabean',
            'sspcp', 'freight', 'insurance', 'kargo', 'bill of lading', 'pelabuhan', 'pabean', 'kontainer', 'komponen',
            // Pilar 5: Pengambilan Keputusan Bisnis
            'keputusan', 'bisnis', 'usaha', 'investasi', 'sewa', 'beli', 'bep', 'break even', 'impas',
            'titik impas', 'margin', 'pricing', 'harga jual', 'capex', 'opex', 'profit', 'untung', 'rugi',
            'ekspansi', 'kelayakan', 'roi', 'payback', 'cost', 'biaya', 'markup', 'omzet', 'pendapatan',
            'rekomendasi', 'analisa', 'analisis', 'strategi', 'konsultasi', 'manajemen', 'perusahaan', 'pt', 'cv',
            'karyawan', 'gaji', 'upah', 'operasional', 'efisiensi', 'proyek', 'penjualan', 'pembelian', 'produk', 'barang',
            'kantor', 'ruko', 'gedung', 'gudang', 'mesin', 'kendaraan', 'laptop', 'komputer', 'atk', 'bensin', 'tol', 'utilitas'
        ];

        foreach ($allowedKeywords as $kw) {
            if (str_contains($msg, $kw)) {
                return false;
            }
        }

        // If not in 5 pillars and not a recognized greeting, strictly restrict
        return true;
    }

    /**
     * Resolves general accounting, finance, and tax questions across Indonesian standard standards
     */
    protected function resolveGeneralDomainKnowledge(string $msg): ?string
    {
        // 0. Sewa Dibayar di Muka (Ruko, Gedung, Kantor) & Perpajakan (PPh 4(2) & PPN)
        $isSewaQuery = (str_contains($msg, 'sewa') || str_contains($msg, 'menyewa'))
            && (str_contains($msg, 'perlakuan') || str_contains($msg, 'pajak') || str_contains($msg, 'dimuka') || str_contains($msg, 'di muka') || str_contains($msg, 'setahun') || str_contains($msg, '1 tahun') || str_contains($msg, 'psak'));

        if ($isSewaQuery) {
            return "### 🏢 Perlakuan Akuntansi & Perpajakan: Sewa Ruko / Gedung Dibayar di Muka (1 Tahun)\n\n"
                 . "Transaksi menyewa properti (ruko, kantor, atau gedung) untuk masa 1 tahun dengan pembayaran di muka seluruhnya memiliki perlakuan standar akuntansi dan perpajakan sebagai berikut:\n\n"
                 . "#### 1. Perlakuan Akuntansi (Standar SAK / PSAK)\n"
                 . "• **Saat Pembayaran (Pengakuan Awal):**\n"
                 . "  Pembayaran untuk 1 tahun ke depan **TIDAK BOLEH** langsung diakui seluruhnya sebagai Beban pada hari transaksi karena melanggar prinsip penandingan (*matching principle*) dan basis akrual (*accrual basis*).\n"
                 . "  Pengeluaran ini wajib dicatat terlebih dahulu sebagai **Aset Lancar** pada akun **[1500] Uang Muka Pembelian & Sewa Dibayar di Muka (*Prepaid Rent*)**.\n\n"
                 . "• **Amortisasi / Penyesuaian Bulanan:**\n"
                 . "  Setiap akhir bulan, lakukan jurnal penyesuaian (*adjusting entry*) sebesar **1/12** dari nilai sewa ke akun beban operasional:\n"
                 . "  - **[Debit]** `[6200] Beban Sewa Gedung & Kantor` (sebesar 1/12 nilai sewa)\n"
                 . "  - **[Kredit]** `[1500] Uang Muka Pembelian & Sewa Dibayar di Muka`\n\n"
                 . "• **Ketentuan PSAK 73 / PSAK 116 (Sewa):**\n"
                 . "  Karena masa sewa adalah **12 bulan (1 tahun)**, entitas Anda memenuhi kriteria pengecualian sewa jangka pendek (*short-term lease exemption*). Anda **tidak wajib** mencatat Aset Hak Guna (*Right-of-Use Asset*) dan Liabilitas Sewa yang kompleks, melainkan cukup menggunakan metode garis lurus (amortisasi bulanan Sewa Dibayar di Muka).\n\n"
                 . "---\n\n"
                 . "#### 2. Perlakuan Perpajakan Indonesia (UU PPh & UU PPN)\n"
                 . "• **PPh Pasal 4 Ayat (2) Final (Sewa Tanah dan/atau Bangunan):**\n"
                 . "  - **Tarif:** **10% Final** dari total nilai sewa bruto (sesuai PP No. 34 Tahun 2017).\n"
                 . "  - **Kewajiban Pemotong Pajak:** Selaku penyewa (Badan Usaha / PKP), Anda bertindak sebagai **Pemotong Pajak**. Anda wajib memotong 10% dari nilai sewa yang dibayarkan ke pemilik ruko.\n"
                 . "  - **Penyetoran & Pelaporan:** Setorkan PPh 4(2) ke kas negara menggunakan e-Billing (Kode MAP 411128 KLU 403) paling lambat **tanggal 10 bulan berikutnya**, dan terbitkan **Bukti Potong Unifikasi (PPh Final)** di Coretax / DJP Online untuk diserahkan kepada pemilik ruko.\n\n"
                 . "• **PPN (Pajak Pertambahan Nilai):**\n"
                 . "  - **Jika Pemilik Ruko adalah Pengusaha Kena Pajak (PKP):** Pemilik wajib memungut PPN **11%** (atau 12%) dan menerbitkan Faktur Pajak Elektronik (*e-Faktur*). PPN ini dicatat sebagai **Pajak Masukan ([1400])** dan dapat dikreditkan sepanjang ruko dipakai untuk kegiatan operasional usaha (3M).\n"
                 . "  - **Jika Pemilik Ruko adalah Orang Pribadi Non-PKP:** Tidak ada PPN yang dipungut.\n\n"
                 . "---\n\n"
                 . "#### 3. Simulasi Jurnal Transaksi di AKRU (Contoh: Sewa Rp 120.000.000 / Tahun)\n"
                 . "*(Asumsi pemilik ruko PKP: Sewa Bruto Rp 120 Juta, PPN 11% Rp 13,2 Juta, Potongan PPh 4(2) 10% Rp 12 Juta)*:\n\n"
                 . "1. **Saat Pembayaran di Muka (Kas Keluar):**\n"
                 . "   - `[Debit]  1500 - Uang Muka Pembelian & Sewa Dibayar di Muka : Rp 120.000.000`\n"
                 . "   - `[Debit]  1400 - PPN Masukan (Pajak Dibayar di Muka)       : Rp  13.200.000`\n"
                 . "   - `[Kredit] 2230 - Hutang PPh 4(2) Final Sewa                : Rp  12.000.000`\n"
                 . "   - `[Kredit] 1110 - Bank BCA Operasional / Kas Tunai         : Rp 121.200.000`\n\n"
                 . "2. **Setiap Akhir Bulan (Jurnal Amortisasi 1/12):**\n"
                 . "   - `[Debit]  6200 - Beban Sewa Gedung & Kantor                : Rp  10.000.000`\n"
                 . "   - `[Kredit] 1500 - Uang Muka Pembelian & Sewa Dibayar di Muka: Rp  10.000.000`\n\n"
                 . "3. **Saat Menyetor PPh 4(2) ke Kas Negara (Maksimal Tgl 10 Bulan Berikutnya):**\n"
                 . "   - `[Debit]  2230 - Hutang PPh 4(2) Final Sewa                : Rp  12.000.000`\n"
                 . "   - `[Kredit] 1110 - Bank BCA Operasional                      : Rp  12.000.000`\n\n"
                 . "> **Fitur Otomasi AKRU:** Anda dapat menjadwalkan amortisasi bulanan ini secara otomatis pada menu **Akuntansi > Penyesuaian & Amortisasi Akrual** tanpa perlu input manual setiap akhir bulan.";
        }

        // 0b. Komponen Pajak & Pungutan Impor Produk / Barang (Kepabeanan & DJP)
        if (str_contains($msg, 'impor') || str_contains($msg, 'import') || str_contains($msg, 'bea cukai') || str_contains($msg, 'bea masuk') || str_contains($msg, 'pph 22 impor') || str_contains($msg, 'ppn impor')) {
            return "### 🚢 Komponen Pajak & Pungutan Impor Barang di Indonesia (Bea Cukai & DJP)\n\n"
                 . "Ketika entitas bisnis Anda melakukan kegiatan impor produk atau barang dari luar negeri masuk ke dalam Daerah Pabean Indonesia, timbul beberapa komponen pungutan resmi yang dipungut oleh Direktorat Jenderal Bea dan Cukai (DJBC) dan Direktorat Jenderal Pajak (DJP) melalui dokumen **Pemberitahuan Impor Barang (PIB)**:\n\n"
                 . "---\n\n"
                 . "#### 1. Dasar Perhitungan Nilai Pabean (Nilai CIF)\n"
                 . "Seluruh pungutan impor dihitung dari nilai **CIF (*Cost, Insurance, Freight*)** yang dikonversi ke Rupiah menggunakan Kurs Pajak / Kurs Kepabeanan resmi Kementerian Keuangan pada tanggal pendaftaran PIB:\n"
                 . "• **Cost (C):** Harga faktur pembelian barang dari pemasok luar negeri.\n"
                 . "• **Insurance (I):** Premi asuransi pengangkutan internasional.\n"
                 . "• **Freight (F):** Ongkos angkut logistik (kapal laut / kargo udara) sampai ke pelabuhan tujuan di Indonesia.\n\n"
                 . "---\n\n"
                 . "#### 2. Empat Komponen Pajak & Pungutan yang Timbul:\n\n"
                 . "| Komponen Pungutan | Dasar Hukum | Tarif Standar | Keterangan & Perlakuan Fiskal |\n"
                 . "|---|---|:---:|---|\n"
                 . "| **1. Bea Masuk (BM)** | UU Kepabeanan No. 17/2006 | **0% – 15%+** | Tergantung pos tarif HS Code (*Harmonized System*) barang pada BTKI. Bea Masuk **dikapitalisasi menambah harga pokok perolehan persediaan (HPP/Aset)**. |\n"
                 . "| **2. PPN Impor** | UU HPP No. 7/2021 | **11%** | Dihitung dari **Nilai Impor = CIF + Bea Masuk**.<br>Bagi Pengusaha Kena Pajak (PKP), PPN Impor **DAPAT DIKREDITKAN** sebagai Pajak Masukan (PM) pada SPT Masa PPN 1111 (Form B1). |\n"
                 . "| **3. PPh Pasal 22 Impor** | PMK No. 34/2017 jo. PMK 41/2022 | **2,5%** *(dengan API/NIB)*<br>**7,5%** *(tanpa API)* | Dihitung dari **Nilai Impor = CIF + Bea Masuk**.<br>Bersifat tidak final dan **DAPAT DIKREDITKAN** terhadap PPh Badan pada SPT Tahunan Formulir 1771 Lampiran III. |\n"
                 . "| **4. PPnBM Impor** | UU PPN & PPnBM | **10% – 75%** | Hanya berlaku jika produk tergolong Barang Kena Pajak Mewah (kendaraan mewah, perhiasan, dll). Tidak dapat dikreditkan, melainkan menambah HPP barang. |\n\n"
                 . "---\n\n"
                 . "#### 3. Simulasi Perhitungan Riil (Contoh: Impor Produk Nilai CIF Rp 100.000.000, Bea Masuk 10%, Pemilik Ber-API/NIB):\n\n"
                 . "1. **Nilai CIF Pabean:** Rp 100.000.000\n"
                 . "2. **Bea Masuk (10% x CIF):** Rp 10.000.000\n"
                 . "3. **Nilai Impor (Dasar Pengenaan Pajak / DPP):** Rp 100.000.000 + Rp 10.000.000 = **Rp 110.000.000**\n"
                 . "4. **PPN Impor (11% x Nilai Impor):** 11% x Rp 110.000.000 = **Rp 12.100.000**\n"
                 . "5. **PPh Pasal 22 Impor (2,5% x Nilai Impor):** 2,5% x Rp 110.000.000 = **Rp 2.750.000**\n\n"
                 . "👉 **Total Kas yang Harus Disetor ke Kas Negara (Billing Bea Cukai):**\n"
                 . "Rp 10.000.000 (BM) + Rp 12.100.000 (PPN) + Rp 2.750.000 (PPh 22) = **Rp 24.850.000**.\n\n"
                . "---\n\n"
                 . "#### 4. Rekomendasi Jurnal Pembukuan Impor di AKRU:\n"
                 . "• **[Debit]** `[1300] Persediaan Barang Dagang / Bahan Baku`: **Rp 110.000.000** *(Harga Beli CIF Rp 100 Jt + Bea Masuk Rp 10 Jt)*\n"
                 . "• **[Debit]** `[1400] PPN Masukan Impor (Dapat Dikreditkan)`: **Rp 12.100.000**\n"
                 . "• **[Debit]** `[1410] Uang Muka PPh Pasal 22 Impor (Kredit Pajak)`: **Rp 2.750.000**\n"
                 . "• **[Kredit]** `[1110] Bank BCA Operasional / Kas Negara`: **Rp 24.850.000** *(Penyetoran Billing Bea Cukai)*\n"
                 . "• **[Kredit]** `[2100] Hutang Usaha Vendor Luar Negeri`: **Rp 100.000.000** *(Invoice Supplier)*\n\n"
                 . "> 💡 **Tips Kepatuhan Coretax & Bea Cukai:**\n"
                 . "> Pastikan dokumen **Surat Setoran Pabean, Cukai, dan Pajak (SSPCP)** serta nomor pendaftaran **PIB** disimpan dengan rapi karena nomor PIB berfungsi setara dengan Faktur Pajak Masukan resmi.";
        }

        // 0c. Perdagangan Internasional: Ekspor Barang / Jasa, PPN Ekspor 0%, PEB, Bea Keluar & Kurs Valas
        if (str_contains($msg, 'ekspor') || str_contains($msg, 'export') || str_contains($msg, 'peb') || str_contains($msg, 'bea keluar') || str_contains($msg, 'ppn ekspor')) {
            return "### 🌐 Ketentuan Pajak, Kepabeanan & Akuntansi Ekspor (Perdagangan Internasional)\n\n"
                 . "Kegiatan ekspor Barang Kena Pajak (BKP) berwujud, BKP tidak berwujud, maupun Jasa Kena Pajak (JKP) dari dalam Daerah Pabean Indonesia ke luar negeri memiliki fasilitas dan regulasi perpajakan yang diatur dalam **UU PPN (UU HPP No. 7/2021)** serta ketentuan kepabeanan DJBC:\n\n"
                 . "---\n\n"
                 . "#### 1. Perlakuan Pajak Pertambahan Nilai (PPN) Ekspor 0%\n"
                 . "• **Tarif PPN Ekspor:** Berdasarkan Pasal 7 ayat (2) UU PPN, ekspor BKP dan JKP tertentu dikenakan **Tarif PPN 0% (Nol Persen)**.\n"
                 . "• **Pajak Masukan Tetap Dapat Dikreditkan / Direstitusi:** Meskipun tarif PPN Keluaran 0%, seluruh **Pajak Masukan** yang dibayar untuk memproduksi atau memperoleh barang ekspor tersebut **TETAP DAPAT DIKREDITKAN** (Pasal 9 ayat 5 UU PPN). Akibatnya, eksportir akan mengalami posisi Lebih Bayar (LB) dan berhak mengajukan **Restitusi PPN (Pengembalian Kelebihan Pembayaran Pajak)** atau kompensasi ke masa berikutnya.\n"
                 . "• **Dokumen Pengganti Faktur Pajak:** Dokumen **Pemberitahuan Ekspor Barang (PEB)** yang telah diberikan **Nota Pelayanan Ekspor (NPE)** oleh Bea Cukai disamakan kedudukannya dengan Faktur Pajak resmi.\n\n"
                 . "---\n\n"
                 . "#### 2. Pungutan Bea Keluar (Komoditas Tertentu)\n"
                 . "• Secara umum, ekspor barang manufaktur dan olahan industri bernilai tambah **TIDAK dikenakan Bea Keluar (0%)**.\n"
                 . "• Bea Keluar hanya dikenakan pada komoditas sumber daya alam mentah/strategis untuk menjamin pasokan dalam negeri (DMO) dan hilirisasi (contoh: Kelapa Sawit/CPO, Kayu olahan tertentu, Kulit, dan Konsentrat Mineral Logam) sesuai PMK terkait.\n\n"
                 . "---\n\n"
                 . "#### 3. Dasar Nilai Ekspor & Incoterms (FOB)\n"
                 . "• Nilai ekspor yang dicantumkan dalam PEB umumnya menggunakan basis **FOB (*Free on Board*)**, yaitu harga barang saat sudah dimuat ke atas kapal di pelabuhan muat Indonesia.\n"
                 . "• Penentuan kurs: Transaksi penjualan ekspor dalam mata uang asing (USD, EUR, SGD, dll) dikonversi ke Rupiah menggunakan **Kurs Menteri Keuangan (Kurs Pajak)** pada tanggal pendaftaran PEB untuk pelaporan SPT, atau kurs tengah BI / kurs spot untuk pembukuan komersial (PSAK 10).\n\n"
                 . "---\n\n"
                 . "#### 4. Rekomendasi Jurnal Penjualan Ekspor di AKRU (Contoh: Ekspor USD 10.000, Kurs Rp 15.000):\n"
                 . "1. **Saat Penerbitan Faktur Penjualan / PEB Terdaftar (FOB Rp 150.000.000, PPN 0%):**\n"
                 . "   - `[Debit]  1200 - Piutang Usaha Luar Negeri (USD Account): Rp 150.000.000`\n"
                 . "   - `[Kredit] 4120 - Pendapatan Penjualan Ekspor            : Rp 150.000.000`\n\n"
                 . "2. **Saat Penerimaan Pembayaran Valas (Misal Kurs Naik Menjadi Rp 15.200 / USD = Rp 152.000.000):**\n"
                 . "   - `[Debit]  1120 - Bank Valas USD                        : Rp 152.000.000`\n"
                 . "   - `[Kredit] 1200 - Piutang Usaha Luar Negeri             : Rp 150.000.000`\n"
                 . "   - `[Kredit] 7200 - Keuntungan Selisih Kurs (Laba/Rugi)   : Rp   2.000.000`\n\n"
                 . "> 💡 **Tips Ekspor AKRU:** Catat faktur ekspor pada menu **Penjualan > Faktur Penjualan** dengan mencentang tarif pajak 0% dan melampirkan nomor PEB/NPE untuk rekonsiliasi data Coretax DJP.";
        }

        // 0d. Pengambilan Keputusan Bisnis: Analisis Sewa vs Beli (Lease vs Buy Evaluation)
        if ((str_contains($msg, 'sewa') && str_contains($msg, 'beli')) || str_contains($msg, 'sewa vs beli') || str_contains($msg, 'beli atau sewa') || str_contains($msg, 'lease vs buy')) {
            return "### 🎯 Analisis Pengambilan Keputusan Bisnis: Sewa vs Beli (Lease vs Buy Evaluation)\n\n"
                 . "Keputusan strategis apakah perusahaan sebaiknya **menyewa (*leasing/rent*)** atau **membeli (*outright buy / asset purchase*)** aset operasional (ruko, kantor, mesin, armada transportasi, atau peralatan) dianalisis dari 4 dimensi keuangan utama:\n\n"
                 . "---\n\n"
                 . "#### 1. Matriks Perbandingan Parameter Keuangan\n\n"
                 . "| Indikator Evaluasi | Opsi Menyewa (Lease) | Opsi Membeli (Buy / Aset Tetap) |\n"
                 . "|---|---|---|\n"
                 . "| **Kebutuhan Modal Awal (*Initial Outlay*)** | **Sangat Rendah** (Hanya uang jaminan / deposit sewa) | **Sangat Tinggi** (Harga beli penuh tunai atau DP 20-30% jika kredit) |\n"
                 . "| **Likuiditas & Modal Kerja** | **Aman & Fleksibel**; dana kas cadangan dapat dialokasikan untuk perputaran stok/pemasaran | **Terkunci** pada aset tidak lancar (mengurangi fleksibilitas kas darurat) |\n"
                 . "| **Dampak Laba Rugi & Beban** | Seluruh biaya sewa diakui sebagai **Beban Operasional bulanan** (*Deductible Expense*) | Diakui melalui **Beban Penyusutan Aset** berkala + Beban Bunga (bila pinjaman) |\n"
                 . "| **Pajak Penghasilan (PPh)** | PPh 4(2) 10% (ruko) / PPh 23 2% (alat). Mengurangi laba kena pajak secara cepat | Mengurangi laba kena pajak secara bertahap selama masa manfaat penyusutan (4-20 thn) |\n"
                 . "| **Hak Milik & Nilai Sisa (*Capital Gain*)** | Tidak memiliki hak milik; tidak ada apresiasi aset di neraca | Hak milik penuh; potensi apresiasi harga properti / nilai sisa penjualan kembali |\n"
                 . "| **Risiko Keusangan (*Obsolescence*)** | **Nol / Rendah**; kontrak selesai dapat upgrade ke tipe terbaru | **Tinggi**; risiko penurunan nilai teknologi atau biaya perbaikan besar |\n\n"
                 . "---\n\n"
                 . "#### 2. Kapan Bisnis Anda Harus Memilih MENYEWA?\n"
                 . "1. **Fase Pertumbuhan Cepat / Rintisan (*Growth / Startup Phase*):** Modal kas masih sangat dibutuhkan untuk mempercepat perputaran piutang dan persediaan.\n"
                 . "2. **Aset Berteknologi Cepat Usang:** Komputer server, mesin printing digital, perangkat IT, atau kendaraan operasional proyek temporer.\n"
                 . "3. **Lokasi Usaha Belum Permanen:** Ingin menguji potensi pasar suatu cabang ruko sebelum mengikat investasi modal jangka panjang.\n\n"
                 . "---\n\n"
                 . "#### 3. Kapan Bisnis Anda Harus Memilih MEMBELI?\n"
                 . "1. **Aset Inti Jangka Panjang (*Core Strategic Asset*):** Gudang pabrik utama, ruko kantor pusat yang dipakai lebih dari 8–15 tahun.\n"
                 . "2. **Likuiditas Kas Melimpah (*Idle Cash*):** Dana kas mengendap yang menghasilkan imbal hasil lebih baik jika dialihkan ke properti komersial daripada tabungan bank.\n"
                 . "3. **Aset Mengalami Kenaikan Nilai:** Properti komersial di lokasi strategis yang memberikan *capital gain* signifikan di neraca ekuitas.\n\n"
                 . "---\n\n"
                 . "#### 4. Rekomendasi Finansial AKRU AI:\n"
                 . "Hitung nilai kini bersih (*Net Present Value / NPV*) dari total arus kas keluar (setelah pajak) antara opsi sewa vs cicilan pembelian. Jika efisiensi modal kas Anda menghasilkan ROI bisnis > 20% per tahun, memutar uang kas pada operasional dan **menyewa properti** umumnya lebih menguntungkan bagi arus kas bisnis.";
        }

        // 0e. Pengambilan Keputusan Bisnis: Analisis Titik Impas (Break-Even Point / BEP)
        if (str_contains($msg, 'bep') || str_contains($msg, 'break even') || str_contains($msg, 'titik impas') || str_contains($msg, 'balik modal') || (str_contains($msg, 'analisis') && str_contains($msg, 'biaya volume laba'))) {
            return "### ⚖️ Analisis Pengambilan Keputusan Bisnis: Titik Impas (Break-Even Point / BEP)\n\n"
                 . "Analisis Titik Impas (*Break-Even Point*) adalah metode strategis untuk menentukan volume penjualan minimum di mana total pendapatan perusahaan sama persis dengan total biaya (Laba Bersih = Rp 0). Melewati titik ini, setiap unit tambahan langsung menghasilkan laba bersih bagi bisnis Anda.\n\n"
                 . "---\n\n"
                 . "#### 1. Klasifikasi Biaya Dasar Perhitungan BEP\n"
                 . "1. **Biaya Tetap (*Fixed Cost / FC*):** Beban yang nilainya konstan tidak terpengaruh oleh volume produksi/penjualan (misal: gaji pokok manajemen, sewa kantor, penyusutan aset tetap, langganan software).\n"
                 . "2. **Biaya Variabel (*Variable Cost / VC*):** Biaya yang bertambah secara proporsional sesuai jumlah produk yang dibuat/dijual (misal: bahan baku, kemasan, komisi penjualan, ongkos kirim unit).\n"
                 . "3. **Harga Jual per Unit (*Price / P*):** Nilai jual satuan kepada pelanggan (sebelum PPN).\n\n"
                 . "---\n\n"
                 . "#### 2. Formula Standar BEP Manajerial\n\n"
                 . "• **BEP dalam Satuan Unit Produk:**\n"
                 . "  $$\\text{BEP (Unit)} = \\frac{\\text{Biaya Tetap (FC)}}{\\text{Harga Jual (P)} - \\text{Biaya Variabel per Unit (VC)}}$$\n"
                 . "  *(Selisih P - VC disebut sebagai **Margin Kontribusi per Unit**)*.\n\n"
                 . "• **BEP dalam Nilai Rupiah / Omzet:**\n"
                 . "  $$\\text{BEP (Rupiah)} = \\frac{\\text{Biaya Tetap (FC)}}{1 - \\left(\\frac{\\text{Biaya Variabel (VC)}}{\\text{Harga Jual (P)}}\\right)}$$\n\n"
                 . "---\n\n"
                 . "#### 3. Simulasi Riil Kasus Bisnis\n"
                 . "Misalkan perusahaan Anda memiliki struktur biaya bulanan:\n"
                 . "• Total Biaya Tetap (Gaji + Sewa + Utilitas): **Rp 60.000.000** / bulan\n"
                 . "• Harga Jual Produk per Unit: **Rp 150.000**\n"
                 . "• Biaya Variabel (HPP Bahan + Kemasan): **Rp 90.000** per unit\n\n"
                 . "👉 **Perhitungan:**\n"
                 . "1. Margin Kontribusi per Unit = Rp 150.000 - Rp 90.000 = **Rp 60.000** per unit (Rasio Margin: 40%).\n"
                 . "2. **BEP Unit:** Rp 60.000.000 / Rp 60.000 = **1.000 Unit**.\n"
                 . "3. **BEP Rupiah:** 1.000 Unit x Rp 150.000 = **Rp 150.000.000** omzet.\n\n"
                 . "🎯 **Kesimpulan Strategis:** Perusahaan wajib menjual minimal 1.000 unit per bulan (omzet Rp 150 juta) untuk menutup seluruh pengeluaran. Penjualan unit ke-1.001 ke atas menyumbang Rp 60.000 laba murni ke laba sebelum pajak (*EBIT*).\n\n"
                 . "---\n\n"
                 . "#### 4. Langkah Menurunkan Risiko BEP (Meningkatkan Margin Usaha):\n"
                 . "• **Negosiasi Bahan Baku:** Menurunkan biaya variabel per unit otomatis meningkatkan margin kontribusi.\n"
                 . "• **Efisiensi Beban Tetap (Overhead):** Mengurangi pemborosan utilitas atau otomatisasi pembukuan di AKRU untuk menekan Fixed Cost.\n"
                 . "• **Penyesuaian Harga (*Value-Based Pricing*):** Menaikkan harga jual secara terukur jika diferensiasi produk kuat.";
        }

        // 0f. Pengambilan Keputusan Bisnis: Penetapan Harga Jual (Pricing Strategy) & Target Margin
        if (str_contains($msg, 'pricing') || str_contains($msg, 'harga jual') || str_contains($msg, 'menentukan harga') || str_contains($msg, 'markup') || str_contains($msg, 'target margin')) {
            return "### 🏷️ Analisis Pengambilan Keputusan Bisnis: Strategi Penetapan Harga Jual (Pricing Strategy)\n\n"
                 . "Penetapan harga jual produk/jasa yang tepat menjamin tercapainya target keuntungan tanpa mengorbankan daya saing di pasar. Terdapat dua metodologi utama yang digunakan dalam akuntansi manajemen:\n\n"
                 . "---\n\n"
                 . "#### 1. Perbedaan Mendasar: Markup vs Target Margin\n\n"
                 . "Banyak pebisnis keliru menyamakan persentase *Markup* dengan *Margin Keuntungan*:\n"
                 . "• **Markup (%):** Tambahan keuntungan yang dihitung di atas dasar HPP.\n"
                 . "  $$\\text{Harga Jual} = \\text{HPP} \\times (1 + \\text{Markup %})$$\n"
                 . "• **Gross Profit Margin (%):** Porsi laba kotor terhadap nilai total harga jual.\n"
                 . "  $$\\text{Harga Jual} = \\frac{\\text{HPP}}{1 - \\text{Target Margin %}}$$\n\n"
                 . "*Contoh:* Jika HPP produk Anda **Rp 100.000** dan Anda menginginkan **Margin 30%**:\n"
                 . "• Jika pakai rumus Markup 30%: Harga = Rp 130.000 (Margin riil hanya 23%!).\n"
                 . "• Jika pakai rumus Target Margin 30%: Harga = Rp 100.000 / (1 - 0.30) = **Rp 142.857** (Margin riil tepat 30%).\n\n"
                 . "---\n\n"
                 . "#### 2. Simulasi Komprehensif Melibatkan PPN 11%\n"
                 . "Bagi Pengusaha Kena Pajak (PKP), jangan menggabungkan PPN ke dalam target margin:\n"
                 . "1. **HPP Bersih Produk:** Rp 200.000\n"
                 . "2. **Target Margin Keuntungan Bersih (40%):** Rp 200.000 / (1 - 0.40) = **Rp 333.333** (Harga Dasar Jual / DPP)\n"
                 . "3. **PPN Keluaran (11% x DPP):** 11% x Rp 333.333 = **Rp 36.667**\n"
                 . "4. **Harga Tagihan ke Konsumen (Gross Price):** **Rp 370.000**\n\n"
                 . "👉 Dengan metode ini, kas masuk dari PPN (Rp 36.667) disetor utuh ke kas negara tanpa memotong margin keuntungan 40% perusahaan Anda.\n\n"
                 . "---\n\n"
                 . "#### 3. Rekomendasi Strategi Pricing Berdasarkan Posisi Produk:\n"
                 . "• **Cost-Plus Pricing:** Cocok untuk barang komoditas dan pesanan manufaktur berbasis kontrak kerja.\n"
                 . "• **Value-Based Pricing:** Menetapkan harga berdasarkan nilai solusi yang dirasakan konsumen (biasanya menghasilkan margin kotor > 60%).\n"
                 . "• **Tiered Pricing (Paket Bertingkat):** Menawarkan paket Basic, Pro, dan Enterprise untuk menangkap segmen pelanggan yang sensitif harga hingga enterprise.";
        }

        // 0g. Pengambilan Keputusan Bisnis: Analisis CAPEX vs OPEX & Kelayakan Investasi
        if (str_contains($msg, 'capex') || str_contains($msg, 'opex') || str_contains($msg, 'kelayakan usaha') || str_contains($msg, 'kelayakan investasi') || str_contains($msg, 'payback period') || str_contains($msg, 'belanja modal')) {
            return "### 📊 Analisis Pengambilan Keputusan Bisnis: CAPEX vs OPEX & Kelayakan Investasi\n\n"
                 . "Dalam tata kelola keuangan korporasi, alokasi anggaran belanja modal (*Capital Expenditure / CAPEX*) dan beban operasional (*Operational Expenditure / OPEX*) menentukan struktur neraca, likuiditas kas, serta efisiensi pajak perusahaan:\n\n"
                 . "---\n\n"
                 . "#### 1. Perbedaan Mendasar CAPEX vs OPEX\n\n"
                 . "| Karakteristik | Belanja Modal (CAPEX) | Beban Operasional (OPEX) |\n"
                 . "|---|---|---|\n"
                 . "| **Definisi** | Pengeluaran untuk memperoleh/meningkatkan aset tetap jangka panjang (> 1 tahun) | Biaya sehari-hari yang habis terpakai dalam periode berjalan untuk menjalankan operasional |\n"
                 . "| **Contoh Transaksi** | Pembelian mesin pabrik, ruko, pembangunan gedung, server data, armada truk | Gaji bulanan, sewa kantor, tagihan listrik/air/internet, biaya iklan, ATK |\n"
                 . "| **Pencatatan Akuntansi** | Dicatat di **Neraca (Aset Tidak Lancar)**, tidak langsung memotong laba | Dicatat langsung di **Laporan Laba Rugi (Beban Operasional)** periode berjalan |\n"
                 . "| **Dampak Pajak** | Dikurangkan secara bertahap via **Beban Penyusutan Aset** (Pasal 11 UU PPh) | Langsung mengurangi Penghasilan Kena Pajak pada tahun pajak berjalan (*100% Tax Deductible*) |\n"
                 . "| **Dampak Arus Kas** | Pengeluaran kas besar sekaligus di muka (*heavy initial cash drain*) | Arus kas keluar tersebar berkala dan terprediksi sesuai kebutuhan operasional |\n\n"
                 . "---\n\n"
                 . "#### 2. Tiga Indikator Utama Evaluasi Kelayakan Investasi Bisnis\n"
                 . "1. **Payback Period (Periode Pengembalian Modal):**\n"
                 . "   Waktu yang diperlukan agar arus kas masuk bersih mampu menutup modal awal investasi.\n"
                 . "   *Kriteria Sehat:* Proyek investasi idealnya memiliki payback period di bawah 3–5 tahun.\n\n"
                 . "2. **Return on Investment (ROI):**\n"
                 . "   $$\\text{ROI} = \\frac{\\text{Total Keuntungan Bersih Investasi}}{\\text{Total Biaya Investasi Awal}} \\times 100\\%$$\n"
                 . "   *Batas Minimal:* ROI proyek bisnis harus lebih tinggi dari biaya modal (*Cost of Capital / WACC*) atau bunga pinjaman bank (misal > 15%).\n\n"
                 . "3. **Net Present Value (NPV):**\n"
                 . "   Menghitung nilai sekarang dari seluruh proyeksi arus kas masa depan yang didiskontokan dengan tingkat suku bunga diskonto.\n"
                 . "   *Kaidah Keputusan:* Jika **NPV > 0**, maka proyek investasi layak secara ekonomis untuk disetujui (*GO*).\n\n"
                 . "---\n\n"
                 . "#### 3. Rekomendasi Manajemen Keuangan:\n"
                 . "Pada masa ketidakpastian pasar atau krisis likuiditas, terapkan strategi *Shift CAPEX to OPEX* (misalnya menggunakan layanan cloud daripada membeli server fisik, atau menyewa kendaraan operasional daripada membeli tunai) untuk menjaga ketersediaan dana kas cadangan (*Cash Runway*).";
        }

        // 1. Rekonsiliasi Fiskal & Koreksi Positif / Negatif
        if (str_contains($msg, 'rekonsiliasi fiskal') || str_contains($msg, 'koreksi fiskal') || str_contains($msg, 'koreksi positif') || str_contains($msg, 'koreksi negatif')) {
            return "### 📑 Panduan Rekonsiliasi Fiskal (Komersial vs Fiskal)\n\n"
                 . "Rekonsiliasi fiskal menjembatani laba komersial (standar SAK) menjadi Penghasilan Neto Fiskal untuk pengisian SPT Tahunan PPh Badan (Formulir 1771-I):\n\n"
                 . "1. **Koreksi Fiskal Positif (Menambah Laba Fiskal):**\n"
                 . "   • Beban yang tidak dapat dikurangkan (*Non-Deductible Expenses* sesuai Pasal 9 UU PPh).\n"
                 . "   • Biaya natura dan kenikmatan tertentu yang melampaui batasan PMK No. 66/2023.\n"
                 . "   • Sanksi administrasi berupa denda, bunga, atau kenaikan pajak.\n"
                 . "   • Biaya jamuan/representasi yang tidak dilengkapi **Daftar Nominatif** resmi.\n"
                 . "   • Pengeluaran untuk keperluan pribadi pemegang saham atau keluarganya.\n"
                 . "   • Selisih penyusutan komersial lebih tinggi dari penyusutan fiskal.\n\n"
                 . "2. **Koreksi Fiskal Negatif (Mengurangi Laba Fiskal):**\n"
                 . "   • Penghasilan yang telah dikenakan **PPh Final Pasal 4(2)** (misal: bunga deposito, sewa tanah/bangunan).\n"
                 . "   • Penghasilan bukan objek pajak (misal: dividen dari dalam negeri bagi badan usaha sesuai UU Cipta Kerja).\n"
                 . "   • Selisih penyusutan fiskal lebih tinggi dari penyusutan komersial.\n\n"
                 . "Anda dapat mencatat dan meninjau penyesuaian ini pada menu **Pajak > Rekonsiliasi Fiskal**.";
        }

        // 2. Penyusutan Aset Tetap (Penyusutan Komersial vs Fiskal)
        if (str_contains($msg, 'penyusutan') || str_contains($msg, 'depresiasi') || str_contains($msg, 'kelompok harta') || str_contains($msg, 'masa manfaat')) {
            return "### 🏢 Ketentuan Penyusutan Aset Tetap (Komersial & Fiskal)\n\n"
                 . "Berdasarkan **Pasal 11 UU PPh**, harta berwujud disusutkan menggunakan metode **Garis Lurus (*Straight-Line*)** atau **Saldo Menurun Ganda (*Double Declining*)** sesuai kelompok masa manfaat:\n\n"
                 . "| Kelompok Harta | Masa Manfaat | Garis Lurus | Saldo Menurun | Contoh Aset |\n"
                 . "|---|---|---|---|---|\n"
                 . "| **Kelompok 1** | 4 Tahun | **25%** | 50% | Komputer, laptop, printer, ponsel, furnitur kayu ringan |\n"
                 . "| **Kelompok 2** | 8 Tahun | **12,5%** | 25% | Mobil dinas, motor, truk, AC, genset, furnitur logam |\n"
                 . "| **Kelompok 3** | 16 Tahun | **6,25%** | 12,5% | Mesin pabrik, peralatan tambang, tangki industri |\n"
                 . "| **Kelompok 4** | 20 Tahun | **5%** | 10% | Alat berat konstruksi, infrastruktur khusus |\n"
                 . "| **Bangunan Permanen** | 20 Tahun | **5%** | - | Gedung kantor, gudang permanen, ruko |\n\n"
                 . "Sistem AKRU secara otomatis menghitung dan memposting beban penyusutan bulanan pada menu **Akuntansi > Register Aset Tetap**.";
        }

        // 3. PPh Final UMKM 0,5% (PP 55/2022)
        if (str_contains($msg, 'umkm') || str_contains($msg, 'pp 55') || str_contains($msg, 'setengah persen') || str_contains($msg, '0,5%') || str_contains($msg, '0.5%')) {
            return "### 🏪 Ketentuan PPh Final UMKM 0,5% (PP No. 55 Tahun 2022)\n\n"
                 . "Fasilitas pajak penghasilan final bagi pelaku usaha dengan peredaran bruto tertentu:\n\n"
                 . "1. **Tarif:** **0,5%** dari omzet peredaran bruto bulanan.\n"
                 . "2. **Batasan Omzet:** Maksimal peredaran bruto **Rp 4,8 Miliar** dalam 1 tahun pajak.\n"
                 . "3. **Fasilitas Bebas Pajak WP Orang Pribadi:** Bagian peredaran bruto s.d. **Rp 500.000.000** dalam setahun **TIDAK dikenai PPh** (mulai bayar 0,5% ketika omzet kumulatif melewati Rp 500 juta).\n"
                 . "4. **Jangka Waktu Maksimal Penggunaan:**\n"
                 . "   • WP Orang Pribadi: Maksimal **7 Tahun Pajak**.\n"
                 . "   • Koperasi, CV, atau Firma: Maksimal **4 Tahun Pajak**.\n"
                 . "   • Perseroan Terbatas (PT): Maksimal **3 Tahun Pajak**.\n"
                 . "   *Setelah batas waktu habis, wajib menggunakan tarif umum PPh Badan/Orang Pribadi berbasis pembukuan riil.*";
        }

        // 4. Standar Akuntansi Sewa (PSAK 73 / PSAK 116)
        if (str_contains($msg, 'psak 73') || str_contains($msg, 'hak guna') || str_contains($msg, 'liabilitas sewa')) {
            return "### 📑 Perlakuan Akuntansi Sewa (PSAK 73 / PSAK 116)\n\n"
                 . "Dalam standar akuntansi keuangan modern, sebagian besar transaksi sewa jangka panjang wajib diakui di Neraca (*On-Balance Sheet*):\n\n"
                 . "1. **Pengakuan Awal (Lessee):**\n"
                 . "   • Mendebit **Aset Hak Guna (*Right-of-Use Asset*)**.\n"
                 . "   • Mengkredit **Liabilitas Sewa (*Lease Liability*)** sebesar nilai kini pembayaran sewa di masa depan.\n"
                 . "2. **Pengukuran Berkala:**\n"
                 . "   • Aset Hak Guna disusutkan selama masa sewa (Beban Penyusutan di Laba Rugi).\n"
                 . "   • Liabilitas Sewa diamortisasi dengan pembebanan bunga sewa berkala (*Finance Cost*).\n"
                 . "3. **Pengecualian (*Exemption*):**\n"
                 . "   • Sewa jangka pendek (< 12 bulan) dan sewa aset bernilai rendah (*low value*) dapat langsung diakui sebagai Beban Sewa operasional garis lurus.";
        }

        // 5. Analisis Rasio Likuiditas & Modal Kerja
        if (str_contains($msg, 'rasio') || str_contains($msg, 'likuiditas') || str_contains($msg, 'current ratio') || str_contains($msg, 'quick ratio') || str_contains($msg, 'modal kerja')) {
            return "### 📊 Analisis Rasio Keuangan & Modal Kerja Bisnis\n\n"
                 . "Indikator utama kesehatan finansial jangka pendek entitas:\n\n"
                 . "1. **Rasio Lancar (*Current Ratio*):**\n"
                 . "   $$\\text{Current Ratio} = \\frac{\\text{Aset Lancar}}{\\text{Kewajiban Lancar}}$$\n"
                 . "   • **Nilai Ideal:** 1,5x s.d. 2,0x. Menunjukkan kemampuan membayar kewajiban jangka pendek dengan kas, piutang, dan stok lancar.\n\n"
                 . "2. **Rasio Cepat (*Quick Ratio / Acid-Test*):**\n"
                 . "   $$\\text{Quick Ratio} = \\frac{\\text{Kas + Setara Kas + Piutang Usaha}}{\\text{Kewajiban Lancar}}$$\n"
                 . "   • Mengeluarkan persediaan barang dagang dari perhitungan karena butuh waktu untuk dicairkan.\n"
                 . "   • **Nilai Sehat:** Minimal 1,0x.\n\n"
                 . "3. **Modal Kerja Bersih (*Net Working Capital*):**\n"
                 . "   $$\\text{Modal Kerja} = \\text{Aset Lancar} - \\text{Kewajiban Lancar}$$\n"
                 . "   • Nilai positif menjamin kelancaran operasional sehari-hari tanpa risiko gagal bayar.";
        }

        // 6. Faktur Pajak & Kredit PPN Masukan
        if (str_contains($msg, 'kredit ppn') || str_contains($msg, 'pajak masukan') || str_contains($msg, 'faktur pajak tidak dapat')) {
            return "### 🧾 Pengkreditan Pajak Masukan PPN (Pasal 9 UU PPN)\n\n"
                 . "Ketentuan pengkreditan Pajak Masukan (PM) terhadap Pajak Keluaran (PK):\n\n"
                 . "1. **Dapat Dikreditkan:**\n"
                 . "   • Pengeluaran BKP/JKP yang berhubungan langsung dengan kegiatan usaha 3M (Mendapatkan, Menagih, Memelihara penghasilan).\n"
                 . "   • Didukung Faktur Pajak Elektronik (*e-Faktur*) yang valid dan lengkap identitas pembeli.\n\n"
                 . "2. **Pajak Masukan yang TIDAK Dapat Dikreditkan (Pasal 9 Ayat 8):**\n"
                 . "   • Perolehan BKP/JKP sebelum Pengusaha dikukuhkan sebagai Pengusaha Kena Pajak (PKP).\n"
                 . "   • Pengeluaran yang tidak mempunyai hubungan langsung dengan kegiatan usaha.\n"
                 . "   • Perolehan dan pemeliharaan kendaraan sedan dan station wagon (kecuali barang dagangan atau disewakan).\n"
                 . "   • Faktur pajak cacat, tidak lengkap, atau tidak mencantumkan keterangan yang sebenarnya.";
        }

        return null;
    }

    /**
     * Builds structured enterprise context for Deep RAG Grounding
     */
    protected function buildEnterpriseContext($companyId): string
    {
        $bankAccounts = BankAccount::where('company_id', $companyId)->where('is_active', true)->get();
        $totalBank = $bankAccounts->sum('current_balance');
        $bankList = [];
        foreach ($bankAccounts as $ba) {
            $bankList[] = "  • {$ba->bank_name} ({$ba->account_number}): Rp " . number_format($ba->current_balance, 0, ',', '.');
        }
        $bankStr = !empty($bankList) ? implode("\n", $bankList) : "  • Belum ada rekening bank";

        $currentMonth = date('Y-m');
        $invoices = SalesInvoice::where('company_id', $companyId)
            ->where('invoice_date', 'like', "{$currentMonth}%")
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->get();
        $totalOmzet = $invoices->sum('total_amount');
        $totalPpn = $invoices->sum('tax_amount');
        $fakturCount = $invoices->count();

        $unpaidInvoices = SalesInvoice::where('company_id', $companyId)->whereIn('status', ['posted', 'partially_paid'])->get();
        $totalPiutang = $unpaidInvoices->sum(fn($i) => $i->total_amount - ($i->paid_amount ?? 0));
        $overdueCount = $unpaidInvoices->filter(fn($i) => $i->due_date && $i->due_date < date('Y-m-d'))->count();

        $unpaidBills = PurchaseInvoice::where('company_id', $companyId)->whereIn('status', ['posted', 'partially_paid'])->get();
        $totalHutang = $unpaidBills->sum(fn($i) => $i->total_amount - ($i->paid_amount ?? 0));

        $accounts = Account::where('company_id', $companyId)->where('is_active', true)->take(30)->get(['code', 'name', 'type']);
        $coaList = [];
        foreach ($accounts as $a) {
            $coaList[] = "  • [{$a->code}] {$a->name} ({$a->type})";
        }
        $coaStr = implode("\n", $coaList);

        $taxCodes = TaxCode::where('company_id', $companyId)->where('is_active', true)->get();
        $taxList = [];
        foreach ($taxCodes as $tc) {
            $taxList[] = "  • {$tc->code} ({$tc->name}): {$tc->rate}%";
        }
        $taxStr = !empty($taxList) ? implode("\n", $taxList) : "  • PPN 11%, PPh 21, PPh 23, PPh 4(2)";

        return "KONTEKS BUKU BESAR RIIL PERUSAHAAN (PER " . date('d F Y') . "):\n"
             . "1. Saldo Likuiditas Kas & Bank: Total Rp " . number_format($totalBank, 0, ',', '.') . "\n{$bankStr}\n"
             . "2. Omzet Penjualan Bulan Ini: Rp " . number_format($totalOmzet, 0, ',', '.') . " ({$fakturCount} faktur, PPN Keluaran: Rp " . number_format($totalPpn, 0, ',', '.') . ")\n"
             . "3. Piutang Usaha Belum Lunas: Rp " . number_format($totalPiutang, 0, ',', '.') . " ({$unpaidInvoices->count()} faktur aktif, {$overdueCount} faktur jatuh tempo)\n"
             . "4. Hutang Usaha Vendor: Rp " . number_format($totalHutang, 0, ',', '.') . " ({$unpaidBills->count()} tagihan pemasok)\n"
             . "5. Daftar Bagan Akun (COA) Resmi di Sistem:\n{$coaStr}\n"
             . "6. Master Tarif Pajak di Sistem:\n{$taxStr}\n";
    }

    /**
     * Standard Enterprise System Instruction for Gemini & OpenAI LLMs
     * Mandates free consultation across 5 Pillars and strict guardrails outside.
     */
    protected function getEnterpriseSystemPrompt(string $enterpriseContext): string
    {
        return "Anda adalah AKRU AI, asisten kecerdasan buatan terpadu Enterprise Resource Planning (ERP) AKRU, berperan sebagai Senior Chartered Accountant, Penasihat Keuangan Korporasi, Ahli Kepabeanan/Perdagangan Internasional, dan Konsultan Pajak Berlisensi Resmi Indonesia.\n\n"
             . "LINGKUP BEBAS KONSULTASI, ANALISIS & PERTANYAAN (5 PILAR UTAMA):\n"
             . "Pengguna memiliki hak dan kebebasan penuh untuk berkonsultasi, menganalisis, mensimulasikan skenario, menghitung angka riil, dan bertanya apapun pada 5 pilar berikut:\n"
             . "1. AKUNTANSI & PEMBUKUAN: Standar SAK/PSAK/ETAP, bagan akun (COA), rekomendasi jurnal Debit/Kredit, buku besar, penyusutan aset tetap, amortisasi sewa/hak guna, jurnal penyesuaian/penutup, neraca saldo, laporan laba rugi, neraca, dan jejak audit (audit trail).\n"
             . "2. MANAJEMEN KEUANGAN & LIKUIDITAS: Saldo kas & rekening bank, arus kas (cash flow forecasting), piutang usaha (aging & penagihan), hutang pemasok, perputaran modal kerja (working capital), rasio likuiditas/solvabilitas/profitabilitas, mitigasi risiko keuangan, dan strategi alokasi dana.\n"
             . "3. KEPATUHAN & PERENCANAAN PERPAJAKAN INDONESIA: UU HPP, UU Cipta Kerja, peraturan Menteri Keuangan (PMK), dan integrasi Coretax DJP. Meliputi PPh 21 (skema TER PP 58/2023 & Pasal 17), PPh 22, PPh 23 (jasa/sewa/royalti 2% ber-NPWP / 4% non-NPWP), PPh 4(2) Final sewa tanah/bangunan 10%, PPh 25/29 Badan, PPh Final UMKM 0,5% (PP 55/2022), Pajak Dividen (bebas PPh 0% jika diinvestasikan 3 tahun di NKRI bagi OP, 10% final jika konsumtif, 0% bagi Badan), PPN 11%/12% (Faktur Masukan/Keluaran, pengkreditan B2B), kompensasi rugi fiskal, dan rekonsiliasi fiskal positif/negatif.\n"
             . "4. PERDAGANGAN INTERNASIONAL (EKSPOR & IMPOR / KEPABEANAN): Seluruh aturan bea cukai dan perpajakan internasional. Meliputi Bea Masuk (0-15%+), PPN Impor 11%, PPh 22 Impor (2,5% ber-API / 7,5% non-API), nilai CIF (Cost, Insurance, Freight), dokumen PIB & SSPCP. Untuk Ekspor: PPN Ekspor 0%, Bea Keluar komoditas tertentu, dokumen PEB (Pemberitahuan Ekspor Barang), Bill of Lading, Invoice, Packing List, Incoterms (FOB, CIF, EXW, DDP), serta perlakuan kurs valuta asing & selisih kurs (PSAK 10) beserta jurnal akuntansinya.\n"
             . "5. PENGAMBILAN KEPUTUSAN BISNIS: Analisis Titik Impas (Break-Even Point / BEP dalam unit & nominal rupiah), evaluasi komparatif Sewa vs Beli (Lease vs Buy), penetapan strategi harga jual (Pricing Strategy, Markup, Margin Kontribusi), analisis biaya relevan (Cost-Volume-Profit), belanja modal vs operasional (CAPEX vs OPEX), kelayakan investasi usaha (Payback Period, ROI, NPV), optimasi struktur biaya, dan strategi ekspansi bisnis.\n\n"
             . "BATASAN KETAT (GUARDRAILS):\n"
             . "Selain 5 pilar di atas (seperti: topik olahraga/sepak bola, film/sinetron/hiburan selebritas, resep masakan/kuliner non-bisnis, video game, ramalan zodiak, musik/lirik lagu pop, asmara pribadi, atau gosip politik praktis yang tidak terkait fiskal), Anda DILARANG KERAS menjawab dan WAJIB MENOLAKNYA SECARA SOPAN. Berikan penolakan ramah dan ingatkan kembali bahwa fokus keahlian Anda adalah 5 pilar bisnis AKRU AI.\n\n"
             . "KONTEKS BUKU BESAR RIIL PERUSAHAAN SAAT INI:\n" . $enterpriseContext . "\n\n"
             . "GAYA PENULISAN: Gunakan Bahasa Indonesia profesional, ringkas, berbobot, terstruktur dengan Markdown (tabel perbandingan, poin tebal, formula perhitungan), dan sertakan rekomendasi jurnal Debit/Kredit jika relevan.";
    }

    /**
     * Upgraded Google Gemini API Integration with Multi-Turn Memory & RAG Grounding
     */
    protected function callGeminiApi($companyId, string $userMessage, array $history = []): ?string
    {
        try {
            $apiKey = PlatformSetting::get('ai_gemini_api_key') ?: env('GEMINI_API_KEY');
            $model = PlatformSetting::get('ai_default_model') ?: env('GEMINI_MODEL', 'gemini-flash-latest');
            if (in_array($model, ['gemini-1.5-flash', 'gemini-2.5-flash'])) {
                $model = 'gemini-flash-latest';
            }
            $temperature = floatval(PlatformSetting::get('ai_temperature') ?: 0.2);

            if (empty($apiKey)) {
                return null;
            }

            $enterpriseContext = $this->buildEnterpriseContext($companyId);
            $systemInstruction = $this->getEnterpriseSystemPrompt($enterpriseContext);

            $contents = [];
            // Append multi-turn history (last 6 messages)
            foreach (array_slice($history, -6) as $item) {
                if (!empty($item['text'])) {
                    $role = ($item['sender'] ?? '') === 'user' ? 'user' : 'model';
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => $item['text']]]
                    ];
                }
            }
            // Append current user message
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]]
            ];

            $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => 1500,
                ]
            ]);

            if (!$response->successful()) {
                $fallbackModel = ($model === 'gemini-flash-lite-latest') ? 'gemini-flash-latest' : 'gemini-flash-lite-latest';
                $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/{$fallbackModel}:generateContent?key={$apiKey}", [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => 1500,
                    ]
                ]);
            }

            if ($response->successful()) {
                $candidates = $response->json('candidates');
                if (!empty($candidates[0]['content']['parts'][0]['text'])) {
                    return $candidates[0]['content']['parts'][0]['text'];
                }
            }
        } catch (\Throwable $e) {
            // Fallback cleanly
        }

        return null;
    }

    /**
     * Upgraded OpenAI ChatGPT Integration with Multi-Turn Memory & RAG Grounding
     */
    protected function callOpenAiApi($companyId, string $userMessage, array $history = []): ?string
    {
        try {
            $apiKey = PlatformSetting::get('ai_openai_api_key') ?: env('OPENAI_API_KEY');
            $model = env('OPENAI_MODEL', 'gpt-4o-mini');

            if (empty($apiKey)) {
                return null;
            }

            $enterpriseContext = $this->buildEnterpriseContext($companyId);
            $systemInstruction = $this->getEnterpriseSystemPrompt($enterpriseContext);

            $messages = [
                ['role' => 'system', 'content' => $systemInstruction]
            ];

            // Append multi-turn history
            foreach (array_slice($history, -6) as $item) {
                if (!empty($item['text'])) {
                    $role = ($item['sender'] ?? '') === 'user' ? 'user' : 'assistant';
                    $messages[] = ['role' => $role, 'content' => $item['text']];
                }
            }

            // Append current message
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $response = Http::timeout(10)->withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.2,
                'max_tokens' => 1500,
            ]);

            if ($response->successful()) {
                $choices = $response->json('choices');
                if (!empty($choices[0]['message']['content'])) {
                    return $choices[0]['message']['content'];
                }
            }
        } catch (\Throwable $e) {
            // Fallback cleanly
        }

        return null;
    }

    /**
     * Autonomous Financial Math & Tax Simulator
     * Evaluates calculations for PPh 21, PPh 23, and Fixed Asset Depreciation with exact journal entries.
     */
    protected function resolveFinancialCalculation(string $msg, $companyId): ?string
    {
        $isCalc = str_contains($msg, 'hitung') || str_contains($msg, 'kalkulator') || str_contains($msg, 'simulasi') || str_contains($msg, 'kalkulasi') || str_contains($msg, 'berapa') || str_contains($msg, 'potong') || str_contains($msg, 'pajak') || str_contains($msg, 'dividen') || str_contains($msg, 'deviden');

        // 0. Simulasi Pajak Dividen (Orang Pribadi vs Badan - UU HPP & UU Cipta Kerja)
        if (str_contains($msg, 'dividen') || str_contains($msg, 'deviden')) {
            $nominal = $this->extractAmountFromText($msg) ?? 1000000000;
            $pphFinalOp = round($nominal * 0.10);

            return "### 💰 Simulasi & Ketentuan Pajak Pengambilan Dividen: Rp " . number_format($nominal, 0, ',', '.') . "\n\n"
                 . "Mengacu pada **UU No. 7 Tahun 2021 (UU HPP)**, **UU No. 11 Tahun 2020 (UU Cipta Kerja)**, dan **PMK No. 18/PMK.03/2021**, besaran pajak atas pembagian dividen sebesar **Rp " . number_format($nominal, 0, ',', '.') . "** diatur berdasarkan status penerima dan pemanfaatannya:\n\n"
                 . "---\n\n"
                 . "#### 1. Penerima: Wajib Pajak Orang Pribadi Dalam Negeri (OPDN)\n"
                 . "| Skenario Pemanfaatan | Tarif PPh | Nominal Pajak | Status & Tata Cara Pelaporan |\n"
                 . "|---|:---:|:---:|---|\n"
                 . "| **A. Diinvestasikan Kembali di Indonesia** | **0% (BEBAS PPh)** | **Rp 0** | Dana dividen diinvestasikan kembali di wilayah NKRI minimal **3 tahun pajak berturut-turut** dalam instrumen investasi yang sah (SBN, Deposito, Reksadana, Saham BEI, Sektor Riil) dan dilaporkan melalui **e-Reporting Dividen** di DJP Online. |\n"
                 . "| **B. Tidak Diinvestasikan (Konsumsi / Pakai Pribadi)** | **10% Final** | **Rp " . number_format($pphFinalOp, 0, ',', '.') . "** | Dikenakan **PPh Final Pasal 17 ayat (2c) UU PPh sebesar 10%**. Wajib disetor sendiri oleh penerima paling lambat tanggal 15 bulan berikutnya melalui Kode Billing MAP 411128 KLU 419. |\n\n"
                 . "---\n\n"
                 . "#### 2. Penerima: Wajib Pajak Badan Dalam Negeri (PT / CV / Koperasi)\n"
                 . "• **Bukan Objek Pajak (Bebas PPh 0% = Rp 0):** Sesuai Pasal 4 ayat (3) huruf f UU PPh s.t.d.t.d UU HPP, dividen yang diterima oleh Badan Usaha Dalam Negeri dari perseroan terbatas di Indonesia **100% BEBAS PAJAK TANPA SYARAT INVESTASI** dan tidak ada pemotongan PPh Pasal 23.\n\n"
                 . "---\n\n"
                 . "#### 3. Rekomendasi Jurnal Pembagian Dividen di AKRU (Entitas Perseroan):\n"
                 . "1. **Saat RUPS Mengesahkan Pembagian Dividen Rp " . number_format($nominal, 0, ',', '.') . "**:\n"
                 . "   - **[Debit]** `[3200] Laba Ditahan (Retained Earnings)` : **Rp " . number_format($nominal, 0, ',', '.') . "**\n"
                 . "   - **[Kredit]** `[2150] Hutang Dividen` : **Rp " . number_format($nominal, 0, ',', '.') . "**\n\n"
                 . "2. **Saat Pembayaran Kas / Transfer Bank ke Pemegang Saham**:\n"
                 . "   - **[Debit]** `[2150] Hutang Dividen` : **Rp " . number_format($nominal, 0, ',', '.') . "**\n"
                 . "   - **[Kredit]** `[1110] Bank BCA Operasional / Kas` : **Rp " . number_format($nominal, 0, ',', '.') . "**\n\n"
                 . "---\n\n"
                 . "> 💡 **Rekomendasi *Tax Planning* (Optimalisasi Pajak):**\n"
                 . "> Jika Anda mengambil dividen sebagai Orang Pribadi, Anda dapat **menghemat pajak Rp " . number_format($pphFinalOp, 0, ',', '.') . "** menjadi **Rp 0** dengan cara menempatkan dana dividen tersebut ke instrumen investasi resmi di Indonesia selama minimal 3 tahun pajak.";
        }

        // 1. Simulasi PPh 21 Karyawan (PP 58/2023 TER Bulanan)
        if ($isCalc && (str_contains($msg, '21') || str_contains($msg, 'gaji') || str_contains($msg, 'karyawan') || str_contains($msg, 'upah'))) {
            $nominal = $this->extractAmountFromText($msg);
            if ($nominal && $nominal >= 1000000) {
                $category = 'A';
                $ptkpLabel = 'TK/0 (PTKP Rp 54.000.000 / tahun)';

                if (str_contains($msg, 'k/1') || str_contains($msg, 'k1') || str_contains($msg, 'tk/2') || str_contains($msg, 'tk2') || str_contains($msg, 'k/2') || str_contains($msg, 'k2')) {
                    $category = 'B';
                    $ptkpLabel = 'Kategori B (K/1, K/2, TK/2, TK/3)';
                } elseif (str_contains($msg, 'k/3') || str_contains($msg, 'k3')) {
                    $category = 'C';
                    $ptkpLabel = 'Kategori C (K/3)';
                }

                $terRate = 0.0;
                if ($category === 'A') {
                    if ($nominal <= 5400000) $terRate = 0.0;
                    elseif ($nominal <= 5650000) $terRate = 0.0025;
                    elseif ($nominal <= 5950000) $terRate = 0.005;
                    elseif ($nominal <= 6300000) $terRate = 0.0075;
                    elseif ($nominal <= 6750000) $terRate = 0.01;
                    elseif ($nominal <= 7500000) $terRate = 0.0125;
                    elseif ($nominal <= 8550000) $terRate = 0.015;
                    elseif ($nominal <= 9650000) $terRate = 0.0175;
                    elseif ($nominal <= 10050000) $terRate = 0.02;
                    elseif ($nominal <= 10350000) $terRate = 0.0225;
                    elseif ($nominal <= 10700000) $terRate = 0.025;
                    elseif ($nominal <= 11050000) $terRate = 0.03;
                    elseif ($nominal <= 11600000) $terRate = 0.035;
                    elseif ($nominal <= 12500000) $terRate = 0.04;
                    elseif ($nominal <= 13750000) $terRate = 0.05;
                    elseif ($nominal <= 15100000) $terRate = 0.06;
                    elseif ($nominal <= 16950000) $terRate = 0.07;
                    elseif ($nominal <= 19750000) $terRate = 0.08;
                    elseif ($nominal <= 24150000) $terRate = 0.09;
                    else $terRate = 0.10;
                } else {
                    if ($nominal <= 6200000) $terRate = 0.0;
                    elseif ($nominal <= 6500000) $terRate = 0.0025;
                    elseif ($nominal <= 6850000) $terRate = 0.005;
                    elseif ($nominal <= 7300000) $terRate = 0.0075;
                    elseif ($nominal <= 9200000) $terRate = 0.01;
                    elseif ($nominal <= 10750000) $terRate = 0.015;
                    elseif ($nominal <= 12550000) $terRate = 0.02;
                    elseif ($nominal <= 14950000) $terRate = 0.04;
                    elseif ($nominal <= 17000000) $terRate = 0.06;
                    else $terRate = 0.08;
                }

                $pph21Amount = round($nominal * $terRate);
                $gajiBersih = $nominal - $pph21Amount;
                $pctDisplay = ($terRate * 100) . '%';

                return "### 🧮 Simulasi Perhitungan PPh 21 Karyawan (PP No. 58/2023)\n\n"
                     . "Berdasarkan penghasilan bruto **Rp " . number_format($nominal, 0, ',', '.') . "** per bulan dengan status **{$ptkpLabel}**:\n\n"
                     . "| Komponen Perhitungan | Nilai (Rp) | Keterangan |\n"
                     . "|---|---|---|\n"
                     . "| **Gaji Bruto Bulanan** | **Rp " . number_format($nominal, 0, ',', '.') . "** | Kompensasi imbalan kerja |\n"
                     . "| **Kategori TER** | **TER Kategori {$category}** | PP 58/2023 Lampiran TER Bulanan |\n"
                     . "| **Tarif Efektif Rata-Rata (TER)** | **{$pctDisplay}** | Berlaku Masa Pajak Januari – November |\n"
                     . "| **Potongan PPh 21 Terutang** | **Rp " . number_format($pph21Amount, 0, ',', '.') . "** | Disetor ke Kas Negara paling lambat tgl 10 bln depan |\n"
                     . "| **Gaji Bersih Diterima (*Take Home Pay*)** | **Rp " . number_format($gajiBersih, 0, ',', '.') . "** | Dibayarkan via transfer bank payroll |\n\n"
                     . "#### Rekomendasi Jurnal Penggajian di AKRU:\n"
                     . "• **[Debit]** `[6100] Beban Gaji & Tunjangan Karyawan`: **Rp " . number_format($nominal, 0, ',', '.') . "**\n"
                     . "• **[Kredit]** `[2210] Hutang PPh 21 Karyawan`: **Rp " . number_format($pph21Amount, 0, ',', '.') . "**\n"
                     . "• **[Kredit]** `[1110] Bank BCA Operasional`: **Rp " . number_format($gajiBersih, 0, ',', '.') . "**\n\n"
                     . "> **Catatan:** Pada Masa Pajak Desember, lakukan rekonsiliasi tahunan menggunakan tarif progresif **Pasal 17 UU HPP** untuk menghitung lebih/kurang bayar tahunan.";
            }
        }

        // 2. Simulasi PPh 23 Jasa / Sewa Harta
        if ($isCalc && (str_contains($msg, '23') || str_contains($msg, 'jasa') || str_contains($msg, 'konsultan') || str_contains($msg, 'servis') || str_contains($msg, 'royalti'))) {
            $nominal = $this->extractAmountFromText($msg);
            if ($nominal && $nominal >= 100000) {
                $hasNpwp = !str_contains($msg, 'non npwp') && !str_contains($msg, 'tanpa npwp');
                $rate = $hasNpwp ? 0.02 : 0.04;
                $ratePct = $hasNpwp ? '2%' : '4% (Non-NPWP +100%)';
                $pph23 = round($nominal * $rate);
                $netPayout = $nominal - $pph23;

                return "### 🧮 Simulasi Perhitungan PPh Pasal 23 (Jasa & Sewa Harta)\n\n"
                     . "Berdasarkan nilai tagihan jasa bruto **Rp " . number_format($nominal, 0, ',', '.') . "**:\n\n"
                     . "| Rincian Transaksi | Nilai (Rp) | Keterangan |\n"
                     . "|---|---|---|\n"
                     . "| **Nilai Tagihan Bruto (DPP)** | **Rp " . number_format($nominal, 0, ',', '.') . "** | Di luar nilai PPN 11% |\n"
                     . "| **Tarif PPh 23** | **{$ratePct}** | UU PPh & PMK No. 141/2015 |\n"
                     . "| **Potongan PPh 23 (WHT)** | **Rp " . number_format($pph23, 0, ',', '.') . "** | Disetor via e-Billing MAP 411124 KLU 104 |\n"
                     . "| **Kas Bersih ke Rekanan Vendor** | **Rp " . number_format($netPayout, 0, ',', '.') . "** | Jumlah transfer bersih |\n\n"
                     . "#### Jurnal Pembukuan di AKRU:\n"
                     . "• **[Debit]** `[6200/6900] Beban Jasa / Operasional`: **Rp " . number_format($nominal, 0, ',', '.') . "**\n"
                     . "• **[Kredit]** `[2220] Hutang PPh 23 Jasa`: **Rp " . number_format($pph23, 0, ',', '.') . "**\n"
                     . "• **[Kredit]** `[1110] Kas / Bank`: **Rp " . number_format($netPayout, 0, ',', '.') . "**\n\n"
                     . "> **Kewajiban Pelaporan:** Terbitkan Bukti Potong Elektronik (**e-Bupot Unifikasi**) di Coretax DJP paling lambat akhir bulan kalender berikutnya.";
            }
        }

        // 3. Simulasi Penyusutan Aset Tetap (Pasal 11 UU PPh)
        if ($isCalc && (str_contains($msg, 'penyusutan') || str_contains($msg, 'depresiasi') || str_contains($msg, 'susut'))) {
            $nominal = $this->extractAmountFromText($msg);
            if ($nominal && $nominal >= 1000000) {
                $group = 1;
                $years = 4;
                $slRate = 0.25;
                $ddRate = 0.50;

                if (str_contains($msg, 'kelompok 2') || str_contains($msg, 'kel 2') || str_contains($msg, 'mobil') || str_contains($msg, 'motor') || str_contains($msg, 'kendaraan')) {
                    $group = 2; $years = 8; $slRate = 0.125; $ddRate = 0.25;
                } elseif (str_contains($msg, 'kelompok 3') || str_contains($msg, 'kel 3') || str_contains($msg, 'mesin')) {
                    $group = 3; $years = 16; $slRate = 0.0625; $ddRate = 0.125;
                } elseif (str_contains($msg, 'kelompok 4') || str_contains($msg, 'kel 4')) {
                    $group = 4; $years = 20; $slRate = 0.05; $ddRate = 0.10;
                } elseif (str_contains($msg, 'bangunan') || str_contains($msg, 'gedung')) {
                    $group = 'Bangunan Permanen'; $years = 20; $slRate = 0.05; $ddRate = 0;
                }

                $annualSl = round($nominal * $slRate);
                $monthlySl = round($annualSl / 12);
                $annualDd = round($nominal * $ddRate);

                return "### 🏢 Simulasi Penyusutan Aset Tetap (Pasal 11 UU PPh)\n\n"
                     . "Perhitungan beban penyusutan atas harga perolehan **Rp " . number_format($nominal, 0, ',', '.') . "** (Kelompok {$group}):\n\n"
                     . "| Parameter Depresiasi | Nilai Komersial & Fiskal |\n"
                     . "|---|---|\n"
                     . "| **Kelompok Harta Berwujud** | **Kelompok {$group}** ({$years} Tahun Masa Manfaat) |\n"
                     . "| **Metode Garis Lurus (*Straight-Line*)** | **" . ($slRate * 100) . "%** per tahun |\n"
                     . "| **Beban Penyusutan Tahunan (Garis Lurus)** | **Rp " . number_format($annualSl, 0, ',', '.') . "** / tahun |\n"
                     . "| **Beban Penyusutan Bulanan (Amortisasi)** | **Rp " . number_format($monthlySl, 0, ',', '.') . "** / bulan |\n"
                     . ($ddRate > 0 ? "| **Metode Saldo Menurun (Tahun ke-1)** | **" . ($ddRate * 100) . "%** = Rp " . number_format($annualDd, 0, ',', '.') . " |\n" : "")
                     . "\n#### Jurnal Penyesuaian Bulanan Otomatis di AKRU:\n"
                     . "• **[Debit]** `[6500] Beban Penyusutan Aset Tetap`: **Rp " . number_format($monthlySl, 0, ',', '.') . "**\n"
                     . "• **[Kredit]** `[1610/1710] Akumulasi Penyusutan Aset`: **Rp " . number_format($monthlySl, 0, ',', '.') . "**\n\n"
                     . "> Daftarkan aset ini di menu **Akuntansi > Register Aset Tetap** agar sistem menghitung dan memposting penyusutan bulanan secara terjadwal.";
            }
        }

        // 4. Simulasi Titik Impas (Break-Even Point / BEP)
        if ($isCalc && (str_contains($msg, 'bep') || str_contains($msg, 'titik impas') || str_contains($msg, 'break even'))) {
            $hasFC = preg_match('/(?:biaya\s*tetap|fixed\s*cost|fc)[\s\:\=]*(?:rp\.?\s*)?(\d+(?:[\.,]\d+)?)\s*(juta|jt|miliar|m|ribu|rb)?/i', $msg, $mFC);
            $hasP = preg_match('/(?:harga(?:\s*jual)?|price|p)[\s\:\=]*(?:rp\.?\s*)?(\d+(?:[\.,]\d+)?)\s*(juta|jt|miliar|m|ribu|rb)?/i', $msg, $mP);
            $hasVC = preg_match('/(?:biaya\s*variabel|variable\s*cost|vc)[\s\:\=]*(?:rp\.?\s*)?(\d+(?:[\.,]\d+)?)\s*(juta|jt|miliar|m|ribu|rb)?/i', $msg, $mVC);

            if ($hasFC && $hasP && $hasVC) {
                $parseVal = function($val, $unit) {
                    $v = floatval(str_replace(',', '.', $val));
                    $u = strtolower($unit ?? '');
                    if ($u === 'juta' || $u === 'jt') return $v * 1000000;
                    if ($u === 'miliar' || $u === 'm') return $v * 1000000000;
                    if ($u === 'ribu' || $u === 'rb') return $v * 1000;
                    return $v;
                };

                $fc = $parseVal($mFC[1], $mFC[2] ?? '');
                $p = $parseVal($mP[1], $mP[2] ?? '');
                $vc = $parseVal($mVC[1], $mVC[2] ?? '');

                if ($p > $vc) {
                    $cmUnit = $p - $vc;
                    $cmRatio = ($cmUnit / $p);
                    $bepUnit = ceil($fc / $cmUnit);
                    $bepRupiah = $bepUnit * $p;

                    return "### ⚖️ Simulasi Perhitungan Titik Impas (BEP) Bisnis\n\n"
                         . "Berdasarkan struktur biaya dan harga yang Anda sampaikan:\n\n"
                         . "| Komponen Finansial | Nilai (Rp) | Keterangan |\n"
                         . "|---|---|---|\n"
                         . "| **Biaya Tetap (*Fixed Cost*)** | **Rp " . number_format($fc, 0, ',', '.') . "** | Beban bulanan konstan |\n"
                         . "| **Harga Jual per Unit (*Price*)** | **Rp " . number_format($p, 0, ',', '.') . "** | Nilai jual bersih ke pelanggan |\n"
                         . "| **Biaya Variabel per Unit (*VC*)** | **Rp " . number_format($vc, 0, ',', '.') . "** | HPP bahan baku & kemasan per unit |\n"
                         . "| **Margin Kontribusi per Unit** | **Rp " . number_format($cmUnit, 0, ',', '.') . "** | Sumbangan laba per unit terjual |\n"
                         . "| **Rasio Margin Kontribusi** | **" . round($cmRatio * 100, 1) . "%** | Persentase laba kotor terhadap omzet |\n\n"
                         . "---\n\n"
                         . "🎯 **Hasil Analisis Titik Impas (BEP):**\n"
                         . "• **BEP dalam Satuan Unit Produk:** **" . number_format($bepUnit, 0, ',', '.') . " Unit**\n"
                         . "• **BEP dalam Nilai Omzet / Rupiah:** **Rp " . number_format($bepRupiah, 0, ',', '.') . "**\n\n"
                         . "> **Interpretasi Keputusan Bisnis:** Anda wajib menjual minimal **" . number_format($bepUnit, 0, ',', '.') . " unit** dengan omzet minimal **Rp " . number_format($bepRupiah, 0, ',', '.') . "** untuk mencapai titik impas (tidak untung dan tidak rugi). Penjualan di atas volume tersebut akan menghasilkan laba bersih sebesar **Rp " . number_format($cmUnit, 0, ',', '.') . "** untuk setiap unit tambahan yang terjual.";
                }
            }
        }

        return null;
    }

    /**
     * Extracts currency numeric amounts from natural language text
     */
    protected function extractAmountFromText(string $text): ?float
    {
        // 1. Matches formatted numbers e.g. "Rp 15.000.000", "Rp 10.500.000"
        if (preg_match('/(?:rp\.?\s*)?(\d{1,3}(?:\.\d{3})+(?:,\d+)?)/i', $text, $m)) {
            $cleaned = str_replace('.', '', $m[1]);
            $cleaned = str_replace(',', '.', $cleaned);
            return floatval($cleaned);
        }

        // 2. Matches Indonesian text units e.g. "15 juta", "10 jt", "500 ribu", "2.5 miliar"
        if (preg_match('/(?:rp\.?\s*)?(\d+(?:[\.,]\d+)?)\s*(juta|jt|miliar|m|ribu|rb)/i', $text, $m)) {
            $num = floatval(str_replace(',', '.', $m[1]));
            $unit = strtolower($m[2]);
            if ($unit === 'juta' || $unit === 'jt') return $num * 1000000;
            if ($unit === 'miliar' || $unit === 'm') return $num * 1000000000;
            if ($unit === 'ribu' || $unit === 'rb') return $num * 1000;
        }

        // 3. Matches plain integers e.g. "15000000"
        if (preg_match('/(?:rp\.?\s*)?(\d{5,})/i', $text, $m)) {
            return floatval($m[1]);
        }

        return null;
    }

    /**
     * Helper to resolve account based on natural language keywords and synonyms
     */
    protected function resolveAccountSuggestion($companyId, string $query): array
    {
        $cleanQuery = strtolower(trim($query));

        // Keyword mapping rules for Indonesian standard Chart of Accounts
        $synonymRules = [
            // 1. Utilitas, Listrik, Air & Internet
            'internet' => ['keywords' => ['internet', 'wifi', 'indihome', 'biznet', 'myrepublic', 'kuota', 'pulsa', 'listrik', 'pln', 'air', 'pdam', 'pam', 'token', 'utilitas'], 'acc_pattern' => '6300', 'name_like' => 'internet', 'reason' => 'Pengeluaran untuk koneksi jaringan, listrik, air, dan komunikasi operasional diklasifikasikan sebagai Beban Utilitas & Komunikasi periode berjalan.', 'debit' => 'Beban Listrik, Air & Internet', 'credit' => 'Kas / Bank BCA / Bank Mandiri'],
            
            // 2. Transportasi & BBM
            'transport' => ['keywords' => ['bensin', 'bbm', 'pertalite', 'pertamax', 'solar', 'shell', 'bahan bakar', 'tol', 'parkir', 'taksi', 'grab', 'gojek', 'ongkir', 'ongkos kirim', 'kurir', 'ekspedisi', 'jne', 'jnt', 'sicepat'], 'acc_pattern' => '6700', 'name_like' => 'transport', 'reason' => 'Beban bahan bakar, tol, tiket transportasi dinas, dan ongkos angkut kurir diakui sebagai Beban Transportasi & Bahan Bakar.', 'debit' => 'Beban Transportasi & Bahan Bakar', 'credit' => 'Kas Kecil / Kas Tunai / Bank'],

            // 3. Perlengkapan & ATK Kantor
            'atk' => ['keywords' => ['atk', 'kertas', 'hvs', 'tinta', 'toner', 'printer', 'pulpen', 'alat tulis', 'map', 'buku', 'amplop', 'lakban', 'stapler', 'perlengkapan kantor'], 'acc_pattern' => '6600', 'name_like' => 'perlengkapan', 'reason' => 'Barang habis pakai perkantoran yang masa manfaatnya kurang dari satu tahun diakui langsung sebagai Beban Perlengkapan & ATK.', 'debit' => 'Beban Perlengkapan & ATK Kantor', 'credit' => 'Kas Kecil / Bank'],

            // 4. Peralatan Kantor & Komputer (Aset Tetap)
            'peralatan' => ['keywords' => ['beli laptop', 'beli komputer', 'pc', 'macbook', 'beli printer', 'beli server', 'meja kantor', 'kursi kantor', 'lemari arsip', 'ac split baru', 'peralatan kantor'], 'acc_pattern' => '1600', 'name_like' => 'peralatan', 'reason' => 'Pengeluaran kapital untuk barang berwujud dengan masa manfaat lebih dari 1 tahun dicatat sebagai Aset Tetap dan disusutkan secara periodik.', 'debit' => 'Peralatan Kantor & Komputer (Aset)', 'credit' => 'Bank / Kas / Hutang Usaha'],

            // 5a. Sewa Dibayar di Muka (Prepaid Rent - Aset Lancar)
            'sewa_dimuka' => [
                'keywords' => ['sewa dibayar dimuka', 'sewa di muka', 'sewa dimuka', 'sewa setahun', 'sewa ruko setahun', 'sewa gedung setahun', 'menyewa ruko', 'menyewa gedung', 'kontrak setahun', 'kontrak 1 tahun', 'bayar sewa di muka'],
                'acc_pattern' => '1500',
                'name_like' => 'sewa dibayar di muka',
                'reason' => 'Sewa dibayar di muka untuk periode 1 tahun diakui sebagai Aset Lancar (Sewa Dibayar di Muka) dan diamortisasi periodik tiap bulan ke Beban Sewa.',
                'debit' => 'Uang Muka Pembelian & Sewa Dibayar di Muka (Aset)',
                'credit' => 'Kas / Bank (setelah dipotong PPh 4(2) 10%)'
            ],

            // 5b. Sewa Gedung & Kantor Operasional (Beban)
            'sewa' => [
                'keywords' => ['sewa kantor', 'sewa gedung', 'sewa ruko', 'sewa gudang', 'sewa tempat', 'kontrak kantor', 'sewa ruang', 'sewa bulanan', 'sewa', 'menyewa', 'ruko'],
                'acc_pattern' => '6200',
                'name_like' => 'sewa',
                'reason' => 'Beban sewa tempat usaha operasional diakui sebagai Beban Sewa Gedung & Kantor pada periode berjalan.',
                'debit' => 'Beban Sewa Gedung & Kantor',
                'credit' => 'Bank / Kas / Hutang PPh 4(2)'
            ],

            // 6. Gaji & Karyawan
            'gaji' => ['keywords' => ['gaji', 'upah', 'salary', 'payroll', 'thr', 'bonus', 'insentif', 'lembur', 'tunjangan', 'bpjs ketenagakerjaan', 'bpjs kesehatan'], 'acc_pattern' => '6100', 'name_like' => 'gaji', 'reason' => 'Kompensasi imbalan kerja karyawan diakui pada akun Beban Gaji & Tunjangan pada saat terutang atau dibayarkan.', 'debit' => 'Beban Gaji & Tunjangan Karyawan', 'credit' => 'Kas / Bank (setelah potong PPh 21)'],

            // 7. Pemasaran & Promosi
            'iklan' => ['keywords' => ['iklan', 'ads', 'facebook ads', 'google ads', 'instagram ads', 'tiktok ads', 'brosur', 'promosi', 'marketing', 'spanduk', 'banner', 'endorse', 'pameran'], 'acc_pattern' => '6400', 'name_like' => 'pemasaran', 'reason' => 'Biaya penayangan iklan digital, materi promosi, dan kegiatan pemasaran diakui sebagai Beban Pemasaran & Promosi.', 'debit' => 'Beban Pemasaran & Promosi', 'credit' => 'Kartu Kredit / Bank / Kas'],

            // 8. Pemeliharaan & Perbaikan
            'servis' => ['keywords' => ['servis', 'service', 'perbaikan', 'reparasi', 'maintenance', 'cuci ac', 'ganti oli', 'bengkel', 'renovasi kecil'], 'acc_pattern' => '6900', 'name_like' => 'operasional', 'reason' => 'Perawatan rutin aset agar tetap berfungsi optimal dicatat sebagai Beban Pemeliharaan / Operasional Lain-lain.', 'debit' => 'Beban Operasional Lain-lain', 'credit' => 'Kas Kecil / Bank'],

            // 9. Administrasi Bank
            'bank_fee' => ['keywords' => ['admin bank', 'biaya transfer', 'biaya kliring', 'biaya rtg', 'buku cek', 'provisi bank'], 'acc_pattern' => '6800', 'name_like' => 'administrasi bank', 'reason' => 'Potongan biaya administrasi bulanan dan transfer perbankan diakui sebagai Beban Administrasi Bank.', 'debit' => 'Beban Administrasi Bank', 'credit' => 'Rekening Bank Terkait'],
        ];

        // Match against domain synonym rules
        foreach ($synonymRules as $rule) {
            foreach ($rule['keywords'] as $kw) {
                if (str_contains($cleanQuery, $kw)) {
                    $account = Account::where('company_id', $companyId)
                        ->where('code', 'like', "{$rule['acc_pattern']}%")
                        ->first();

                    if (!$account) {
                        $account = Account::where('company_id', $companyId)
                            ->where('name', 'like', "%{$rule['name_like']}%")
                            ->first();
                    }

                    if ($account) {
                        return [
                            'account' => $account,
                            'confidence' => '97%',
                            'reasoning' => $rule['reason'],
                            'journal_hint' => "Debit: [{$account->code}] {$account->name} | Kredit: {$rule['credit']}",
                        ];
                    }
                }
            }
        }

        // Tokenized fallback search in accounts table
        $tokens = preg_split('/[\s,\-_]+/', $cleanQuery);
        $stopWords = ['kantor', 'beli', 'biaya', 'bayar', 'tagihan', 'untuk', 'dan', 'di', 'ke', 'pt', 'cv', 'bulanan', 'dinas'];
        $usefulTokens = array_filter($tokens, fn($t) => strlen($t) > 2 && !in_array($t, $stopWords));

        foreach ($usefulTokens as $token) {
            $account = Account::where('company_id', $companyId)
                ->where(function ($q) use ($token) {
                    $q->where('name', 'like', "%{$token}%")
                      ->orWhere('code', 'like', "%{$token}%");
                })->first();

            if ($account) {
                return [
                    'account' => $account,
                    'confidence' => '89%',
                    'reasoning' => "Ditemukan kecocokan kata kunci '{$token}' pada nama akun bagan akun entitas Anda.",
                    'journal_hint' => "Debit: [{$account->code}] {$account->name} | Kredit: Kas / Rekening Bank yang digunakan.",
                ];
            }
        }

        // General default fallback: Beban Operasional Lain-lain (6900), bukan Beban Gaji!
        $defaultExpense = Account::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('code', 'like', '69%')->orWhere('name', 'like', '%lain-lain%');
            })->first();

        if (!$defaultExpense) {
            $defaultExpense = Account::where('company_id', $companyId)->where('type', 'expense')->first();
        }

        return [
            'account' => $defaultExpense,
            'confidence' => '70%',
            'reasoning' => 'Uraian belum memiliki pola kata kunci spesifik, disarankan masuk ke akun beban operasional umum atau diperiksa kembali oleh bagian akuntansi.',
            'journal_hint' => $defaultExpense ? "Debit: [{$defaultExpense->code}] {$defaultExpense->name} | Kredit: Kas / Bank." : "Debit Akun Beban, Kredit Kas/Bank.",
        ];
    }
}
