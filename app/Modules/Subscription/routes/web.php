<?php

// AKRU Module Routes - Subscription
// Routes will be defined during Sprint 00 implementation.

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    Route::get('/subscription', [\App\Modules\Subscription\Http\Controllers\SubscriptionController::class, 'index'])
        ->name('subscription.index');
    Route::post('/subscription/plan', [\App\Modules\Subscription\Http\Controllers\SubscriptionController::class, 'changePlan'])
        ->name('subscription.change-plan');
});

