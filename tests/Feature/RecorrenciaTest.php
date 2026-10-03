<?php

namespace Tests\Feature;

use App\Enums\FrequenciaRecorrencia;
use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\Recorrencia;
use App\Models\Sala;
use App\Models\User;
use App\Services\RecorrenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Agora": segunda-feira, 05/10/2026 às 10:00. Antecedência para recorrência: 90 dias (até 03/01/2027).
 */
class RecorrenciaTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private Sala $sala;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-05 10:00:00'));

        $this->ana = User::factory()->create(['nome' => 'Ana Souza']);
        $this->sala = Sala::factory()->create(['nome' => 'Sala 01']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formulario(array $sobrescrever = []): array
    {
        return [
            'sala_id' => $this->sala->id,
            'data' => '2026-10-06',
            'hora_inicio' => '14:00',
            'hora_fim' => '15:00',
            'descricao' => 'Sessão semanal',
            'repetir' => '1',
            'frequencia' => 'semanal',
            'fim_tipo' => 'ocorrencias',
            'ocorrencias' => 4,
            ...$sobrescrever,
        ];
    }

    private function datas(): array
    {
        return Agendamento::orderBy('inicio')->get()->map(fn ($a) => $a->inicio->format('Y-m-d H:i'))->all();
    }

    // ------------------------------------------------------------------
    // Geração das datas
    // ------------------------------------------------------------------

    public function test_frequencias_geram_as_datas_corretas(): void
    {
        $primeira = Carbon::parse('2026-01-31 14:00');

        $this->assertSame('2026-02-07', FrequenciaRecorrencia::Semanal->ocorrencia($primeira, 1)->toDateString());
        $this->assertSame('2026-02-14', FrequenciaRecorrencia::Quinzenal->ocorrencia($primeira, 1)->toDateString());
        $this->assertSame('2026-03-31 14:00', FrequenciaRecorrencia::Mensal->ocorrencia($primeira, 2)->format('Y-m-d H:i'));
        // Fevereiro não tem dia 31.
        $this->assertNull(FrequenciaRecorrencia::Mensal->ocorrencia($primeira, 1));
    }

    // ------------------------------------------------------------------
    // Prévia e criação
    // ------------------------------------------------------------------

    public function test_primeiro_envio_mostra_a_previa_sem_criar_nada(): void
    {
        $this->actingAs($this->ana)
            ->post('/agendamentos', $this->formulario())
            ->assertOk()
            ->assertSee('Confirmar agendamentos recorrentes')
            ->assertSee('4 de 4 livres')
            ->assertSee('Confirmar 4 agendamentos');

        $this->assertSame(0, Agendamento::count());
        $this->assertSame(0, Recorrencia::count());
    }

    public function test_confirmacao_cria_serie_semanal(): void
    {
        $this->actingAs($this->ana)
            ->post('/agendamentos', $this->formulario(['confirmado' => '1']))
            ->assertRedirect()
            ->assertSessionHas('sucesso', 'Série criada: 4 agendamento(s).');

        $this->assertSame(
            ['2026-10-06 14:00', '2026-10-13 14:00', '2026-10-20 14:00', '2026-10-27 14:00'],
            $this->datas(),
        );

        $recorrencia = Recorrencia::sole();
        $this->assertSame(FrequenciaRecorrencia::Semanal, $recorrencia->frequencia);
        $this->assertSame(4, $recorrencia->agendamentos()->count());
        $this->assertSame(4, Agendamento::where('user_id', $this->ana->id)->where('criado_por', $this->ana->id)->count());
    }

    public function test_serie_quinzenal_com_data_final(): void
    {
        $this->actingAs($this->ana)->post('/agendamentos', $this->formulario([
            'frequencia' => 'quinzenal',
            'fim_tipo' => 'data',
            'data_fim' => '2026-11-17',
            'confirmado' => '1',
        ]));

        $this->assertSame(
            ['2026-10-06 14:00', '2026-10-20 14:00', '2026-11-03 14:00', '2026-11-17 14:00'],
            $this->datas(),
        );
    }

    public function test_serie_mensal_pula_dias_inexistentes(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00'));

        $previa = app(RecorrenciaService::class)->previa([
            'user_id' => $this->ana->id,
            'sala_id' => $this->sala->id,
            'inicio' => Carbon::parse('2026-10-31 09:00'),
            'fim' => Carbon::parse('2026-10-31 10:00'),
            'descricao' => 'Mensal',
        ], ['frequencia' => FrequenciaRecorrencia::Mensal, 'data_fim' => null, 'ocorrencias' => 3]);

        // Out/31 livre, Nov não tem 31, Dez/31 livre.
        $this->assertSame(['livre', 'ignorada', 'livre'], $previa->pluck('situacao')->all());
        $this->assertSame('2026-12-31', $previa[2]['inicio']->toDateString());
    }

    public function test_datas_alem_do_limite_de_recorrencia_nao_sao_criadas(): void
    {
        // Semanal a partir de 06/10: ocorrências após 03/01/2027 ficam fora do limite de 90 dias.
        $this->actingAs($this->ana)->post('/agendamentos', $this->formulario([
            'ocorrencias' => 15,
            'confirmado' => '1',
        ]));

        $this->assertSame(13, Agendamento::count());
        $this->assertSame('2026-12-29', Agendamento::max('inicio') ? Carbon::parse(Agendamento::max('inicio'))->toDateString() : null);
    }

    public function test_recorrencia_respeita_limite_proprio_e_nao_o_de_30_dias(): void
    {
        $this->actingAs($this->ana)->post('/agendamentos', $this->formulario([
            'ocorrencias' => 8,
            'confirmado' => '1',
        ]));

        // 8 semanas a partir de 06/10 → última em 24/11 (além dos 30 dias do agendamento avulso).
        $this->assertSame(8, Agendamento::count());
    }

    // ------------------------------------------------------------------
    // Conflitos
    // ------------------------------------------------------------------

    public function test_datas_em_conflito_ficam_de_fora(): void
    {
        $bruno = User::factory()->create(['nome' => 'Bruno Lima']);

        Agendamento::factory()->periodo('2026-10-13 14:30', '2026-10-13 15:30')
            ->create(['user_id' => $bruno->id, 'sala_id' => $this->sala->id]);

        $this->actingAs($this->ana)
            ->post('/agendamentos', $this->formulario())
            ->assertSee('3 de 4 livres')
            ->assertSee('Sala ocupada das 14:30 – 15:30 por Bruno Lima.');

        $this->actingAs($this->ana)
            ->post('/agendamentos', $this->formulario(['confirmado' => '1']))
            ->assertSessionHas('sucesso', 'Série criada: 3 agendamento(s). 1 data(s) ficaram de fora por conflito ou restrição.');

        $this->assertSame(3, Agendamento::where('user_id', $this->ana->id)->count());
        $this->assertFalse(Agendamento::where('user_id', $this->ana->id)->whereDate('inicio', '2026-10-13')->exists());
    }

    public function test_conflito_do_profissional_em_outra_sala(): void
    {
        $outraSala = Sala::factory()->create();

        Agendamento::factory()->periodo('2026-10-20 14:00', '2026-10-20 15:00')
            ->create(['user_id' => $this->ana->id, 'sala_id' => $outraSala->id]);

        $previa = $this->actingAs($this->ana)->post('/agendamentos', $this->formulario());

        $previa->assertSee('Profissional já agendado');
    }

    public function test_nenhuma_data_livre_nao_cria_serie(): void
    {
        $sala = $this->sala;
        Agendamento::factory()->count(2)->sequence(
            ['inicio' => '2026-10-06 14:00', 'fim' => '2026-10-06 15:00'],
            ['inicio' => '2026-10-13 14:00', 'fim' => '2026-10-13 15:00'],
        )->create(['sala_id' => $sala->id]);

        $this->actingAs($this->ana)
            ->from('/agendamentos/create')
            ->post('/agendamentos', $this->formulario(['ocorrencias' => 2, 'confirmado' => '1']))
            ->assertRedirect('/agendamentos/create')
            ->assertSessionHasErrors('hora_inicio');

        $this->assertSame(0, Recorrencia::count());
        $this->assertSame(2, Agendamento::count());
    }

    public function test_datas_no_passado_sao_ignoradas(): void
    {
        $this->actingAs($this->ana)
            ->from('/agendamentos/create')
            ->post('/agendamentos', $this->formulario([
                'data' => '2026-10-05',
                'hora_inicio' => '08:00',
                'hora_fim' => '09:00',
                'ocorrencias' => 2,
                'frequencia' => 'mensal',
                'confirmado' => '1',
            ]));

        // 05/10 08:00 já passou; 05/11 08:00 é livre → cria só 1.
        $this->assertSame(1, Agendamento::count());
        $this->assertSame('2026-11-05', Agendamento::sole()->inicio->toDateString());
    }

    // ------------------------------------------------------------------
    // Validação
    // ------------------------------------------------------------------

    public function test_validacao_dos_campos_da_repeticao(): void
    {
        $this->actingAs($this->ana)
            ->post('/agendamentos', $this->formulario(['frequencia' => 'diaria', 'ocorrencias' => 1]))
            ->assertSessionHasErrors(['frequencia', 'ocorrencias']);

        $this->actingAs($this->ana)
            ->post('/agendamentos', $this->formulario(['fim_tipo' => 'data', 'data_fim' => '2026-10-01']))
            ->assertSessionHasErrors('data_fim');

        $this->assertSame(0, Agendamento::count());
    }

    public function test_sem_repetir_continua_criando_agendamento_unico(): void
    {
        $this->actingAs($this->ana)
            ->post('/agendamentos', $this->formulario(['repetir' => '0', 'frequencia' => 'invalida']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Agendamento::count());
        $this->assertNull(Agendamento::sole()->recorrencia_id);
    }

    // ------------------------------------------------------------------
    // Cancelamento
    // ------------------------------------------------------------------

    public function test_cancelar_somente_este_ou_este_e_os_proximos(): void
    {
        $this->actingAs($this->ana)->post('/agendamentos', $this->formulario(['confirmado' => '1']));
        [$primeiro, $segundo, $terceiro, $quarto] = Agendamento::orderBy('inicio')->get()->all();

        $this->actingAs($this->ana)
            ->patch("/agendamentos/{$primeiro->id}/cancelar", ['escopo' => 'este'])
            ->assertSessionHas('sucesso');

        $this->assertSame(StatusAgendamento::Cancelado, $primeiro->fresh()->status);
        $this->assertSame(StatusAgendamento::Agendado, $segundo->fresh()->status);

        $this->actingAs($this->ana)
            ->patch("/agendamentos/{$terceiro->id}/cancelar", ['escopo' => 'proximos', 'motivo_cancelamento' => 'Fim do tratamento'])
            ->assertSessionHas('sucesso', '2 agendamento(s) da série cancelado(s). Os horários foram liberados.');

        $this->assertSame(StatusAgendamento::Agendado, $segundo->fresh()->status);
        $this->assertSame(StatusAgendamento::Cancelado, $terceiro->fresh()->status);
        $this->assertSame(StatusAgendamento::Cancelado, $quarto->fresh()->status);
        $this->assertSame('Fim do tratamento', $quarto->fresh()->motivo_cancelamento);
    }

    public function test_detalhes_e_lista_indicam_a_serie(): void
    {
        $this->actingAs($this->ana)->post('/agendamentos', $this->formulario(['confirmado' => '1']));
        $agendamento = Agendamento::orderBy('inicio')->first();

        $this->actingAs($this->ana)->get("/agendamentos/{$agendamento->id}")
            ->assertOk()
            ->assertSee('Série semanal')
            ->assertSee('Este e os próximos da série');

        $this->actingAs($this->ana)
            ->get("/agendamentos?periodo=todos&recorrencia_id={$agendamento->recorrencia_id}")
            ->assertOk()
            ->assertSee('Agendamento recorrente');
    }
}
