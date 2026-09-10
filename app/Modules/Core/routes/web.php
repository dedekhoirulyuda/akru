<?php

/**
 * AKRU Module Routes — Core
 */

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {

    // Dashboard Executive & Operational Control
    Route::get('/dashboard', [\App\Modules\Core\Http\Controllers\DashboardController::class, 'index'])
        ->name('dashboard.index');

    // Company Switcher & Management
    Route::get('/companies', [\App\Modules\Core\Http\Controllers\CompanyController::class, 'index'])
        ->name('companies.index');
    Route::post('/companies', [\App\Modules\Core\Http\Controllers\CompanyController::class, 'store'])
        ->name('companies.store');
    Route::post('/companies/switch/{company}', [\App\Modules\Core\Http\Controllers\CompanyController::class, 'switch'])
        ->name('companies.switch');

    // Onboarding
    Route::get('/onboarding', [\App\Modules\Core\Http\Controllers\OnboardingController::class, 'index'])
        ->name('onboarding.index');
    Route::post('/onboarding/complete', [\App\Modules\Core\Http\Controllers\OnboardingController::class, 'complete'])
        ->name('onboarding.complete');

    // Settings
    Route::get('/settings', [\App\Modules\Core\Http\Controllers\SettingController::class, 'index'])
        ->name('settings.index');
    Route::put('/settings', [\App\Modules\Core\Http\Controllers\SettingController::class, 'update'])
        ->name('settings.update');

    // Print Layout Settings
    Route::get('/settings/print-layout', [\App\Modules\Core\Http\Controllers\PrintSettingController::class, 'index'])
        ->name('print-layout.index');
    Route::post('/settings/print-layout', [\App\Modules\Core\Http\Controllers\PrintSettingController::class, 'store'])
        ->name('print-layout.store');
    Route::put('/settings/print-layout/{id}', [\App\Modules\Core\Http\Controllers\PrintSettingController::class, 'update'])
        ->name('print-layout.update');
    Route::delete('/settings/print-layout/{id}', [\App\Modules\Core\Http\Controllers\PrintSettingController::class, 'destroy'])
        ->name('print-layout.destroy');
    Route::post('/settings/print-layout/{id}/duplicate', [\App\Modules\Core\Http\Controllers\PrintSettingController::class, 'duplicate'])
        ->name('print-layout.duplicate');
    Route::post('/settings/print-layout/{id}/set-default', [\App\Modules\Core\Http\Controllers\PrintSettingController::class, 'setDefault'])
        ->name('print-layout.set-default');
    Route::get('/settings/print-layout/{id}/preview', [\App\Modules\Core\Http\Controllers\PrintSettingController::class, 'preview'])
        ->name('print-layout.preview');
    Route::post('/settings/print-layout/upload-logo', [\App\Modules\Core\Http\Controllers\PrintSettingController::class, 'uploadLogo'])
        ->name('print-layout.upload-logo');

    // Branches & Warehouses
    Route::resource('branches', \App\Modules\Core\Http\Controllers\BranchController::class);

    // AKRU AI Assistant & Automation
    Route::get('/ai/assistant', [\App\Modules\Core\Http\Controllers\AiAssistantController::class, 'index'])->name('ai.index');
    Route::get('/ai/suggest-category', [\App\Modules\Core\Http\Controllers\AiAssistantController::class, 'suggestCategory'])->name('ai.suggest-category');
    Route::post('/ai/chat', [\App\Modules\Core\Http\Controllers\AiAssistantController::class, 'chat'])->name('ai.chat');
});
