<?php

/**
 * AKRU API Routes — v1
 *
 * Blueprint §12: Endpoint /api/v1/..., versioned, idempotency key,
 * consistent pagination and error structure.
 */

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api'])->group(function () {

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'app' => config('akru.name'),
            'version' => config('akru.version'),
            'timestamp' => now()->toIso8601String(),
        ]);
    })->name('api.health');

    // Sync endpoint for offline queue
    Route::post('/sync', function () {
        // Placeholder — handled by SyncEngine
        return response()->json(['status' => 'received'], 202);
    })->middleware('auth:sanctum')->name('api.sync');

});
