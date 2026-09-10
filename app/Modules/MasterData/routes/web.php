<?php

use App\Modules\MasterData\Http\Controllers\AccountController;
use App\Modules\MasterData\Http\Controllers\BankAccountController;
use App\Modules\MasterData\Http\Controllers\ContactController;
use App\Modules\MasterData\Http\Controllers\ItemController;
use App\Modules\MasterData\Http\Controllers\TaxCodeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    // COA (Chart of Accounts)
    Route::get('/coa', [AccountController::class, 'index'])->name('coa.index');
    Route::post('/coa', [AccountController::class, 'store'])->name('coa.store');
    Route::get('/coa/template', [AccountController::class, 'downloadTemplate'])->name('coa.template');
    Route::post('/coa/import', [AccountController::class, 'import'])->name('coa.import');

    // Contacts: Customers & Suppliers
    Route::get('/customers', [ContactController::class, 'customers'])->name('customers.index');
    Route::get('/customers/template', [ContactController::class, 'downloadCustomerTemplate'])->name('customers.template');
    Route::post('/customers/import', [ContactController::class, 'importCustomers'])->name('customers.import');

    Route::get('/suppliers', [ContactController::class, 'suppliers'])->name('suppliers.index');
    Route::get('/suppliers/template', [ContactController::class, 'downloadSupplierTemplate'])->name('suppliers.template');
    Route::post('/suppliers/import', [ContactController::class, 'importSuppliers'])->name('suppliers.import');

    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');

    // Products / Items
    Route::get('/products', [ItemController::class, 'index'])->name('products.index');
    Route::post('/products', [ItemController::class, 'store'])->name('products.store');
    Route::get('/products/template', [ItemController::class, 'downloadTemplate'])->name('products.template');
    Route::post('/products/import', [ItemController::class, 'import'])->name('products.import');

    // Bank Accounts
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');

    // Tax Codes
    Route::get('/tax-codes', [TaxCodeController::class, 'index'])->name('tax-codes.index');
    Route::post('/tax-codes', [TaxCodeController::class, 'store'])->name('tax-codes.store');

    // Dimensions (Departemen / Proyek / Cost Center)
    Route::get('/dimensions', [\App\Modules\MasterData\Http\Controllers\DimensionController::class, 'index'])->name('dimensions.index');
    Route::post('/dimensions', [\App\Modules\MasterData\Http\Controllers\DimensionController::class, 'store'])->name('dimensions.store');
    Route::post('/dimensions/{id}/values', [\App\Modules\MasterData\Http\Controllers\DimensionController::class, 'storeValue'])->name('dimensions.values.store');
});
