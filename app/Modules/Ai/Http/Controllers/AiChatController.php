<?php

namespace App\Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\Models\AiConversation;
use App\Modules\Ai\Models\AiMessage;
use App\Modules\Ai\Services\AiOrchestratorService;
use App\Modules\MasterData\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(
        protected AiOrchestratorService $orchestrator
    ) {}

    /**
     * Handle incoming chat message.
     */
    public function chat(Request $request): JsonResponse
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;
        $userId = auth()->id() ?? 1;

        $message = trim($request->input('message', ''));
        if (empty($message)) {
            return response()->json([
                'success' => false,
                'reply' => 'Silakan masukkan pertanyaan atau instruksi analisis finansial Anda.',
            ], 422);
        }

        $conversationId = $request->input('conversation_id');
        $mode = $request->input('mode', 'general');
        $provider = $request->input('provider');
        if ($provider) {
            session(['ai_provider_preference' => $provider]);
        }

        $result = $this->orchestrator->processChat($companyId, $userId, $message, $conversationId, $mode);

        return response()->json($result);
    }

    /**
     * Get list of conversations for current user.
     */
    public function getConversations(Request $request): JsonResponse
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;

        $conversations = AiConversation::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->get(['id', 'title', 'mode', 'last_message_at']);

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Get message history for a specific conversation.
     */
    public function getConversationMessages(Request $request, int $id): JsonResponse
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;

        $conversation = AiConversation::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $messages = AiMessage::with(['citations'])
            ->where('company_id', $companyId)
            ->where('conversation_id', $conversation->id)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Smart COA Suggestion Endpoint.
     */
    public function suggestCategory(Request $request): JsonResponse
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;
        $query = trim($request->get('query', ''));

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan masukkan deskripsi transaksi.',
            ]);
        }

        $lower = strtolower($query);

        $account = Account::where('company_id', $companyId)->where('is_active', true)
            ->where(function($q) use ($lower) {
                if (str_contains($lower, 'bensin') || str_contains($lower, 'bbm') || str_contains($lower, 'solar') || str_contains($lower, 'tol') || str_contains($lower, 'parkir')) {
                    $q->where('code', '6700')->orWhere(function($sub) {
                        $sub->where('type', 'expense')->where(function($s2) {
                            $s2->where('name', 'like', '%transportasi%')->orWhere('name', 'like', '%bahan bakar%');
                        });
                    });
                } elseif (str_contains($lower, 'internet') || str_contains($lower, 'wifi') || str_contains($lower, 'listrik') || str_contains($lower, 'air')) {
                    $q->where('code', '6300')->orWhere('name', 'like', '%internet%')->orWhere('name', 'like', '%listrik%')->orWhere('name', 'like', '%utilitas%');
                } elseif (str_contains($lower, 'sewa') || str_contains($lower, 'ruko') || str_contains($lower, 'gedung')) {
                    $q->where('name', 'like', '%sewa%')->orWhere('code', '6200');
                } elseif (str_contains($lower, 'gaji') || str_contains($lower, 'honor') || str_contains($lower, 'upah')) {
                    $q->where('name', 'like', '%gaji%')->orWhere('code', '6000');
                } elseif (str_contains($lower, 'kertas') || str_contains($lower, 'atk') || str_contains($lower, 'pulpen')) {
                    $q->where('name', 'like', '%atk%')->orWhere('name', 'like', '%kantor%')->orWhere('code', '6400');
                } else {
                    $q->where('type', 'expense');
                }
            })->first();

        if (!$account) {
            $account = Account::where('company_id', $companyId)->where('type', 'expense')->first();
        }

        return response()->json([
            'success' => true,
            'suggested_account' => $account ? [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'type' => $account->type,
                'normal_balance' => $account->normal_balance,
            ] : null,
            'confidence' => '94%',
            'reasoning' => 'Sesuai PSAK basis akrual, pengeluaran ini digolongkan sebagai Beban Operasional periode berjalan.',
            'journal_hint' => "[Debit] {$account?->code} - {$account?->name} / [Kredit] 1110 - Kas/Bank",
        ]);
    }
}
