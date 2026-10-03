<?php

use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\PaginaInicialController;
use App\Http\Controllers\Admin\ProfissionalController;
use App\Http\Controllers\Admin\SalaController as AdminSalaController;
use App\Http\Controllers\Admin\TutorialController as AdminTutorialController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\SalaController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TutorialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Página pública da clínica
|--------------------------------------------------------------------------
*/
Route::get('/', [SiteController::class, 'inicio'])->name('inicio');
Route::get('/marca/logo', [SiteController::class, 'logo'])->name('site.logo');
Route::get('/profissionais/{user}/foto', [SiteController::class, 'foto'])->name('profissionais.foto');

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

    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/senha', [PerfilController::class, 'atualizarSenha'])->name('perfil.senha');

    Route::get('/salas', [SalaController::class, 'index'])->name('salas.index');

    Route::get('/tutoriais', [TutorialController::class, 'index'])->name('tutoriais.index');
    Route::get('/tutoriais/{tutorial}', [TutorialController::class, 'show'])->name('tutoriais.show');
    Route::get('/tutoriais/{tutorial}/video', [TutorialController::class, 'video'])->name('tutoriais.video');
    Route::post('/tutoriais/{tutorial}/assistido', [TutorialController::class, 'marcarAssistido'])->name('tutoriais.assistido');

    Route::get('/relatorio', [RelatorioController::class, 'index'])->name('relatorio.index');
    Route::get('/relatorio/exportar', [RelatorioController::class, 'exportar'])->name('relatorio.exportar');

    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda');
    Route::get('/agenda/eventos', [AgendaController::class, 'eventos'])->name('agenda.eventos');

    Route::resource('agendamentos', AgendamentoController::class)->except(['destroy']);

    Route::patch('agendamentos/{agendamento}/cancelar', [AgendamentoController::class, 'cancelar'])
        ->name('agendamentos.cancelar');

    Route::patch('agendamentos/{agendamento}/atendimento', [AgendamentoController::class, 'registrarAtendimento'])
        ->name('agendamentos.atendimento');

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
        Route::get('profissionais/{profissional}/desativar', [ProfissionalController::class, 'confirmarDesativacao'])
            ->name('profissionais.desativar');

        Route::resource('salas', AdminSalaController::class)->except(['show', 'destroy']);

        Route::patch('salas/{sala}/status', [AdminSalaController::class, 'alternarStatus'])
            ->name('salas.status');
        Route::get('salas/{sala}/desativar', [AdminSalaController::class, 'confirmarDesativacao'])
            ->name('salas.desativar');

        Route::resource('tutoriais', AdminTutorialController::class)
            ->parameters(['tutoriais' => 'tutorial']);
        Route::patch('tutoriais/{tutorial}/publicacao', [AdminTutorialController::class, 'alternarPublicacao'])
            ->name('tutoriais.publicacao');

        Route::get('pagina-inicial', [PaginaInicialController::class, 'edit'])->name('pagina-inicial.edit');
        Route::put('pagina-inicial', [PaginaInicialController::class, 'update'])->name('pagina-inicial.update');

        Route::get('configuracoes', [ConfiguracaoController::class, 'edit'])->name('configuracoes.edit');
        Route::put('configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');
    });
});
