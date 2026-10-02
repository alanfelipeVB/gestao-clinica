<?php

use Illuminate\Support\Facades\Route;

// Provisório: prévia do layout. Será substituído pelo login/dashboard na etapa de autenticação.
Route::view('/', 'inicio')->name('inicio');
