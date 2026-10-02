<?php

namespace Tests\Feature;

use App\Enums\StatusAgendamento;
use App\Exceptions\ConflitoDeHorarioException;
use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use App\Services\AgendamentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regra principal: uma sala não pode ter dois agendamentos sobrepostos,
 * e um profissional não pode estar em duas salas ao mesmo tempo.
 *
 * Cenário base: Sala 01 reservada em 06/10/2026 das 14:00 às 15:00 (Ana).
 */
class ConflitoAgendamentoTest extends TestCase
{
    use RefreshDatabase;

    private AgendamentoService $servico;

    private User $ana;

    private User $bruno;

    private Sala $sala01;

    private Sala $sala02;

    private Agendamento $existente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-05 10:00:00'));

        $this->servico = app(AgendamentoService::class);
        $this->ana = User::factory()->create(['nome' => 'Ana Souza']);
        $this->bruno = User::factory()->create(['nome' => 'Bruno Lima']);
        $this->sala01 = Sala::factory()->create(['nome' => 'Sala 01']);
        $this->sala02 = Sala::factory()->create(['nome' => 'Sala 02']);

        $this->existente = Agendamento::factory()
            ->periodo('2026-10-06 14:00', '2026-10-06 15:00')
            ->create([
                'user_id' => $this->ana->id,
                'sala_id' => $this->sala01->id,
                'descricao' => 'Avaliação confidencial do paciente X',
            ]);
    }

    /**
     * @return array{user_id: int, sala_id: int, inicio: Carbon, fim: Carbon, descricao: string}
     */
    private function dados(string $inicio, string $fim, ?User $profissional = null, ?Sala $sala = null): array
    {
        return [
            'user_id' => ($profissional ?? $this->bruno)->id,
            'sala_id' => ($sala ?? $this->sala01)->id,
            'inicio' => Carbon::parse("2026-10-06 {$inicio}"),
            'fim' => Carbon::parse("2026-10-06 {$fim}"),
            'descricao' => 'Novo atendimento',
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function periodosEmConflito(): array
    {
        return [
            'totalmente dentro do existente' => ['14:15', '14:45'],
            'começa antes e termina durante' => ['13:30', '14:30'],
            'começa durante e termina depois' => ['14:30', '15:30'],
            'exatamente o mesmo horário' => ['14:00', '15:00'],
            'engloba o existente' => ['13:00', '16:00'],
            'mesmo início, termina depois' => ['14:00', '15:30'],
            'começa antes, mesmo fim' => ['13:00', '15:00'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function periodosSemConflito(): array
    {
        return [
            'consecutivo logo depois' => ['15:00', '16:00'],
            'consecutivo logo antes' => ['13:00', '14:00'],
            'bem antes' => ['08:00', '09:00'],
            'bem depois' => ['17:00', '18:30'],
        ];
    }

    #[DataProvider('periodosEmConflito')]
    public function test_bloqueia_sobreposicao_na_mesma_sala(string $inicio, string $fim): void
    {
        try {
            $this->servico->criar($this->dados($inicio, $fim), $this->bruno);
            $this->fail('Era esperado um conflito de horário.');
        } catch (ConflitoDeHorarioException $e) {
            $this->assertTrue($e->conflitante->is($this->existente));
        }

        $this->assertSame(1, Agendamento::count());
    }

    #[DataProvider('periodosSemConflito')]
    public function test_permite_periodos_sem_sobreposicao_incluindo_consecutivos(string $inicio, string $fim): void
    {
        $novo = $this->servico->criar($this->dados($inicio, $fim), $this->bruno);

        $this->assertTrue($novo->exists);
        $this->assertSame(2, Agendamento::count());
    }

    public function test_permite_mesmo_horario_em_outra_sala(): void
    {
        $novo = $this->servico->criar($this->dados('14:00', '15:00', sala: $this->sala02), $this->bruno);

        $this->assertTrue($novo->exists);
    }

    public function test_bloqueia_mesmo_profissional_em_duas_salas_ao_mesmo_tempo(): void
    {
        $this->expectException(ConflitoDeHorarioException::class);
        $this->expectExceptionMessage('Ana Souza já tem um agendamento');

        $this->servico->criar($this->dados('14:30', '15:30', $this->ana, $this->sala02), $this->ana);
    }

    public function test_cancelamento_libera_o_horario(): void
    {
        $this->servico->cancelar($this->existente, $this->ana);

        $novo = $this->servico->criar($this->dados('14:00', '15:00'), $this->bruno);

        $this->assertTrue($novo->exists);
        $this->assertSame(StatusAgendamento::Cancelado, $this->existente->fresh()->status);
    }

    public function test_edicao_ignora_o_proprio_agendamento(): void
    {
        // Estende o próprio agendamento: 14:00–15:00 → 14:00–15:30.
        $this->servico->atualizar($this->existente, $this->dados('14:00', '15:30', $this->ana));

        $this->assertSame('15:30', $this->existente->fresh()->fim->format('H:i'));
    }

    public function test_edicao_que_passa_a_colidir_com_outro_agendamento_e_bloqueada(): void
    {
        $doBruno = $this->servico->criar($this->dados('16:00', '17:00'), $this->bruno);

        try {
            // Bruno tenta antecipar para 14:30, colidindo com a Ana.
            $this->servico->atualizar($doBruno, $this->dados('14:30', '15:30'));
            $this->fail('Era esperado um conflito de horário.');
        } catch (ConflitoDeHorarioException $e) {
            $this->assertTrue($e->conflitante->is($this->existente));
        }

        $this->assertSame('16:00', $doBruno->fresh()->inicio->format('H:i'));
    }

    public function test_edicao_para_horario_consecutivo_e_permitida(): void
    {
        $doBruno = $this->servico->criar($this->dados('16:00', '17:00'), $this->bruno);

        $this->servico->atualizar($doBruno, $this->dados('15:00', '16:00'));

        $this->assertSame('15:00', $doBruno->fresh()->inicio->format('H:i'));
    }

    public function test_formulario_mostra_conflito_sem_expor_a_descricao_alheia(): void
    {
        $this->actingAs($this->bruno)
            ->from('/agendamentos/create')
            ->post('/agendamentos', [
                'sala_id' => $this->sala01->id,
                'data' => '2026-10-06',
                'hora_inicio' => '14:30',
                'hora_fim' => '15:30',
                'descricao' => 'Novo atendimento',
            ])
            ->assertRedirect('/agendamentos/create')
            ->assertSessionHasErrors('hora_inicio');

        $mensagem = session('errors')->first('hora_inicio');

        $this->assertStringContainsString('Sala 01 já está reservada em 06/10/2026 das 14:00 às 15:00 por Ana Souza', $mensagem);
        $this->assertStringNotContainsString('confidencial', $mensagem);
        $this->assertSame(1, Agendamento::count());
    }

    public function test_admin_tambem_respeita_conflitos(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/agendamentos', [
                'user_id' => $this->bruno->id,
                'sala_id' => $this->sala01->id,
                'data' => '2026-10-06',
                'hora_inicio' => '14:00',
                'hora_fim' => '15:00',
                'descricao' => 'Tentativa do admin',
            ])
            ->assertSessionHasErrors('hora_inicio');

        $this->assertSame(1, Agendamento::count());
    }
}
