<?php

namespace App\Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\Models\AiSuggestion;
use App\Modules\Ai\Services\AiSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiSuggestionController extends Controller
{
    public function __construct(
        protected AiSuggestionService $suggestionService
    ) {}

    /**
     * Review a suggestion (accept, edit, or reject).
     */
    public function review(Request $request, int $id): JsonResponse
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;
        $userId = auth()->id() ?? 1;

        $decision = $request->input('decision'); // accept, edit, reject
        $editedPayload = $request->input('edited_payload');
        $comment = $request->input('comment');

        $review = $this->suggestionService->review($companyId, $userId, $id, $decision, $editedPayload, $comment);

        return response()->json([
            'success' => true,
            'message' => "Usulan berhasil di-{$decision}.",
            'review' => $review,
        ]);
    }

    /**
     * Convert an approved suggestion into an official DRAFT in the corresponding module.
     * Guaranteed never to auto-post.
     */
    public function convertToDraft(Request $request, int $id): JsonResponse
    {
        $companyId = session('active_company_id', session('current_company_id')) ?? auth()->user()->current_company_id ?? 1;
        $userId = auth()->id() ?? 1;

        $result = $this->suggestionService->convertToDraft($companyId, $userId, $id);

        return response()->json($result);
    }
}
