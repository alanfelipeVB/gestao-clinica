<?php

namespace App\Services;

use App\Enums\SituacaoAtendimento;
use App\Enums\StatusAgendamento;
use App\Events\AgendamentoAtualizado;
use App\Events\AgendamentoCancelado;
use App\Events\AgendamentoCriado;
use App\Exceptions\ConflitoDeHorarioException;
use App\Exceptions\RegraAgendamentoException;
use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regras de negócio de agendamento: criação, edição e cancelamento.
 * Toda gravação de agendamento deve passar por aqui.
 */
class AgendamentoService
{
    /** Granularidade dos horários, em minutos. */
    public const INTERVALO_MINUTOS = 15;

    public function __construct(private readonly ConfiguracaoService $configuracoes)
    {
    }

    /**
     * @param  array{user_id: int, sala_id: int, inicio: Carbon, fim: Carbon, descricao: string}  $dados
     *
     * @throws RegraAgendamentoException
     */
    public function criar(array $dados, User $autor): Agendamento
    {
        $profissional = User::findOrFail($dados['user_id']);
        $sala = Sala::findOrFail($dados['sala_id']);

        $this->validarProfissional($profissional);
        $this->validarSala($sala);
        $this->validarHorario($dados['inicio'], $dados['fim']);

        $agendamento = DB::transaction(function () use ($dados, $profissional, $sala, $autor) {
            $this->bloquearEVerificarConflitos($sala->id, $profissional->id, $dados['inicio'], $dados['fim']);

            return Agendamento::create([
                'user_id' => $profissional->id,
                'sala_id' => $sala->id,
                'inicio' => $dados['inicio'],
                'fim' => $dados['fim'],
                'descricao' => $dados['descricao'],
                'status' => StatusAgendamento::Agendado,
                'situacao' => SituacaoAtendimento::Pendente,
                'criado_por' => $autor->id,
            ]);
        });

        AgendamentoCriado::dispatch($agendamento);

        return $agendamento;
    }

    /**
     * @param  array{user_id: int, sala_id: int, inicio: Carbon, fim: Carbon, descricao: string}  $dados
     *
     * @throws RegraAgendamentoException
     */
    public function atualizar(Agendamento $agendamento, array $dados): Agendamento
    {
        if (! $agendamento->estaAgendado()) {
            throw new RegraAgendamentoException('Agendamentos cancelados não podem ser alterados.');
        }

        if ($agendamento->jaIniciou()) {
            throw new RegraAgendamentoException('Agendamentos que já começaram não podem ser alterados.');
        }

        // Sala e profissional só precisam estar ativos se forem trocados.
        if ((int) $dados['user_id'] !== $agendamento->user_id) {
            $this->validarProfissional(User::findOrFail($dados['user_id']));
        }

        if ((int) $dados['sala_id'] !== $agendamento->sala_id) {
            $this->validarSala(Sala::findOrFail($dados['sala_id']));
        }

        $this->validarHorario($dados['inicio'], $dados['fim']);

        DB::transaction(function () use ($agendamento, $dados) {
            $this->bloquearEVerificarConflitos(
                (int) $dados['sala_id'],
                (int) $dados['user_id'],
                $dados['inicio'],
                $dados['fim'],
                ignorarId: $agendamento->id,
            );

            $agendamento->update([
                'user_id' => $dados['user_id'],
                'sala_id' => $dados['sala_id'],
                'inicio' => $dados['inicio'],
                'fim' => $dados['fim'],
                'descricao' => $dados['descricao'],
            ]);
        });

        AgendamentoAtualizado::dispatch($agendamento);

        return $agendamento;
    }

    /**
     * @throws RegraAgendamentoException
     */
    public function cancelar(Agendamento $agendamento, User $autor, ?string $motivo = null): Agendamento
    {
        if (! $agendamento->estaAgendado()) {
            throw new RegraAgendamentoException('Este agendamento já foi cancelado.');
        }

        $agendamento->update([
            'status' => StatusAgendamento::Cancelado,
            'cancelado_por' => $autor->id,
            'cancelado_em' => now(),
            'motivo_cancelamento' => $motivo,
        ]);

        AgendamentoCancelado::dispatch($agendamento);

        return $agendamento;
    }

    /**
     * Registra o resultado do atendimento (realizado / não realizado).
     *
     * @throws RegraAgendamentoException
     */
    public function registrarAtendimento(
        Agendamento $agendamento,
        SituacaoAtendimento $situacao,
        User $autor,
        ?string $observacao = null,
    ): Agendamento {
        if ($situacao === SituacaoAtendimento::Pendente) {
            throw new RegraAgendamentoException('Informe se o atendimento foi realizado ou não.', 'situacao');
        }

        if (! $agendamento->estaAgendado()) {
            throw new RegraAgendamentoException('Agendamentos cancelados não têm atendimento a registrar.', 'situacao');
        }

        if (! $agendamento->jaIniciou()) {
            throw new RegraAgendamentoException('O atendimento só pode ser registrado a partir do horário de início.', 'situacao');
        }

        $agendamento->update([
            'situacao' => $situacao,
            'situacao_marcada_por' => $autor->id,
            'situacao_marcada_em' => now(),
            'observacao_atendimento' => $observacao,
        ]);

        return $agendamento;
    }

    /**
     * Cancela vários agendamentos (ex.: ao desativar sala ou profissional).
     *
     * @param  Collection<int, Agendamento>  $agendamentos
     * @return int quantidade cancelada
     */
    public function cancelarEmLote(Collection $agendamentos, User $autor, string $motivo): int
    {
        return DB::transaction(function () use ($agendamentos, $autor, $motivo) {
            $agendamentos->each(fn (Agendamento $a) => $this->cancelar($a, $autor, $motivo));

            return $agendamentos->count();
        });
    }

    /**
     * Horários selecionáveis no formulário (00:00, 00:15, ..., 23:45).
     *
     * @return list<string>
     */
    public static function horarios(): array
    {
        $total = intdiv(24 * 60, self::INTERVALO_MINUTOS);

        return array_map(
            fn (int $i) => sprintf('%02d:%02d', intdiv($i * self::INTERVALO_MINUTOS, 60), ($i * self::INTERVALO_MINUTOS) % 60),
            range(0, $total - 1),
        );
    }

    /**
     * Último dia em que é permitido agendar.
     */
    public function dataLimite(): Carbon
    {
        return today()->addDays($this->configuracoes->antecedenciaMaximaDias());
    }

    /**
     * Procura um agendamento ativo que se sobreponha ao período informado.
     *
     * Há sobreposição quando: existente.inicio < novo.fim E existente.fim > novo.inicio.
     * A comparação é estrita, então agendamentos consecutivos (14:00–15:00 e 15:00–16:00)
     * não conflitam. Cancelados são ignorados.
     *
     * @param  'sala_id'|'user_id'  $coluna
     */
    public function buscarConflito(string $coluna, int $id, Carbon $inicio, Carbon $fim, ?int $ignorarId = null): ?Agendamento
    {
        return Agendamento::query()
            ->agendados()
            ->where($coluna, $id)
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->with(['sala', 'profissional'])
            ->orderBy('inicio')
            ->first();
    }

    /**
     * Deve ser chamado dentro de uma transação.
     *
     * Trava as linhas da sala e do profissional (sempre nessa ordem, para evitar deadlock)
     * até o fim da transação. Assim, duas requisições simultâneas para a mesma sala ou o
     * mesmo profissional são processadas uma de cada vez e a segunda enxerga a primeira.
     *
     * @throws ConflitoDeHorarioException
     */
    private function bloquearEVerificarConflitos(int $salaId, int $userId, Carbon $inicio, Carbon $fim, ?int $ignorarId = null): void
    {
        Sala::query()->whereKey($salaId)->lockForUpdate()->first();
        User::query()->whereKey($userId)->lockForUpdate()->first();

        if ($conflito = $this->buscarConflito('sala_id', $salaId, $inicio, $fim, $ignorarId)) {
            throw ConflitoDeHorarioException::daSala($conflito);
        }

        if ($conflito = $this->buscarConflito('user_id', $userId, $inicio, $fim, $ignorarId)) {
            throw ConflitoDeHorarioException::doProfissional($conflito);
        }
    }

    private function validarProfissional(User $profissional): void
    {
        if (! $profissional->ativo) {
            throw new RegraAgendamentoException('O profissional selecionado está inativo.', 'user_id');
        }
    }

    private function validarSala(Sala $sala): void
    {
        if (! $sala->ativa) {
            throw new RegraAgendamentoException('A sala selecionada está inativa e não aceita agendamentos.', 'sala_id');
        }
    }

    private function validarHorario(Carbon $inicio, Carbon $fim): void
    {
        if (! $inicio->isSameDay($fim)) {
            throw new RegraAgendamentoException('O agendamento deve começar e terminar no mesmo dia.', 'hora_fim');
        }

        if ($fim->lessThanOrEqualTo($inicio)) {
            throw new RegraAgendamentoException('O horário de término deve ser posterior ao horário de início.', 'hora_fim');
        }

        foreach ([$inicio, $fim] as $horario) {
            if ($horario->minute % self::INTERVALO_MINUTOS !== 0 || $horario->second !== 0) {
                throw new RegraAgendamentoException(
                    'Os horários devem ser em intervalos de '.self::INTERVALO_MINUTOS.' minutos (ex.: 14:00, 14:15, 14:30).',
                    'hora_inicio',
                );
            }
        }

        if ($inicio->lessThanOrEqualTo(now())) {
            throw new RegraAgendamentoException('Não é possível agendar em um horário que já passou.', 'hora_inicio');
        }

        $limite = $this->dataLimite();

        if ($inicio->copy()->startOfDay()->greaterThan($limite)) {
            throw new RegraAgendamentoException(
                "É possível agendar com até {$this->configuracoes->antecedenciaMaximaDias()} dias de antecedência (até {$limite->format('d/m/Y')}).",
                'data',
            );
        }
    }
}
