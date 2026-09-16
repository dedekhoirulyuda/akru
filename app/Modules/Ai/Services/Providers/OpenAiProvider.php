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

class OpenAiProvider implements AiProviderContract
{
    public function __construct(
        protected AiToolRegistry $toolRegistry
    ) {}

    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(
            supportsTools: true,
            supportsStreaming: true,
            supportsMultimodal: false,
            isFree: false,
            contextLimit: 128000,
            outputLimit: 4096
        );
    }

    public function healthCheck(?string $apiKey = null): ProviderHealth
    {
        $key = $apiKey ?: PlatformSetting::get('ai_openai_api_key') ?: env('OPENAI_API_KEY');
        if (!$key) {
            return new ProviderHealth(false, 'missing_credentials', 'OpenAI API Key belum dikonfigurasi.');
        }

        try {
            $start = microtime(true);
            $res = Http::withToken($key)->timeout(5)->get('https://api.openai.com/v1/models');
            $latency = (int) ((microtime(true) - $start) * 1000);

            if ($res->successful()) {
                return new ProviderHealth(true, 'operational', 'Terhubung ke OpenAI API.', $latency);
            }

            return new ProviderHealth(false, 'error', 'Gagal memvalidasi API key OpenAI: ' . $res->status());
        } catch (\Throwable $e) {
            return new ProviderHealth(false, 'unreachable', $e->getMessage());
        }
    }

    public function chat(AiRequest $request): AiResponse
    {
        $key = $request->apiKey ?: PlatformSetting::get('ai_openai_api_key') ?: env('OPENAI_API_KEY');
        if (!$key) {
            throw new \RuntimeException("OpenAI API Key is not configured.");
        }

        $model = $request->modelCode ?: env('OPENAI_MODEL', 'gpt-4o-mini');

        $messages = [
            [
                'role' => 'system',
                'content' => 'Anda adalah AKRU AI, asisten keuangan enterprise berorientasi akuntansi (SAK/PSAK), perpajakan Indonesia, dan tata kelola bisnis. Berikan jawaban profesional dalam Bahasa Indonesia.',
            ],
            [
                'role' => 'user',
                'content' => $request->userMessage,
            ],
        ];

        $res = Http::withToken($key)->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.2,
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException("OpenAI API Error: " . $res->body());
        }

        $data = $res->json();
        $text = $data['choices'][0]['message']['content'] ?? 'Tidak ada respon dari OpenAI.';

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
                'provider' => 'openai',
                'model' => $model,
                'fallback_used' => false,
            ],
            usage: [
                'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $data['usage']['total_tokens'] ?? 0,
            ]
        );
    }
}
