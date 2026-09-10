<?php

use App\Modules\Finance\Http\Controllers\BankReconciliationController;
use App\Modules\Finance\Http\Controllers\BankTransferController;
use App\Modules\Finance\Http\Controllers\CashTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    // Cash & Bank Transactions (BKM / BKK)
    Route::get('/finance/cash-bank', [CashTransactionController::class, 'index'])->name('cash-bank.index');
    Route::get('/finance/cash-bank/export/excel', [CashTransactionController::class, 'exportExcel'])->name('cash-bank.export.excel');
    Route::get('/finance/cash-bank/export/pdf', [CashTransactionController::class, 'exportPdf'])->name('cash-bank.export.pdf');
    Route::get('/finance/cash-bank/create', [CashTransactionController::class, 'create'])->name('cash-bank.create');
    Route::post('/finance/cash-bank', [CashTransactionController::class, 'store'])->name('cash-bank.store');

    // Bank Transfers
    Route::get('/finance/transfers', [BankTransferController::class, 'index'])->name('transfers.index');
    Route::get('/finance/transfers/create', [BankTransferController::class, 'create'])->name('transfers.create');
    Route::post('/finance/transfers', [BankTransferController::class, 'store'])->name('transfers.store');

    // Bank Reconciliation
    Route::get('/finance/reconciliation', [BankReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::get('/finance/reconciliation/export/excel', [BankReconciliationController::class, 'exportExcel'])->name('reconciliation.export.excel');
    Route::get('/finance/reconciliation/export/pdf', [BankReconciliationController::class, 'exportPdf'])->name('reconciliation.export.pdf');

    // Petty Cash (Kas Kecil Imprest)
    Route::get('/finance/petty-cash', [\App\Modules\Finance\Http\Controllers\PettyCashController::class, 'index'])->name('petty-cash.index');
    Route::post('/finance/petty-cash/funds', [\App\Modules\Finance\Http\Controllers\PettyCashController::class, 'storeFund'])->name('petty-cash.funds.store');
    Route::post('/finance/petty-cash/vouchers', [\App\Modules\Finance\Http\Controllers\PettyCashController::class, 'storeVoucher'])->name('petty-cash.vouchers.store');
    Route::post('/finance/petty-cash/{id}/replenish', [\App\Modules\Finance\Http\Controllers\PettyCashController::class, 'replenish'])->name('petty-cash.replenish');

    // Budgets (Anggaran Biaya)
    Route::get('/finance/budgets', [\App\Modules\Finance\Http\Controllers\BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/finance/budgets', [\App\Modules\Finance\Http\Controllers\BudgetController::class, 'store'])->name('budgets.store');

    // Cash-Flow Forecast (Proyeksi Arus Kas)
    Route::get('/finance/forecast', [\App\Modules\Finance\Http\Controllers\CashFlowForecastController::class, 'index'])->name('forecast.index');
});
