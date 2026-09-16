<?php

use App\Modules\Ai\Http\Controllers\AiAnomalyController;
use App\Modules\Ai\Http\Controllers\AiChatController;
use App\Modules\Ai\Http\Controllers\AiDashboardController;
use App\Modules\Ai\Http\Controllers\AiSuggestionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant'])->group(function () {
    // AKRU AI Hub
    Route::get('/ai', [AiDashboardController::class, 'index'])->name('ai.dashboard');
    Route::get('/ai/assistant', [AiDashboardController::class, 'index'])->name('ai.index');

    // Chatbot & Conversations
    Route::post('/ai/chat', [AiChatController::class, 'chat'])->name('ai.chat');
    Route::get('/ai/conversations', [AiChatController::class, 'getConversations'])->name('ai.conversations');
    Route::get('/ai/conversations/{id}', [AiChatController::class, 'getConversationMessages'])->name('ai.conversations.messages');
    Route::get('/ai/suggest-category', [AiChatController::class, 'suggestCategory'])->name('ai.suggest-category');

    // Anomaly Center
    Route::post('/ai/anomalies/scan', [AiAnomalyController::class, 'scan'])->name('ai.anomalies.scan');
    Route::post('/ai/anomalies/{id}/resolve', [AiAnomalyController::class, 'resolve'])->name('ai.anomalies.resolve');

    // Suggestions & Human Review
    Route::post('/ai/suggestions/{id}/review', [AiSuggestionController::class, 'review'])->name('ai.suggestions.review');
    Route::post('/ai/suggestions/{id}/convert-to-draft', [AiSuggestionController::class, 'convertToDraft'])->name('ai.suggestions.convert-to-draft');
});
