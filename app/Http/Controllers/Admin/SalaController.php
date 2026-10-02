<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaRequest;
use App\Models\Sala;
use App\Services\AgendamentoService;
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

    public function alternarStatus(Request $request, Sala $sala, AgendamentoService $agendamentos): RedirectResponse
    {
        if (! $sala->ativa) {
            $sala->update(['ativa' => true]);

            return back()->with('sucesso', "{$sala->nome} foi ativada.");
        }

        $acao = $request->validate([
            'acao_agendamentos' => ['nullable', Rule::in(['manter', 'cancelar'])],
        ])['acao_agendamentos'] ?? null;

        $futuros = $sala->agendamentos()->agendados()->futuros()->get();

        // Há agendamentos futuros e o admin ainda não decidiu o que fazer com eles.
        if ($futuros->isNotEmpty() && $acao === null) {
            return redirect()->route('admin.salas.desativar', $sala);
        }

        $cancelados = $acao === 'cancelar'
            ? $agendamentos->cancelarEmLote($futuros, $request->user(), 'Sala desativada.')
            : 0;

        $sala->update(['ativa' => false]);

        $mensagem = "{$sala->nome} foi desativada.";
        if ($cancelados > 0) {
            $mensagem .= " {$cancelados} agendamento(s) cancelado(s).";
        } elseif ($futuros->isNotEmpty()) {
            $mensagem .= " Os agendamentos futuros foram mantidos.";
        }

        return redirect()->route('admin.salas.index')->with('sucesso', $mensagem);
    }

    public function confirmarDesativacao(Sala $sala): View|RedirectResponse
    {
        $futuros = $sala->agendamentos()->agendados()->futuros()->with('profissional')->orderBy('inicio')->get();

        if (! $sala->ativa || $futuros->isEmpty()) {
            return redirect()->route('admin.salas.index');
        }

        return view('admin.confirmar-desativacao', [
            'titulo' => 'Desativar sala',
            'nome' => $sala->nome,
            'agendamentos' => $futuros,
            'action' => route('admin.salas.status', $sala),
            'voltar' => route('admin.salas.index'),
            'mostrarProfissional' => true,
        ]);
    }
}
