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
