<?php

use App\Modules\Purchase\Http\Controllers\PurchaseInvoiceController;
use App\Modules\Purchase\Http\Controllers\SupplierPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    // Purchase Invoices (Bills)
    Route::get('/purchases', [PurchaseInvoiceController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/export/excel', [PurchaseInvoiceController::class, 'exportExcel'])->name('purchases.export.excel');
    Route::get('/purchases/export/pdf', [PurchaseInvoiceController::class, 'exportPdf'])->name('purchases.export.pdf');
    Route::get('/purchases/create', [PurchaseInvoiceController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseInvoiceController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{invoice}', [PurchaseInvoiceController::class, 'show'])->name('purchases.show');
    Route::post('/purchases/{invoice}/post', [PurchaseInvoiceController::class, 'post'])->name('purchases.post');

    // Supplier Payments
    Route::get('/payments', [SupplierPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/export/excel', [SupplierPaymentController::class, 'exportExcel'])->name('payments.export.excel');
    Route::get('/payments/export/pdf', [SupplierPaymentController::class, 'exportPdf'])->name('payments.export.pdf');
    Route::get('/payments/create', [SupplierPaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [SupplierPaymentController::class, 'store'])->name('payments.store');

    // Purchase Requests (Permintaan Pembelian)
    Route::get('/purchase-requests', [\App\Modules\Purchase\Http\Controllers\PurchaseRequestController::class, 'index'])->name('purchase-requests.index');
    Route::get('/purchase-requests/create', [\App\Modules\Purchase\Http\Controllers\PurchaseRequestController::class, 'create'])->name('purchase-requests.create');
    Route::post('/purchase-requests', [\App\Modules\Purchase\Http\Controllers\PurchaseRequestController::class, 'store'])->name('purchase-requests.store');
    Route::post('/purchase-requests/{id}/approve', [\App\Modules\Purchase\Http\Controllers\PurchaseRequestController::class, 'approve'])->name('purchase-requests.approve');

    // Purchase Orders (Pesanan Pembelian)
    Route::get('/purchase-orders', [\App\Modules\Purchase\Http\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/create', [\App\Modules\Purchase\Http\Controllers\PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('/purchase-orders', [\App\Modules\Purchase\Http\Controllers\PurchaseOrderController::class, 'store'])->name('purchase-orders.store');

    // Goods Receipts (Penerimaan Barang Fisik di Gudang)
    Route::get('/goods-receipts', [\App\Modules\Purchase\Http\Controllers\GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::get('/goods-receipts/create', [\App\Modules\Purchase\Http\Controllers\GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
    Route::post('/goods-receipts', [\App\Modules\Purchase\Http\Controllers\GoodsReceiptController::class, 'store'])->name('goods-receipts.store');
});
