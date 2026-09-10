<?php

use App\Modules\Accounting\Http\Controllers\GeneralLedgerController;
use App\Modules\Accounting\Http\Controllers\JournalController;
use App\Modules\Accounting\Http\Controllers\ManualJournalController;
use App\Modules\Accounting\Http\Controllers\ReportController;
use App\Modules\Accounting\Http\Controllers\TrialBalanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant'])->group(function () {
    // Jurnal Umum & Explorer
    Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('/journals/export/excel', [JournalController::class, 'exportExcel'])->name('journals.export.excel');
    Route::get('/journals/export/pdf', [JournalController::class, 'exportPdf'])->name('journals.export.pdf');
    Route::get('/journals/{journal}', [JournalController::class, 'show'])->name('journals.show');
    Route::post('/journals/{journal}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse');

    // Jurnal Manual / Penyesuaian
    Route::get('/manual-journals/create', [ManualJournalController::class, 'create'])->name('manual-journals.create');
    Route::post('/manual-journals', [ManualJournalController::class, 'store'])->name('manual-journals.store');

    // Buku Besar
    Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])->name('ledger.index');
    Route::get('/general-ledger/export/excel', [GeneralLedgerController::class, 'exportExcel'])->name('ledger.export.excel');
    Route::get('/general-ledger/export/pdf', [GeneralLedgerController::class, 'exportPdf'])->name('ledger.export.pdf');

    // Neraca Saldo
    Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance.index');
    Route::get('/trial-balance/export/excel', [TrialBalanceController::class, 'exportExcel'])->name('trial-balance.export.excel');
    Route::get('/trial-balance/export/pdf', [TrialBalanceController::class, 'exportPdf'])->name('trial-balance.export.pdf');

    // Laporan Keuangan Standar
    Route::get('/reports/profit-loss', [ReportController::class, 'profitAndLoss'])->name('reports.profit-loss');
    Route::get('/reports/profit-loss/export/excel', [ReportController::class, 'exportProfitLossExcel'])->name('reports.profit-loss.excel');
    Route::get('/reports/profit-loss/export/pdf', [ReportController::class, 'exportProfitLossPdf'])->name('reports.profit-loss.pdf');

    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('/reports/balance-sheet/export/excel', [ReportController::class, 'exportBalanceSheetExcel'])->name('reports.balance-sheet.excel');
    Route::get('/reports/balance-sheet/export/pdf', [ReportController::class, 'exportBalanceSheetPdf'])->name('reports.balance-sheet.pdf');

    Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');
    Route::get('/reports/cash-flow/export/excel', [ReportController::class, 'exportCashFlowExcel'])->name('reports.cash-flow.excel');
    Route::get('/reports/cash-flow/export/pdf', [ReportController::class, 'exportCashFlowPdf'])->name('reports.cash-flow.pdf');

    Route::get('/reports/aging', [ReportController::class, 'aging'])->name('reports.aging');
    Route::get('/reports/aging/export/excel', [ReportController::class, 'exportAgingExcel'])->name('reports.aging.excel');
    Route::get('/reports/aging/export/pdf', [ReportController::class, 'exportAgingPdf'])->name('reports.aging.pdf');

    // Buku Register Aset Tetap & Penyusutan
    Route::get('/assets', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'index'])->name('assets.index');
    Route::post('/assets', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'store'])->name('assets.store');
    Route::post('/assets/{asset}/depreciate', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'depreciate'])->name('assets.depreciate');

    // Closing Workbench & Penutupan Buku Periode
    Route::get('/closing', [\App\Modules\Accounting\Http\Controllers\ClosingController::class, 'index'])->name('closing.index');
    Route::post('/closing/{period}/close', [\App\Modules\Accounting\Http\Controllers\ClosingController::class, 'close'])->name('closing.close');
    Route::post('/closing/{period}/reopen', [\App\Modules\Accounting\Http\Controllers\ClosingController::class, 'reopen'])->name('closing.reopen');

    // Accruals & Prepaid Expense Schedules
    Route::get('/accruals', [\App\Modules\Accounting\Http\Controllers\AccrualController::class, 'index'])->name('accruals.index');
    Route::post('/accruals', [\App\Modules\Accounting\Http\Controllers\AccrualController::class, 'store'])->name('accruals.store');
    Route::post('/accruals/{id}/lines/{lineId}/process', [\App\Modules\Accounting\Http\Controllers\AccrualController::class, 'processPeriod'])->name('accruals.process');
});
