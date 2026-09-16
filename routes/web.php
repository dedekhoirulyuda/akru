<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard.index');
    }

    return redirect()->route('login');
});

Route::get('/dashboard-redirect', function () {
    return redirect()->route('dashboard.index');
})->name('dashboard');

Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    Route::get('/regulations', [\App\Http\Controllers\RegulationController::class, 'index'])->name('regulations.index');
    Route::get('/regulations/{id}', [\App\Http\Controllers\RegulationController::class, 'show'])->name('regulations.show');
});

