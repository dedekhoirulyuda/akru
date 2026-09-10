<?php

use App\Modules\Platform\Http\Controllers\PlatformApiController;
use App\Modules\Platform\Http\Controllers\PlatformCompanyController;
use App\Modules\Platform\Http\Controllers\PlatformDashboardController;
use App\Modules\Platform\Http\Controllers\PlatformModuleController;
use App\Modules\Platform\Http\Controllers\PlatformPlanController;
use App\Modules\Platform\Http\Controllers\PlatformSubscriptionController;
use App\Modules\Platform\Http\Controllers\PlatformUserController;
use Illuminate\Support\Facades\Route;

// Impersonation stop route (available to any authenticated user who has an impersonated session)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/platform/impersonate/stop', [PlatformUserController::class, 'stopImpersonate'])
        ->name('platform.impersonate.stop');
});

// Platform Owner (Super Admin) Protected Routes
Route::middleware(['web', 'auth', 'superadmin'])->prefix('platform')->name('platform.')->group(function () {
    // 1. Dashboard
    Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');

    // 2. Tenants / Companies
    Route::get('/companies', [PlatformCompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies', [PlatformCompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{id}', [PlatformCompanyController::class, 'show'])->name('companies.show');
    Route::patch('/companies/{id}/status', [PlatformCompanyController::class, 'updateStatus'])->name('companies.status');
    Route::patch('/companies/{id}/quota', [PlatformCompanyController::class, 'updateQuota'])->name('companies.quota');
    Route::delete('/companies/{id}', [PlatformCompanyController::class, 'destroy'])->name('companies.destroy');

    // 3. Users & Impersonation
    Route::get('/users', [PlatformUserController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/superadmin', [PlatformUserController::class, 'toggleSuperAdmin'])->name('users.toggle-superadmin');
    Route::post('/users/{id}/reset-password', [PlatformUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{id}/impersonate', [PlatformUserController::class, 'impersonate'])->name('users.impersonate');

    // 4. Plans & Pricing
    Route::get('/plans', [PlatformPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [PlatformPlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{id}', [PlatformPlanController::class, 'update'])->name('plans.update');
    Route::post('/plans/{id}/toggle', [PlatformPlanController::class, 'toggleActive'])->name('plans.toggle');
    Route::delete('/plans/{id}', [PlatformPlanController::class, 'destroy'])->name('plans.destroy');

    // 5. Subscriptions & Invoices
    Route::get('/subscriptions', [PlatformSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/{id}/extend', [PlatformSubscriptionController::class, 'extend'])->name('subscriptions.extend');
    Route::post('/subscriptions/{id}/plan', [PlatformSubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
    Route::post('/invoices', [PlatformSubscriptionController::class, 'createInvoice'])->name('invoices.store');
    Route::post('/invoices/{id}/pay', [PlatformSubscriptionController::class, 'markInvoicePaid'])->name('invoices.pay');

    // 6. Modules & Feature Flags
    Route::get('/modules', [PlatformModuleController::class, 'index'])->name('modules.index');
    Route::post('/modules/plan/{id}', [PlatformModuleController::class, 'updatePlanModules'])->name('modules.plan');
    Route::post('/modules/company/{id}', [PlatformModuleController::class, 'updateCompanyModules'])->name('modules.company');
    Route::post('/modules/toggle/{key}', [PlatformModuleController::class, 'toggleGlobal'])->name('modules.toggle-global');

    // 7. API Integrations & Tokens
    Route::get('/api-integrations', [PlatformApiController::class, 'index'])->name('api.index');
    Route::post('/api-integrations/settings', [PlatformApiController::class, 'updateSettings'])->name('api.settings');
    Route::post('/api-integrations/tokens', [PlatformApiController::class, 'generateToken'])->name('api.tokens.store');
    Route::delete('/api-integrations/tokens/{id}', [PlatformApiController::class, 'revokeToken'])->name('api.tokens.destroy');
});
