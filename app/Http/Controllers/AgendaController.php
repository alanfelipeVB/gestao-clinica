<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        return view('agenda.index', [
            'salas' => Sala::ativas()->orderBy('nome')->get(['id', 'nome', 'cor']),
            'profissionais' => User::ativos()->orderBy('nome')->get(['id', 'nome']),
            'filtros' => $request->only(['sala_id', 'user_id', 'data']),
        ]);
    }

    /**
     * Eventos no formato do FullCalendar para o período visível.
     * Para o profissional, agendamentos de colegas aparecem como "Ocupado", sem descrição.
     */
    public function eventos(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'sala_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $fuso = config('app.timezone');
        $inicio = Carbon::parse($filtros['start'])->setTimezone($fuso);
        $fim = Carbon::parse($filtros['end'])->setTimezone($fuso);

        // Evita consultas muito grandes (a visão mensal ocupa no máximo ~6 semanas).
        abort_if($inicio->diffInDays($fim) > 62, 422, 'Período muito longo.');

        $user = $request->user();

        $eventos = Agendamento::query()
            ->agendados()
            ->with(['sala:id,nome,cor', 'profissional:id,nome'])
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->when($filtros['sala_id'] ?? null, fn ($q, $salaId) => $q->where('sala_id', $salaId))
            ->when($filtros['user_id'] ?? null, fn ($q, $userId) => $q->where('user_id', $userId))
            ->orderBy('inicio')
            ->get()
            ->map(fn (Agendamento $agendamento) => $this->paraEvento($agendamento, $user));

        return response()->json($eventos);
    }

    /**
     * @return array<string, mixed>
     */
    private function paraEvento(Agendamento $agendamento, User $user): array
    {
        $podeVerDetalhes = $user->can('view', $agendamento);

        return [
            'id' => $agendamento->id,
            'title' => $podeVerDetalhes
                ? $agendamento->profissional->nome
                : 'Ocupado — '.$agendamento->profissional->nome,
            'start' => $agendamento->inicio->format('Y-m-d\TH:i:s'),
            'end' => $agendamento->fim->format('Y-m-d\TH:i:s'),
            'backgroundColor' => $agendamento->sala->cor,
            'borderColor' => $agendamento->sala->cor,
            'classNames' => $podeVerDetalhes ? [] : ['evento-ocupado'],
            'extendedProps' => [
                'sala' => $agendamento->sala->nome,
                'profissional' => $agendamento->profissional->nome,
                'data' => $agendamento->inicio->translatedFormat('l, d/m/Y'),
                'horario' => $agendamento->horario(),
                'descricao' => $podeVerDetalhes ? $agendamento->descricao : null,
                'resumo' => $podeVerDetalhes ? Str::limit($agendamento->descricao, 60) : null,
                'url_detalhes' => $podeVerDetalhes ? route('agendamentos.show', $agendamento) : null,
                'url_editar' => $user->can('update', $agendamento) ? route('agendamentos.edit', $agendamento) : null,
                'url_cancelar' => $user->can('cancel', $agendamento) ? route('agendamentos.cancelar', $agendamento) : null,
            ],
        ];
    }
}
