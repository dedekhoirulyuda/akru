<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AkruAiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Providers
        DB::table('ai_providers')->updateOrInsert(
            ['code' => 'akru_native'],
            [
                'name' => 'AKRU Native AI (Gratis)',
                'adapter_class' => 'App\\Modules\\Ai\\Services\\Providers\\AkruNativeProvider',
                'is_active' => true,
                'capability_flags_json' => json_encode([
                    'cost' => 'free',
                    'supports_tools' => true,
                    'supports_citations' => true,
                    'is_fallback' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('ai_providers')->updateOrInsert(
            ['code' => 'google_gemini'],
            [
                'name' => 'Google Gemini Pro',
                'adapter_class' => 'App\\Modules\\Ai\\Services\\Providers\\GeminiProvider',
                'is_active' => true,
                'capability_flags_json' => json_encode([
                    'cost' => 'external_api',
                    'supports_tools' => true,
                    'supports_streaming' => true,
                    'supports_multimodal' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('ai_providers')->updateOrInsert(
            ['code' => 'openai'],
            [
                'name' => 'OpenAI ChatGPT',
                'adapter_class' => 'App\\Modules\\Ai\\Services\\Providers\\OpenAiProvider',
                'is_active' => true,
                'capability_flags_json' => json_encode([
                    'cost' => 'external_api',
                    'supports_tools' => true,
                    'supports_streaming' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $nativeId = DB::table('ai_providers')->where('code', 'akru_native')->value('id');
        $geminiId = DB::table('ai_providers')->where('code', 'google_gemini')->value('id');
        $openaiId = DB::table('ai_providers')->where('code', 'openai')->value('id');

        // 2. Provider Models
        if ($nativeId) {
            DB::table('ai_provider_models')->updateOrInsert(
                ['provider_id' => $nativeId, 'model_code' => 'native-rules-v1'],
                [
                    'display_name' => 'AKRU Deterministic FinLogic Engine',
                    'capability_flags_json' => json_encode(['tools' => true, 'deterministic' => true]),
                    'context_limit' => 16384,
                    'output_limit' => 4096,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($geminiId) {
            DB::table('ai_provider_models')->updateOrInsert(
                ['provider_id' => $geminiId, 'model_code' => 'gemini-1.5-flash'],
                [
                    'display_name' => 'Gemini 1.5 Flash (Fast & Cost-Efficient)',
                    'capability_flags_json' => json_encode(['tools' => true, 'multimodal' => true]),
                    'context_limit' => 1000000,
                    'output_limit' => 8192,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('ai_provider_models')->updateOrInsert(
                ['provider_id' => $geminiId, 'model_code' => 'gemini-2.0-flash'],
                [
                    'display_name' => 'Gemini 2.0 Flash (Next-Gen Reasoning)',
                    'capability_flags_json' => json_encode(['tools' => true, 'multimodal' => true]),
                    'context_limit' => 1000000,
                    'output_limit' => 8192,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($openaiId) {
            DB::table('ai_provider_models')->updateOrInsert(
                ['provider_id' => $openaiId, 'model_code' => 'gpt-4o-mini'],
                [
                    'display_name' => 'GPT-4o Mini (Affordable Intelligence)',
                    'capability_flags_json' => json_encode(['tools' => true, 'structured_outputs' => true]),
                    'context_limit' => 128000,
                    'output_limit' => 4096,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Default Global Anomaly Rules
        $rules = [
            [
                'code' => 'DUP_SALES_INVOICE',
                'name' => 'Potensi Duplikasi Faktur Penjualan',
                'category' => 'duplicate',
                'base_severity' => 'medium',
                'configuration_json' => json_encode(['window_days' => 1, 'match_fields' => ['total_amount', 'invoice_date']]),
            ],
            [
                'code' => 'LARGE_ROUND_CASH_EXPENSE',
                'name' => 'Pengeluaran Kas Bernilai Bulat Besar',
                'category' => 'threshold',
                'base_severity' => 'low',
                'configuration_json' => json_encode(['min_amount' => 5000000, 'modulo' => 1000000]),
            ],
            [
                'code' => 'MISSING_TAX_DOCUMENT',
                'name' => 'Transaksi Jasa Tanpa Bukti Potong Pajak (PPh 23)',
                'category' => 'reconciliation',
                'base_severity' => 'high',
                'configuration_json' => json_encode(['account_code_prefix' => '6', 'min_amount' => 2000000]),
            ],
            [
                'code' => 'NEGATIVE_STOCK_ALERT',
                'name' => 'Peringatan Saldo Persediaan Negatif',
                'category' => 'reconciliation',
                'base_severity' => 'critical',
                'configuration_json' => json_encode(['threshold' => 0]),
            ],
        ];

        foreach ($rules as $r) {
            DB::table('ai_anomaly_rules')->updateOrInsert(
                ['code' => $r['code']],
                array_merge($r, [
                    'version' => '1.0',
                    'detector_type' => 'rule',
                    'is_active' => true,
                    'shadow_mode' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 4. Default Global Knowledge Source
        $sourceId = DB::table('ai_knowledge_sources')->where('source_type', 'tax_regulation')->value('id');
        if (!$sourceId) {
            $sourceId = DB::table('ai_knowledge_sources')->insertGetId([
                'source_type' => 'tax_regulation',
                'name' => 'Regulasi & Ketentuan Perpajakan Indonesia (UU HPP & PMK)',
                'jurisdiction' => 'ID',
                'sensitivity' => 'public',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $docId = DB::table('ai_knowledge_documents')->where('title', 'Tarif PPh 21 TER & PPN UU HPP')->value('id');
        if (!$docId && $sourceId) {
            $docId = DB::table('ai_knowledge_documents')->insertGetId([
                'source_id' => $sourceId,
                'title' => 'Tarif PPh 21 TER & PPN UU HPP',
                'version' => '2026.1',
                'language' => 'id',
                'status' => 'approved',
                'effective_at' => '2024-01-01 00:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ai_knowledge_chunks')->insert([
                [
                    'document_id' => $docId,
                    'section_path' => 'PPh 21 TER Bulanan',
                    'chunk_text' => 'Berdasarkan PP No. 58 Tahun 2023, penghitungan PPh Pasal 21 bulanan Januari-November menggunakan Tarif Efektif Rata-Rata (TER) Kategori A (TK/0, TK/1, K/0), Kategori B (TK/2, TK/3, K/1, K/2), dan Kategori C (K/3). Tarif dimulai dari 0% untuk penghasilan bruto bulanan tertentu hingga 34%.',
                    'search_text' => 'pph 21 ter tarif efektif rata-rata pp 58 2023 kategori a b c ptkp gaji bulanan',
                    'token_count' => 65,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'document_id' => $docId,
                    'section_path' => 'PPN UU HPP',
                    'chunk_text' => 'Tarif PPN normal adalah 11% sesuai UU HPP No. 7 Tahun 2021. PPN Masukan atas pembelian barang modal atau beban operasional usaha dapat dikreditkan terhadap PPN Keluaran melalui SPT Masa PPN 1111.',
                    'search_text' => 'ppn 11 persen tarif ppn keluaran masukan uu hpp faktur pajak kredit pajak',
                    'token_count' => 50,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
