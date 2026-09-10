<?php

use App\Modules\Workflow\Http\Controllers\WorkQueueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant'])->group(function () {
    Route::get('/workflow', [WorkQueueController::class, 'index'])->name('workflow.index');
    Route::post('/workflow/{approvalRequest}/approve', [WorkQueueController::class, 'approve'])->name('workflow.approve');
    Route::post('/workflow/{approvalRequest}/reject', [WorkQueueController::class, 'reject'])->name('workflow.reject');
});
