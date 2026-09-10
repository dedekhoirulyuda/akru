<?php

// AKRU Module Routes - Partner
// Routes will be defined during Sprint 00 implementation.

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    Route::get('/partner/workspace', [\App\Modules\Partner\Http\Controllers\PartnerController::class, 'index'])->name('partner.workspace');
});

