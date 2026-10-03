<?php

namespace App\Services;

use App\Enums\PerfilUsuario;
use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Consultas dos painéis inicial do administrador e do profissional.
 */
class DashboardService
{
    /**
     * @return array{profissionais: int, salas: int, agendamentos_hoje: int, salas_ocupadas: int}
     */
    public function totais(): array
    {
        return [
            'profissionais' => User::ativos()->where('perfil', PerfilUsuario::Profissional)->count(),
            'salas' => Sala::ativas()->count(),
            'agendamentos_hoje' => Agendamento::agendados()->whereDate('inicio', today())->count(),
            'salas_ocupadas' => Agendamento::agendados()
                ->whereHas('sala', fn ($q) => $q->where('ativa', true))
                ->where('inicio', '<=', now())
                ->where('fim', '>', now())
                ->distinct()
                ->count('sala_id'),
        ];
    }

    /**
     * Situação atual de cada sala ativa: ocupada (agendamento em andamento) ou livre
     * (com o próximo agendamento do dia, se houver).
     *
     * @return \Illuminate\Support\Collection<int, array{sala: Sala, atual: ?Agendamento, proximo: ?Agendamento}>
     */
    public function situacaoDasSalas(): \Illuminate\Support\Collection
    {
        $restantesHoje = Agendamento::agendados()
            ->with('profissional:id,nome')
            ->where('fim', '>', now())
            ->where('inicio', '<', today()->addDay())
            ->orderBy('inicio')
            ->get()
            ->groupBy('sala_id');

        return Sala::ativas()->orderBy('nome')->get()->map(function (Sala $sala) use ($restantesHoje) {
            $agendamentos = $restantesHoje->get($sala->id, collect());

            return [
                'sala' => $sala,
                'atual' => $agendamentos->first(fn (Agendamento $a) => $a->inicio->lessThanOrEqualTo(now())),
                'proximo' => $agendamentos->first(fn (Agendamento $a) => $a->inicio->greaterThan(now())),
            ];
        });
    }

    /**
     * Agendamentos de hoje (todos ou apenas do profissional).
     *
     * @return Collection<int, Agendamento>
     */
    public function agendamentosDeHoje(?User $profissional = null): Collection
    {
        return Agendamento::agendados()
            ->with(['sala:id,nome,cor', 'profissional:id,nome'])
            ->whereDate('inicio', today())
            ->when($profissional, fn ($q) => $q->where('user_id', $profissional->id))
            ->orderBy('inicio')
            ->get();
    }

    /**
     * Atendimentos já iniciados que ainda não foram marcados como realizados ou não.
     *
     * @return Collection<int, Agendamento>
     */
    public function pendentesDeConfirmacao(User $profissional, int $limite = 10): Collection
    {
        return Agendamento::pendentesDeConfirmacao()
            ->with(['sala:id,nome,cor', 'profissional:id,nome'])
            ->where('user_id', $profissional->id)
            ->orderByDesc('inicio')
            ->limit($limite)
            ->get();
    }

    public function totalPendentesDeConfirmacao(?User $profissional = null): int
    {
        return Agendamento::pendentesDeConfirmacao()
            ->when($profissional, fn ($q) => $q->where('user_id', $profissional->id))
            ->count();
    }

    /**
     * Próximos agendamentos a partir de amanhã.
     *
     * @return Collection<int, Agendamento>
     */
    public function proximosAgendamentos(?User $profissional = null, int $dias = 7, int $limite = 10): Collection
    {
        return Agendamento::agendados()
            ->with(['sala:id,nome,cor', 'profissional:id,nome'])
            ->where('inicio', '>=', today()->addDay())
            ->where('inicio', '<', today()->addDays($dias + 1))
            ->when($profissional, fn ($q) => $q->where('user_id', $profissional->id))
            ->orderBy('inicio')
            ->limit($limite)
            ->get();
    }
}
