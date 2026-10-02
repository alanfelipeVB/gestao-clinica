<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaRequest;
use App\Models\Sala;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['ativas', 'inativas'])],
        ]);

        $salas = Sala::query()
            ->busca($filtros['busca'] ?? null)
            ->when(($filtros['status'] ?? null) === 'ativas', fn ($q) => $q->where('ativa', true))
            ->when(($filtros['status'] ?? null) === 'inativas', fn ($q) => $q->where('ativa', false))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('admin.salas.index', [
            'salas' => $salas,
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('admin.salas.create', [
            'sala' => new Sala(['cor' => '#0f766e']),
        ]);
    }

    public function store(SalaRequest $request): RedirectResponse
    {
        $sala = Sala::create([...$request->validated(), 'ativa' => true]);

        return redirect()
            ->route('admin.salas.index')
            ->with('sucesso', "{$sala->nome} cadastrada com sucesso.");
    }

    public function edit(Sala $sala): View
    {
        return view('admin.salas.edit', [
            'sala' => $sala,
        ]);
    }

    public function update(SalaRequest $request, Sala $sala): RedirectResponse
    {
        $sala->update($request->validated());

        return redirect()
            ->route('admin.salas.index')
            ->with('sucesso', "{$sala->nome} atualizada.");
    }

    public function alternarStatus(Sala $sala): RedirectResponse
    {
        $sala->update(['ativa' => ! $sala->ativa]);

        $acao = $sala->ativa ? 'ativada' : 'desativada';

        return back()->with('sucesso', "{$sala->nome} foi {$acao}.");
    }
}
