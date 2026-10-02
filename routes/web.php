<?php

use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\ProfissionalController;
use App\Http\Controllers\Admin\SalaController as AdminSalaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalaController;
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

    Route::get('/salas', [SalaController::class, 'index'])->name('salas.index');

    /*
    |----------------------------------------------------------------------
    | Somente administrador
    |----------------------------------------------------------------------
    */
    Route::middleware('perfil:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('profissionais', ProfissionalController::class)
            ->except(['show', 'destroy'])
            ->parameters(['profissionais' => 'profissional']);

        Route::patch('profissionais/{profissional}/status', [ProfissionalController::class, 'alternarStatus'])
            ->name('profissionais.status');

        Route::resource('salas', AdminSalaController::class)->except(['show', 'destroy']);

        Route::patch('salas/{sala}/status', [AdminSalaController::class, 'alternarStatus'])
            ->name('salas.status');

        Route::get('configuracoes', [ConfiguracaoController::class, 'edit'])->name('configuracoes.edit');
        Route::put('configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');
    });
});
