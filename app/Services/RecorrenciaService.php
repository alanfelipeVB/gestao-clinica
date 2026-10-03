<?php

namespace App\Services;

use App\Enums\FrequenciaRecorrencia;
use App\Exceptions\RegraAgendamentoException;
use App\Models\Agendamento;
use App\Models\Recorrencia;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agendamentos recorrentes: gera as datas da série, monta a prévia (livre/conflito)
 * e cria as ocorrências livres. Cada ocorrência passa pelo AgendamentoService,
 * com todas as regras e a verificação de conflitos de um agendamento comum.
 */
class RecorrenciaService
{
    /** Limite de segurança de ocorrências por série. */
    public const MAX_OCORRENCIAS = 52;

    public const LIVRE = 'livre';

    public const CONFLITO = 'conflito';

    public const IGNORADA = 'ignorada';

    public function __construct(private readonly AgendamentoService $agendamentos)
    {
    }

    /**
     * Lista as datas da série e indica, para cada uma, se pode ser criada.
     *
     * @param  array{user_id: int, sala_id: int, inicio: Carbon, fim: Carbon, descricao: string}  $dados  primeira ocorrência
     * @param  array{frequencia: FrequenciaRecorrencia, data_fim: ?Carbon, ocorrencias: ?int}  $serie
     * @return Collection<int, array{inicio: ?Carbon, fim: ?Carbon, situacao: string, motivo: ?string, referencia: string}>
     *
     * @throws RegraAgendamentoException quando a sala ou o profissional estão inativos
     */
    public function previa(array $dados, array $serie): Collection
    {
        $this->validarSalaEProfissional($dados);

        $limite = $this->agendamentos->dataLimiteRecorrencia();
        $duracao = (int) $dados['inicio']->diffInMinutes($dados['fim']);
        $itens = collect();

        for ($n = 0; $n < self::MAX_OCORRENCIAS; $n++) {
            if ($serie['ocorrencias'] !== null && $n >= $serie['ocorrencias']) {
                break;
            }

            $inicio = $serie['frequencia']->ocorrencia($dados['inicio'], $n);

            if ($inicio === null) {
                $mes = $dados['inicio']->copy()->startOfMonth()->addMonths($n);

                if ($serie['data_fim'] && $mes->greaterThan($serie['data_fim'])) {
                    break;
                }

                $itens->push([
                    'inicio' => null,
                    'fim' => null,
                    'situacao' => self::IGNORADA,
                    'motivo' => "{$mes->translatedFormat('F/Y')} não tem dia {$dados['inicio']->day}.",
                    'referencia' => ucfirst($mes->translatedFormat('F \d\e Y')),
                ]);

                continue;
            }

            if ($serie['data_fim'] && $inicio->copy()->startOfDay()->greaterThan($serie['data_fim'])) {
                break;
            }

            $itens->push($this->avaliar($inicio, $inicio->copy()->addMinutes($duracao), $dados, $limite));
        }

        return $itens;
    }

    /**
     * Cria a série com as datas livres. As demais são ignoradas.
     *
     * @return array{recorrencia: Recorrencia, criados: Collection<int, Agendamento>, ignorados: int}
     *
     * @throws RegraAgendamentoException quando nenhuma data pode ser criada
     */
    public function criar(array $dados, array $serie, User $autor): array
    {
        $previa = $this->previa($dados, $serie);
        $livres = $previa->where('situacao', self::LIVRE);

        if ($livres->isEmpty()) {
            throw new RegraAgendamentoException('Nenhuma data da série está disponível. Ajuste o horário, a sala ou o período.', 'hora_inicio');
        }

        $limite = $this->agendamentos->dataLimiteRecorrencia();

        return DB::transaction(function () use ($dados, $serie, $autor, $livres, $previa, $limite) {
            $recorrencia = Recorrencia::create([
                'user_id' => $dados['user_id'],
                'sala_id' => $dados['sala_id'],
                'frequencia' => $serie['frequencia'],
                'data_inicio' => $dados['inicio']->toDateString(),
                'data_fim' => $serie['data_fim']?->toDateString(),
                'ocorrencias' => $serie['ocorrencias'],
                'criado_por' => $autor->id,
            ]);

            $criados = collect();

            foreach ($livres as $item) {
                try {
                    $criados->push($this->agendamentos->criar([
                        ...$dados,
                        'inicio' => $item['inicio'],
                        'fim' => $item['fim'],
                        'recorrencia_id' => $recorrencia->id,
                    ], $autor, $limite));
                } catch (RegraAgendamentoException) {
                    // Ocupada entre a prévia e a confirmação: fica de fora da série.
                }
            }

            return [
                'recorrencia' => $recorrencia,
                'criados' => $criados,
                'ignorados' => $previa->count() - $criados->count(),
            ];
        });
    }

    /**
     * @return array{inicio: Carbon, fim: Carbon, situacao: string, motivo: ?string, referencia: string}
     */
    private function avaliar(Carbon $inicio, Carbon $fim, array $dados, Carbon $limite): array
    {
        $item = [
            'inicio' => $inicio,
            'fim' => $fim,
            'situacao' => self::LIVRE,
            'motivo' => null,
            'referencia' => ucfirst($inicio->translatedFormat('D, d/m/Y')),
        ];

        try {
            $this->agendamentos->validarHorario($inicio, $fim, $limite);
        } catch (RegraAgendamentoException $e) {
            return [...$item, 'situacao' => self::IGNORADA, 'motivo' => $e->getMessage()];
        }

        if ($conflito = $this->agendamentos->buscarConflito('sala_id', $dados['sala_id'], $inicio, $fim)) {
            return [...$item, 'situacao' => self::CONFLITO,
                'motivo' => "Sala ocupada das {$conflito->horario()} por {$conflito->profissional->nome}."];
        }

        if ($conflito = $this->agendamentos->buscarConflito('user_id', $dados['user_id'], $inicio, $fim)) {
            return [...$item, 'situacao' => self::CONFLITO,
                'motivo' => "Profissional já agendado das {$conflito->horario()} na {$conflito->sala->nome}."];
        }

        return $item;
    }

    private function validarSalaEProfissional(array $dados): void
    {
        if (! Sala::whereKey($dados['sala_id'])->where('ativa', true)->exists()) {
            throw new RegraAgendamentoException('A sala selecionada está inativa e não aceita agendamentos.', 'sala_id');
        }

        if (! User::whereKey($dados['user_id'])->where('ativo', true)->exists()) {
            throw new RegraAgendamentoException('O profissional selecionado está inativo.', 'user_id');
        }
    }
}
