<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Models\AiProvider;
use App\Modules\Ai\Models\AiUsageEvent;
use App\Modules\Core\Models\AiChatUsage;
use Illuminate\Support\Str;

class AiUsageService
{
    /**
     * Record a chat usage event and update daily counter.
     */
    public function recordEvent(int $companyId, ?int $userId, string $providerCode, array $usage = [], int $latencyMs = 0): AiUsageEvent
    {
        $provider = AiProvider::where('code', $providerCode)->first();

        $event = AiUsageEvent::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'provider_id' => $provider?->id,
            'capability_code' => 'chat',
            'request_units' => 1,
            'input_tokens' => $usage['prompt_tokens'] ?? 0,
            'output_tokens' => $usage['completion_tokens'] ?? ($usage['candidates_tokens'] ?? 0),
            'estimated_cost' => 0,
            'latency_ms' => $latencyMs,
            'status' => 'success',
            'request_id' => (string) Str::uuid(),
        ]);

        AiChatUsage::recordUsage($companyId, $userId);

        return $event;
    }
}
