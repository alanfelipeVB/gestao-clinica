<?php

namespace App\Services;

use App\Enums\StatusAgendamento;
use App\Events\AgendamentoAtualizado;
use App\Events\AgendamentoCancelado;
use App\Events\AgendamentoCriado;
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

        $agendamento = DB::transaction(fn () => Agendamento::create([
            'user_id' => $profissional->id,
            'sala_id' => $sala->id,
            'inicio' => $dados['inicio'],
            'fim' => $dados['fim'],
            'descricao' => $dados['descricao'],
            'status' => StatusAgendamento::Agendado,
            'criado_por' => $autor->id,
        ]));

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

        DB::transaction(fn () => $agendamento->update([
            'user_id' => $dados['user_id'],
            'sala_id' => $dados['sala_id'],
            'inicio' => $dados['inicio'],
            'fim' => $dados['fim'],
            'descricao' => $dados['descricao'],
        ]));

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
