<?php

namespace App\Services;

use App\Enums\PerfilUsuario;
use App\Enums\SituacaoAtendimento;
use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Relatório mensal de atendimentos por profissional.
 * Os números são agregados no banco (uma consulta por relatório).
 */
class RelatorioService
{
    /** Colunas numéricas de cada linha do relatório. */
    public const COLUNAS = [
        'agendados', 'realizados', 'nao_realizados', 'pendentes', 'futuros', 'cancelados', 'minutos_realizados',
    ];

    /**
     * Uma linha por profissional com os números do mês.
     * Para o administrador, inclui profissionais ativos sem nenhum agendamento no mês.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function mensal(Carbon $mes, ?int $salaId = null, ?User $somenteProfissional = null): Collection
    {
        [$inicio, $fim] = $this->limites($mes);

        $agendado = StatusAgendamento::Agendado->value;
        $agora = now()->format('Y-m-d H:i:s');

        // TIMESTAMPDIFF: MySQL (banco usado pela aplicação e pelos testes).
        $numeros = Agendamento::query()
            ->select('user_id')
            ->selectRaw('SUM(status = ?) AS agendados', [$agendado])
            ->selectRaw('SUM(status = ? AND situacao = ?) AS realizados', [$agendado, SituacaoAtendimento::Realizado->value])
            ->selectRaw('SUM(status = ? AND situacao = ?) AS nao_realizados', [$agendado, SituacaoAtendimento::NaoRealizado->value])
            ->selectRaw('SUM(status = ? AND situacao = ? AND inicio <= ?) AS pendentes', [$agendado, SituacaoAtendimento::Pendente->value, $agora])
            ->selectRaw('SUM(status = ? AND situacao = ? AND inicio > ?) AS futuros', [$agendado, SituacaoAtendimento::Pendente->value, $agora])
            ->selectRaw('SUM(status = ?) AS cancelados', [StatusAgendamento::Cancelado->value])
            ->selectRaw(
                'SUM(CASE WHEN status = ? AND situacao = ? THEN TIMESTAMPDIFF(MINUTE, inicio, fim) ELSE 0 END) AS minutos_realizados',
                [$agendado, SituacaoAtendimento::Realizado->value],
            )
            ->where('inicio', '>=', $inicio)
            ->where('inicio', '<', $fim)
            ->when($salaId, fn ($q) => $q->where('sala_id', $salaId))
            ->when($somenteProfissional, fn ($q) => $q->where('user_id', $somenteProfissional->id))
            ->groupBy('user_id')
            ->toBase()
            ->get()
            ->keyBy('user_id');

        $profissionais = $this->profissionaisDoRelatorio($numeros->keys()->all(), $somenteProfissional);

        return $profissionais->map(function (User $profissional) use ($numeros) {
            $linha = $numeros->get($profissional->id);
            $valores = [];

            foreach (self::COLUNAS as $coluna) {
                $valores[$coluna] = (int) ($linha->{$coluna} ?? 0);
            }

            return ['profissional' => $profissional, ...$valores, 'taxa' => $this->taxa($valores)];
        })->values();
    }

    /**
     * Soma das linhas do relatório.
     *
     * @param  Collection<int, array<string, mixed>>  $linhas
     * @return array<string, int|float|null>
     */
    public function totais(Collection $linhas): array
    {
        $totais = [];

        foreach (self::COLUNAS as $coluna) {
            $totais[$coluna] = (int) $linhas->sum($coluna);
        }

        return [...$totais, 'taxa' => $this->taxa($totais)];
    }

    /**
     * Agendamentos do mês de um profissional (detalhe exibido para ele).
     *
     * @return EloquentCollection<int, Agendamento>
     */
    public function agendamentosDoMes(User $profissional, Carbon $mes, ?int $salaId = null): EloquentCollection
    {
        [$inicio, $fim] = $this->limites($mes);

        return Agendamento::query()
            ->with('sala:id,nome,cor')
            ->where('user_id', $profissional->id)
            ->where('inicio', '>=', $inicio)
            ->where('inicio', '<', $fim)
            ->when($salaId, fn ($q) => $q->where('sala_id', $salaId))
            ->orderBy('inicio')
            ->get();
    }

    /**
     * Taxa de comparecimento: realizados / (realizados + não realizados), em %.
     * Nula quando ainda não há atendimentos registrados.
     *
     * @param  array<string, int>  $valores
     */
    public function taxa(array $valores): ?float
    {
        $registrados = $valores['realizados'] + $valores['nao_realizados'];

        return $registrados > 0 ? round($valores['realizados'] * 100 / $registrados, 1) : null;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function limites(Carbon $mes): array
    {
        $inicio = $mes->copy()->startOfMonth();

        return [$inicio, $inicio->copy()->addMonth()];
    }

    /**
     * @param  list<int>  $comAgendamentos
     * @return EloquentCollection<int, User>
     */
    private function profissionaisDoRelatorio(array $comAgendamentos, ?User $somenteProfissional): EloquentCollection
    {
        if ($somenteProfissional) {
            return new EloquentCollection([$somenteProfissional]);
        }

        return User::query()
            ->where(fn ($q) => $q
                ->whereIn('id', $comAgendamentos)
                ->orWhere(fn ($q) => $q->where('ativo', true)->where('perfil', PerfilUsuario::Profissional)))
            ->orderBy('nome')
            ->get(['id', 'nome', 'profissao', 'ativo']);
    }
}
