<?php

// AKRU Module Routes - Document
// Routes will be defined during Sprint 00 implementation.

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    Route::get('/document/converter', [\App\Modules\Document\Http\Controllers\StatementConverterController::class, 'index'])->name('converter.index');
    Route::post('/document/converter', [\App\Modules\Document\Http\Controllers\StatementConverterController::class, 'convert'])->name('converter.convert');
    Route::post('/document/converter/export-excel', [\App\Modules\Document\Http\Controllers\StatementConverterController::class, 'exportExcel'])->name('converter.export.excel');
    Route::post('/document/converter/export-csv', [\App\Modules\Document\Http\Controllers\StatementConverterController::class, 'exportCsv'])->name('converter.export.csv');
});

