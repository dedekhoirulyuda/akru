<?php

// AKRU Module Routes - Reporting
// Routes will be defined during Sprint 00 implementation.

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    Route::get('/reports/analytics', [\App\Modules\Reporting\Http\Controllers\AnalyticsController::class, 'salesPurchaseAnalytics'])->name('reports.analytics');
});

