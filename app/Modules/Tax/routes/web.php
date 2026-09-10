<?php

use App\Modules\Tax\Http\Controllers\TaxControlController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant'])->group(function () {
    // PPN Monitoring & SPT Masa 1111
    Route::get('/tax/ppn', [TaxControlController::class, 'ppnSummary'])->name('tax.ppn');

    // PPh Withholding & Bukti Potong Unifikasi
    Route::get('/tax/pph', [TaxControlController::class, 'pphWithholding'])->name('tax.pph');

    // Rekonsiliasi Fiskal (Koreksi Fiskal Positif & Negatif)
    Route::get('/tax/fiscal', [TaxControlController::class, 'fiscalReconciliation'])->name('tax.fiscal');
    Route::post('/tax/fiscal/correction', [TaxControlController::class, 'storeFiscalCorrection'])->name('tax.fiscal.correction');

    // Ekspor Coretax-Ready
    Route::get('/tax/coretax', [TaxControlController::class, 'coretaxExport'])->name('tax.coretax');

    // Kalender Kepatuhan Pajak & Deadline Alert
    Route::get('/tax/calendar', [\App\Modules\Tax\Http\Controllers\TaxCalendarController::class, 'index'])->name('tax.calendar');

    // Tax Audit Package (Kertas Kerja Audit Pajak)
    Route::get('/tax/audit-package', [\App\Modules\Tax\Http\Controllers\TaxAuditPackageController::class, 'index'])->name('tax.audit-package');
});
