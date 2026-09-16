<?php

namespace App\Modules\Ai\Services\Providers;

use App\Modules\Ai\Services\Contracts\AiProviderContract;
use App\Modules\Ai\Services\DTOs\AiRequest;
use App\Modules\Ai\Services\DTOs\AiResponse;
use App\Modules\Ai\Services\DTOs\ProviderCapabilities;
use App\Modules\Ai\Services\DTOs\ProviderHealth;
use App\Modules\Ai\Services\AiToolRegistry;
use App\Modules\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProviderContract
{
    public function __construct(
        protected AiToolRegistry $toolRegistry
    ) {}

    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(
            supportsTools: true,
            supportsStreaming: true,
            supportsMultimodal: true,
            isFree: false,
            contextLimit: 1000000,
            outputLimit: 8192
        );
    }

    public function healthCheck(?string $apiKey = null): ProviderHealth
    {
        $key = $apiKey ?: PlatformSetting::get('ai_gemini_api_key') ?: env('GEMINI_API_KEY');
        if (!$key) {
            return new ProviderHealth(false, 'missing_credentials', 'Google Gemini API Key belum dikonfigurasi.');
        }

        try {
            $start = microtime(true);
            $res = Http::timeout(5)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}");
            $latency = (int) ((microtime(true) - $start) * 1000);

            if ($res->successful()) {
                return new ProviderHealth(true, 'operational', 'Terhubung ke Google Gemini API.', $latency);
            }

            return new ProviderHealth(false, 'error', 'Gagal memvalidasi API key Google Gemini: ' . $res->status());
        } catch (\Throwable $e) {
            return new ProviderHealth(false, 'unreachable', $e->getMessage());
        }
    }

    public function chat(AiRequest $request): AiResponse
    {
        $key = $request->apiKey ?: PlatformSetting::get('ai_gemini_api_key') ?: env('GEMINI_API_KEY');
        if (!$key) {
            throw new \RuntimeException("Google Gemini API Key is not configured.");
        }

        $model = $request->modelCode ?: PlatformSetting::get('ai_default_model') ?: env('GEMINI_MODEL', 'gemini-1.5-flash');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $systemInstruction = "Anda adalah AKRU AI, asisten keuangan enterprise berorientasi akuntansi (SAK/PSAK), perpajakan Indonesia, dan tata kelola bisnis. "
            . "Berikan jawaban profesional dalam Bahasa Indonesia. Gunakan data yang valid, sertakan angka konkrit bila tersedia, dan patuhi prinsip Human-in-the-Loop (AI tidak boleh memposting transaksi secara otonom).";

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $request->userMessage]]]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 2048,
            ],
        ];

        $response = Http::timeout(20)->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException("Gemini API Error: " . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Tidak ada respon dari Gemini.';

        return new AiResponse(
            answer: $text,
            summary: mb_substr(strip_tags($text), 0, 150) . '...',
            intent: 'generative_reasoning',
            scope: ['company_id' => $request->companyId, 'period' => 'current', 'data_as_of' => now()->toIso8601String()],
            metrics: [],
            findings: [],
            recommendations: [],
            citations: [],
            confidence: 0.90,
            riskLevel: 'low',
            requiresHumanReview: true,
            providerDisclosure: [
                'provider' => 'google_gemini',
                'model' => $model,
                'fallback_used' => false,
            ],
            usage: [
                'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                'candidates_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            ]
        );
    }
}
