<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerfilUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfissionalRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfissionalController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['ativos', 'inativos'])],
            'perfil' => ['nullable', Rule::enum(PerfilUsuario::class)],
        ]);

        $profissionais = User::query()
            ->busca($filtros['busca'] ?? null)
            ->when(($filtros['status'] ?? null) === 'ativos', fn ($q) => $q->where('ativo', true))
            ->when(($filtros['status'] ?? null) === 'inativos', fn ($q) => $q->where('ativo', false))
            ->when($filtros['perfil'] ?? null, fn ($q, $perfil) => $q->where('perfil', $perfil))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('admin.profissionais.index', [
            'profissionais' => $profissionais,
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('admin.profissionais.create', [
            'profissional' => new User(['perfil' => PerfilUsuario::Profissional]),
        ]);
    }

    public function store(ProfissionalRequest $request): RedirectResponse
    {
        $profissional = User::create([...$request->dados(), 'ativo' => true]);

        return redirect()
            ->route('admin.profissionais.index')
            ->with('sucesso', "Profissional {$profissional->nome} cadastrado com sucesso.");
    }

    public function edit(User $profissional): View
    {
        return view('admin.profissionais.edit', [
            'profissional' => $profissional,
        ]);
    }

    public function update(ProfissionalRequest $request, User $profissional): RedirectResponse
    {
        $profissional->update($request->dados());

        $mensagem = $request->filled('password')
            ? "Dados de {$profissional->nome} atualizados e senha redefinida."
            : "Dados de {$profissional->nome} atualizados.";

        return redirect()->route('admin.profissionais.index')->with('sucesso', $mensagem);
    }

    public function alternarStatus(Request $request, User $profissional): RedirectResponse
    {
        if ($profissional->is($request->user())) {
            return back()->with('erro', 'Você não pode desativar a sua própria conta.');
        }

        $profissional->update(['ativo' => ! $profissional->ativo]);

        $acao = $profissional->ativo ? 'ativado' : 'desativado';

        return back()->with('sucesso', "{$profissional->nome} foi {$acao}.");
    }
}
