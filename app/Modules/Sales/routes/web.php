<?php

use App\Modules\Sales\Http\Controllers\CustomerReceiptController;
use App\Modules\Sales\Http\Controllers\SalesInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    // Sales Invoices
    Route::get('/sales', [SalesInvoiceController::class, 'index'])->name('sales.index');
    Route::get('/sales/export/excel', [SalesInvoiceController::class, 'exportExcel'])->name('sales.export.excel');
    Route::get('/sales/export/pdf', [SalesInvoiceController::class, 'exportPdf'])->name('sales.export.pdf');
    Route::get('/sales/create', [SalesInvoiceController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SalesInvoiceController::class, 'store'])->name('sales.store');
    Route::get('/sales/{invoice}', [SalesInvoiceController::class, 'show'])->name('sales.show');
    Route::post('/sales/{invoice}/post', [SalesInvoiceController::class, 'post'])->name('sales.post');
    Route::get('/sales/{invoice}/print', [SalesInvoiceController::class, 'print'])->name('sales.print');

    // Customer Receipts
    Route::get('/receipts', [CustomerReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/export/excel', [CustomerReceiptController::class, 'exportExcel'])->name('receipts.export.excel');
    Route::get('/receipts/export/pdf', [CustomerReceiptController::class, 'exportPdf'])->name('receipts.export.pdf');
    Route::get('/receipts/create', [CustomerReceiptController::class, 'create'])->name('receipts.create');
    Route::post('/receipts', [CustomerReceiptController::class, 'store'])->name('receipts.store');

    // Quotations (Penawaran Harga)
    Route::get('/quotations', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'index'])->name('quotations.index');
    Route::get('/quotations/create', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'create'])->name('quotations.create');
    Route::post('/quotations', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'store'])->name('quotations.store');
    Route::post('/quotations/{id}/convert', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'convertToOrder'])->name('quotations.convert');

    // Sales Orders (Pesanan Penjualan)
    Route::get('/sales-orders', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('/sales-orders/create', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'create'])->name('sales-orders.create');
    Route::post('/sales-orders', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'store'])->name('sales-orders.store');

    // Delivery Orders (Surat Jalan Pengiriman Barang)
    Route::get('/deliveries', [\App\Modules\Sales\Http\Controllers\DeliveryOrderController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/create', [\App\Modules\Sales\Http\Controllers\DeliveryOrderController::class, 'create'])->name('deliveries.create');
    Route::post('/deliveries', [\App\Modules\Sales\Http\Controllers\DeliveryOrderController::class, 'store'])->name('deliveries.store');
});
