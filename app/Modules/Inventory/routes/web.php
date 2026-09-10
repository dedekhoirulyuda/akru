<?php

use App\Modules\Inventory\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant'])->group(function () {
    Route::get('/inventory', [StockController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/export/excel', [StockController::class, 'exportExcel'])->name('inventory.export.excel');
    Route::get('/inventory/export/pdf', [StockController::class, 'exportPdf'])->name('inventory.export.pdf');
    Route::post('/inventory/adjustment', [StockController::class, 'adjustment'])->name('inventory.adjustment');

    // Transfer Antar-Gudang
    Route::get('/stock-transfers', [\App\Modules\Inventory\Http\Controllers\StockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::get('/stock-transfers/create', [\App\Modules\Inventory\Http\Controllers\StockTransferController::class, 'create'])->name('stock-transfers.create');
    Route::post('/stock-transfers', [\App\Modules\Inventory\Http\Controllers\StockTransferController::class, 'store'])->name('stock-transfers.store');
    Route::post('/stock-transfers/{id}/receive', [\App\Modules\Inventory\Http\Controllers\StockTransferController::class, 'receive'])->name('stock-transfers.receive');

    // Stock Opname
    Route::get('/stock-opnames', [\App\Modules\Inventory\Http\Controllers\StockOpnameController::class, 'index'])->name('stock-opnames.index');
    Route::get('/stock-opnames/export/excel', [\App\Modules\Inventory\Http\Controllers\StockOpnameController::class, 'exportExcel'])->name('stock-opnames.export.excel');
    Route::get('/stock-opnames/export/pdf', [\App\Modules\Inventory\Http\Controllers\StockOpnameController::class, 'exportPdf'])->name('stock-opnames.export.pdf');
    Route::get('/stock-opnames/create', [\App\Modules\Inventory\Http\Controllers\StockOpnameController::class, 'create'])->name('stock-opnames.create');
    Route::post('/stock-opnames', [\App\Modules\Inventory\Http\Controllers\StockOpnameController::class, 'store'])->name('stock-opnames.store');
    Route::post('/stock-opnames/{id}/approve', [\App\Modules\Inventory\Http\Controllers\StockOpnameController::class, 'approve'])->name('stock-opnames.approve');
});
