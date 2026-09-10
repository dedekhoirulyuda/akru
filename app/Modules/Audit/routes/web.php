<?php

use App\Modules\Audit\Http\Controllers\AuditExplorerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant'])->group(function () {
    Route::get('/audit', [AuditExplorerController::class, 'index'])->name('audit.index');
});
