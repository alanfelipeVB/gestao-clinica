<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

/*
|--------------------------------------------------------------------------
| Visitantes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Usuários autenticados (admin e profissional)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Somente administrador
    |----------------------------------------------------------------------
    */
    Route::middleware('perfil:admin')->prefix('admin')->name('admin.')->group(function () {
        //
    });
});
