<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Models\AiConversation;
use App\Modules\Ai\Models\AiMessage;
use App\Modules\Ai\Models\AiMessageCitation;
use App\Modules\Ai\Models\AiToolCall;
use App\Modules\Ai\Models\AiProvider;
use App\Modules\Ai\Services\DTOs\AiRequest;
use App\Modules\Ai\Services\DTOs\AiResponse;
use Illuminate\Support\Facades\DB;

class AiOrchestratorService
{
    public function __construct(
        protected AiProviderRouter $router,
        protected AiPolicyService $policyService,
        protected AiUsageService $usageService
    ) {}

    /**
     * Process a chat interaction end-to-end.
     */
    public function processChat(int $companyId, int $userId, string $message, ?int $conversationId = null, string $mode = 'general'): array
    {
        $start = microtime(true);

        // 1. Quota Check
        $quota = $this->policyService->checkQuota($companyId);
        if (!$quota['allowed']) {
            return [
                'success' => false,
                'quota_exceeded' => true,
                'quota' => $quota,
                'reply' => "### ⏳ Batas Kuota Chat Harian Tercapai ({$quota['used']}/{$quota['limit']})\n\n"
                    . "Penggunaan chat AI entitas Anda hari ini telah mencapai batas maksimum. "
                    . "Kuota harian akan di-reset otomatis pada **pukul 00:00 WIB**. "
                    . "Anda dapat meng-upgrade paket langganan untuk menikmati akses tak terbatas.",
            ];
        }

        // 2. Redact sensitive input
        $sanitizedMessage = $this->policyService->redactSensitiveData($companyId, $message);

        // 3. Conversation Management
        $conversation = null;
        if ($conversationId) {
            $conversation = AiConversation::where('company_id', $companyId)->find($conversationId);
        }

        if (!$conversation) {
            $title = mb_substr($message, 0, 40) . (mb_strlen($message) > 40 ? '...' : '');
            $conversation = AiConversation::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'title' => $title,
                'mode' => $mode,
                'status' => 'active',
                'last_message_at' => now(),
            ]);
        }

        // Save User Message
        $userMsgModel = AiMessage::create([
            'company_id' => $companyId,
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'message_type' => 'text',
            'content' => $sanitizedMessage,
            'status' => 'success',
        ]);

        // 4. Construct Request DTO & Route to Provider
        $requestDto = new AiRequest(
            companyId: $companyId,
            userId: $userId,
            userMessage: $sanitizedMessage,
            mode: $mode,
            conversationId: $conversation->id,
            branchScope: (array) session('active_branch_id'),
            periodScope: ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->toDateString()]
        );

        $aiResponse = $this->router->route($requestDto);

        $latencyMs = (int) ((microtime(true) - $start) * 1000);

        // 5. Persist Assistant Response & Citations
        $providerCode = $aiResponse->providerDisclosure['provider'] ?? 'akru_native';
        $provider = AiProvider::where('code', $providerCode)->first();

        $assistantMsgModel = DB::transaction(function () use ($companyId, $conversation, $aiResponse, $provider, $userMsgModel) {
            $msg = AiMessage::create([
                'company_id' => $companyId,
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'message_type' => 'structured',
                'content' => $aiResponse->answer,
                'structured_content_json' => $aiResponse->toArray(),
                'provider_id' => $provider?->id,
                'confidence' => $aiResponse->confidence,
                'risk_level' => $aiResponse->riskLevel,
                'data_as_of' => now(),
                'status' => 'success',
                'parent_message_id' => $userMsgModel->id,
            ]);

            // Save Citations
            foreach ($aiResponse->citations as $cit) {
                AiMessageCitation::create([
                    'company_id' => $companyId,
                    'message_id' => $msg->id,
                    'source_type' => $cit['source_type'] ?? 'general',
                    'label' => $cit['label'] ?? 'Referensi AKRU',
                    'deep_link' => $cit['deep_link'] ?? null,
                    'data_as_of' => now(),
                ]);
            }

            // Save Tool Calls
            foreach ($aiResponse->toolCalls as $tc) {
                AiToolCall::create([
                    'company_id' => $companyId,
                    'message_id' => $msg->id,
                    'tool_code' => $tc['tool'] ?? 'unknown',
                    'status' => $tc['status'] ?? 'success',
                ]);
            }

            $conversation->update(['last_message_at' => now()]);

            return $msg;
        });

        // 6. Record Usage
        $this->usageService->recordEvent($companyId, $userId, $providerCode, $aiResponse->usage, $latencyMs);

        // Re-check quota for response payload
        $updatedQuota = $this->policyService->checkQuota($companyId);

        return [
            'success' => true,
            'reply' => $aiResponse->answer,
            'summary' => $aiResponse->summary,
            'topic' => $aiResponse->intent ?? 'general',
            'conversation_id' => $conversation->id,
            'message_id' => $assistantMsgModel->id,
            'structured_response' => $aiResponse->toArray(),
            'quota' => $updatedQuota,
            'provider' => $aiResponse->providerDisclosure,
            'citations' => $aiResponse->citations,
            'metrics' => $aiResponse->metrics,
            'recommendations' => $aiResponse->recommendations,
        ];
    }
}
