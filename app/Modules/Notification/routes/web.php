<?php

// AKRU Module Routes - Notification
// Routes will be defined during Sprint 00 implementation.

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    Route::get('/notifications', [\App\Modules\Notification\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [\App\Modules\Notification\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
});

