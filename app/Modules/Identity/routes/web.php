<?php

use App\Modules\Identity\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes
Route::middleware(['web'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Password reset placeholder
    Route::get('/forgot-password', function () {
        return back()->with('info', 'Silakan hubungi administrator sistem untuk reset password akun Anda.');
    })->name('password.request');
});

// Authenticated Team User Management Routes
Route::middleware(['web', 'auth', \App\Http\Middleware\TenantResolver::class])->group(function () {
    Route::resource('users', \App\Modules\Identity\Http\Controllers\UserController::class)->only(['index', 'store', 'destroy']);
});
