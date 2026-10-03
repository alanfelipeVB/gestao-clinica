<?php

namespace Database\Seeders;

use App\Enums\SituacaoAtendimento;
use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use App\Services\AgendamentoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Profissionais e agendamentos de exemplo para o ambiente de desenvolvimento.
 * Gera agendamentos de 3 dias úteis atrás até 7 dias úteis à frente, sem conflitos.
 * Idempotente: não roda novamente se os profissionais de demonstração já existirem.
 */
class DemonstracaoSeeder extends Seeder
{
    /** Senha de todos os profissionais de demonstração (somente ambiente local). */
    public const SENHA = 'password';

    private const PROFISSIONAIS = [
        ['nome' => 'Dra. Ana Ribeiro', 'email' => 'ana.ribeiro@demo.test', 'profissao' => 'Psicóloga'],
        ['nome' => 'Dr. João Martins', 'email' => 'joao.martins@demo.test', 'profissao' => 'Fisioterapeuta'],
        ['nome' => 'Dra. Carla Mendes', 'email' => 'carla.mendes@demo.test', 'profissao' => 'Nutricionista'],
        ['nome' => 'Dr. Paulo Azevedo', 'email' => 'paulo.azevedo@demo.test', 'profissao' => 'Fonoaudiólogo'],
        ['nome' => 'Dra. Juliana Costa', 'email' => 'juliana.costa@demo.test', 'profissao' => 'Dermatologista'],
    ];

    /** Faixas de horário (não se sobrepõem entre si). */
    private const FAIXAS = [
        ['08:00', '09:00'],
        ['09:30', '11:00'],
        ['11:00', '12:00'],
        ['14:00', '15:00'],
        ['15:30', '17:00'],
        ['17:00', '18:00'],
    ];

    private const DESCRICOES = [
        'Atendimento e avaliação de paciente.',
        'Sessão de acompanhamento.',
        'Primeira consulta.',
        'Retorno e ajuste de tratamento.',
        'Procedimento agendado.',
        'Atendimento familiar.',
    ];

    public function run(AgendamentoService $servico): void
    {
        if (User::where('email', self::PROFISSIONAIS[0]['email'])->exists()) {
            $this->command?->info('Dados de demonstração já existem; nada a fazer.');

            return;
        }

        $profissionais = collect(self::PROFISSIONAIS)->map(fn (array $dados) => User::factory()->create([
            ...$dados,
            'password' => self::SENHA,
        ]));

        $salas = Sala::ativas()->orderBy('id')->get();

        if ($salas->isEmpty()) {
            $this->command?->warn('Nenhuma sala ativa: rode o SalaSeeder antes.');

            return;
        }

        $criados = 0;

        foreach ($this->diasUteis() as $d => $dia) {
            foreach ($salas as $s => $sala) {
                foreach (self::FAIXAS as $f => [$horaInicio, $horaFim]) {
                    // Ocupa cerca de 2/3 das faixas, variando entre dias e salas.
                    if (($d + $s + $f) % 3 === 0) {
                        continue;
                    }

                    // Na mesma faixa, cada sala recebe um profissional diferente.
                    $profissional = $profissionais[($s + $f + $d) % $profissionais->count()];
                    $inicio = Carbon::parse("{$dia->toDateString()} {$horaInicio}");
                    $fim = Carbon::parse("{$dia->toDateString()} {$horaFim}");

                    // Respeita agendamentos já existentes no banco.
                    if ($servico->buscarConflito('sala_id', $sala->id, $inicio, $fim)
                        || $servico->buscarConflito('user_id', $profissional->id, $inicio, $fim)) {
                        continue;
                    }

                    $situacao = $this->situacaoDeExemplo($fim, $d + $s + $f);

                    Agendamento::create([
                        'user_id' => $profissional->id,
                        'sala_id' => $sala->id,
                        'inicio' => $inicio,
                        'fim' => $fim,
                        'descricao' => self::DESCRICOES[($d + $s + $f) % count(self::DESCRICOES)],
                        'status' => StatusAgendamento::Agendado,
                        'situacao' => $situacao,
                        'situacao_marcada_por' => $situacao === SituacaoAtendimento::Pendente ? null : $profissional->id,
                        'situacao_marcada_em' => $situacao === SituacaoAtendimento::Pendente ? null : $fim,
                        'criado_por' => $profissional->id,
                    ]);

                    $criados++;
                }
            }
        }

        $this->command?->info("Demonstração: {$profissionais->count()} profissionais e {$criados} agendamentos criados.");
    }

    /**
     * Agendamentos já encerrados recebem um resultado variado: a maioria realizados,
     * alguns não realizados e alguns ainda pendentes de confirmação.
     */
    public static function situacaoDeExemplo(Carbon $fim, int $semente): SituacaoAtendimento
    {
        if ($fim->isFuture()) {
            return SituacaoAtendimento::Pendente;
        }

        return match ($semente % 7) {
            0 => SituacaoAtendimento::NaoRealizado,
            1 => SituacaoAtendimento::Pendente,
            default => SituacaoAtendimento::Realizado,
        };
    }

    /**
     * De 3 dias úteis atrás até 7 dias úteis à frente (inclui hoje, se for dia útil).
     *
     * @return list<Carbon>
     */
    private function diasUteis(): array
    {
        $dias = [];
        $dia = today()->subWeekdays(3);
        $limite = today()->addWeekdays(7);

        while ($dia->lessThanOrEqualTo($limite)) {
            if ($dia->isWeekday()) {
                $dias[] = $dia->copy();
            }
            $dia->addDay();
        }

        return $dias;
    }
}
