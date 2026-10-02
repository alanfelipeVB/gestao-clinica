<?php

namespace Tests\Feature;

use App\Enums\StatusAgendamento;
use App\Events\AgendamentoAtualizado;
use App\Events\AgendamentoCancelado;
use App\Events\AgendamentoCriado;
use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use App\Services\ConfiguracaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AgendamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $profissional;

    private Sala $sala;

    protected function setUp(): void
    {
        parent::setUp();

        // "Agora" fixo: segunda-feira, 05/10/2026 às 10:00.
        $this->travelTo(Carbon::parse('2026-10-05 10:00:00'));

        $this->admin = User::factory()->admin()->create();
        $this->profissional = User::factory()->create(['nome' => 'Ana Souza']);
        $this->sala = Sala::factory()->create(['nome' => 'Sala 01']);
    }

    /**
     * @return array<string, mixed>
     */
    private function dados(array $sobrescrever = []): array
    {
        return [
            'sala_id' => $this->sala->id,
            'data' => '2026-10-06',
            'hora_inicio' => '14:00',
            'hora_fim' => '15:30',
            'descricao' => 'Atendimento e avaliação de paciente.',
            ...$sobrescrever,
        ];
    }

    private function agendamentoDe(User $profissional, string $inicio = '2026-10-06 14:00', string $fim = '2026-10-06 15:00'): Agendamento
    {
        return Agendamento::factory()
            ->periodo($inicio, $fim)
            ->create(['user_id' => $profissional->id, 'sala_id' => $this->sala->id]);
    }

    // ------------------------------------------------------------------
    // Criação
    // ------------------------------------------------------------------

    public function test_profissional_cria_agendamento_para_si(): void
    {
        Event::fake([AgendamentoCriado::class]);

        $outro = User::factory()->create();

        // Mesmo enviando user_id de outro profissional, o agendamento é do próprio usuário.
        $resposta = $this->actingAs($this->profissional)
            ->post('/agendamentos', $this->dados(['user_id' => $outro->id]));

        $agendamento = Agendamento::sole();

        $resposta->assertRedirect("/agendamentos/{$agendamento->id}")->assertSessionHas('sucesso');

        $this->assertSame($this->profissional->id, $agendamento->user_id);
        $this->assertSame($this->profissional->id, $agendamento->criado_por);
        $this->assertSame('2026-10-06 14:00:00', $agendamento->inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-06 15:30:00', $agendamento->fim->format('Y-m-d H:i:s'));
        $this->assertSame(StatusAgendamento::Agendado, $agendamento->status);

        Event::assertDispatched(AgendamentoCriado::class);
    }

    public function test_admin_cria_agendamento_em_nome_de_profissional(): void
    {
        $this->actingAs($this->admin)
            ->post('/agendamentos', $this->dados(['user_id' => $this->profissional->id]))
            ->assertSessionHasNoErrors();

        $agendamento = Agendamento::sole();
        $this->assertSame($this->profissional->id, $agendamento->user_id);
        $this->assertSame($this->admin->id, $agendamento->criado_por);
    }

    public function test_validacao_de_campos_obrigatorios_e_formato(): void
    {
        $this->actingAs($this->profissional)
            ->post('/agendamentos', [])
            ->assertSessionHasErrors(['sala_id', 'data', 'hora_inicio', 'hora_fim', 'descricao']);

        $this->actingAs($this->profissional)
            ->post('/agendamentos', $this->dados(['hora_inicio' => '15:00', 'hora_fim' => '14:00']))
            ->assertSessionHasErrors('hora_fim');

        $this->actingAs($this->admin)
            ->post('/agendamentos', $this->dados())
            ->assertSessionHasErrors('user_id');

        $this->assertSame(0, Agendamento::count());
    }

    public function test_nao_permite_agendar_no_passado(): void
    {
        $this->actingAs($this->profissional)
            ->post('/agendamentos', $this->dados(['data' => '2026-10-05', 'hora_inicio' => '09:00', 'hora_fim' => '11:00']))
            ->assertSessionHasErrors(['hora_inicio' => 'Não é possível agendar em um horário que já passou.']);

        $this->assertSame(0, Agendamento::count());
    }

    public function test_horarios_devem_ser_multiplos_de_15_minutos(): void
    {
        $this->actingAs($this->profissional)
            ->post('/agendamentos', $this->dados(['hora_inicio' => '14:10']))
            ->assertSessionHasErrors('hora_inicio');

        $this->assertSame(0, Agendamento::count());
    }

    public function test_respeita_antecedencia_maxima(): void
    {
        // Hoje 05/10 + 30 dias = 04/11 é o último dia permitido.
        $this->actingAs($this->profissional)
            ->post('/agendamentos', $this->dados(['data' => '2026-11-05']))
            ->assertSessionHasErrors('data');

        $this->actingAs($this->profissional)
            ->post('/agendamentos', $this->dados(['data' => '2026-11-04']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Agendamento::count());
    }

    public function test_antecedencia_maxima_e_configuravel(): void
    {
        app(ConfiguracaoService::class)->salvar([ConfiguracaoService::ANTECEDENCIA_MAXIMA_DIAS => 60]);

        $this->actingAs($this->profissional)
            ->post('/agendamentos', $this->dados(['data' => '2026-11-20']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Agendamento::count());
    }

    public function test_nao_permite_agendar_sala_inativa(): void
    {
        $this->sala->update(['ativa' => false]);

        $this->actingAs($this->profissional)
            ->post('/agendamentos', $this->dados())
            ->assertSessionHasErrors('sala_id');

        $this->assertSame(0, Agendamento::count());
    }

    public function test_admin_nao_agenda_para_profissional_inativo(): void
    {
        $inativo = User::factory()->inativo()->create();

        $this->actingAs($this->admin)
            ->post('/agendamentos', $this->dados(['user_id' => $inativo->id]))
            ->assertSessionHasErrors('user_id');

        $this->assertSame(0, Agendamento::count());
    }

    // ------------------------------------------------------------------
    // Visualização
    // ------------------------------------------------------------------

    public function test_profissional_lista_apenas_os_proprios_agendamentos(): void
    {
        $this->agendamentoDe($this->profissional)->update(['descricao' => 'Meu atendimento']);
        $this->agendamentoDe(User::factory()->create(), '2026-10-07 09:00', '2026-10-07 10:00')
            ->update(['descricao' => 'Atendimento de outra pessoa']);

        $this->actingAs($this->profissional)->get('/agendamentos')
            ->assertOk()
            ->assertSee('Meu atendimento')
            ->assertDontSee('Atendimento de outra pessoa');

        $this->actingAs($this->admin)->get('/agendamentos')
            ->assertOk()
            ->assertSee('Meu atendimento')
            ->assertSee('Atendimento de outra pessoa');
    }

    public function test_profissional_nao_ve_detalhes_de_agendamento_de_outro(): void
    {
        $alheio = $this->agendamentoDe(User::factory()->create());

        $this->actingAs($this->profissional)->get("/agendamentos/{$alheio->id}")->assertForbidden();
        $this->actingAs($this->admin)->get("/agendamentos/{$alheio->id}")->assertOk();
    }

    // ------------------------------------------------------------------
    // Edição
    // ------------------------------------------------------------------

    public function test_profissional_edita_o_proprio_agendamento_futuro(): void
    {
        Event::fake([AgendamentoAtualizado::class]);

        $agendamento = $this->agendamentoDe($this->profissional);

        $this->actingAs($this->profissional)
            ->put("/agendamentos/{$agendamento->id}", $this->dados(['hora_inicio' => '16:00', 'hora_fim' => '17:00']))
            ->assertSessionHasNoErrors()
            ->assertRedirect("/agendamentos/{$agendamento->id}");

        $this->assertSame('16:00', $agendamento->fresh()->inicio->format('H:i'));
        Event::assertDispatched(AgendamentoAtualizado::class);
    }

    public function test_profissional_nao_edita_agendamento_de_outro(): void
    {
        $alheio = $this->agendamentoDe(User::factory()->create());

        $this->actingAs($this->profissional)
            ->put("/agendamentos/{$alheio->id}", $this->dados())
            ->assertForbidden();
    }

    public function test_nao_edita_agendamento_que_ja_comecou_ou_cancelado(): void
    {
        $emAndamento = $this->agendamentoDe($this->profissional, '2026-10-05 09:30', '2026-10-05 11:00');
        $cancelado = Agendamento::factory()->cancelado()->periodo('2026-10-08 14:00', '2026-10-08 15:00')
            ->create(['user_id' => $this->profissional->id]);

        $this->actingAs($this->profissional)->get("/agendamentos/{$emAndamento->id}/edit")->assertForbidden();
        $this->actingAs($this->admin)->put("/agendamentos/{$emAndamento->id}", $this->dados(['user_id' => $this->profissional->id]))->assertForbidden();
        $this->actingAs($this->profissional)->put("/agendamentos/{$cancelado->id}", $this->dados())->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Cancelamento
    // ------------------------------------------------------------------

    public function test_profissional_cancela_o_proprio_agendamento_antes_do_inicio(): void
    {
        Event::fake([AgendamentoCancelado::class]);

        $agendamento = $this->agendamentoDe($this->profissional);

        $this->actingAs($this->profissional)
            ->patch("/agendamentos/{$agendamento->id}/cancelar", ['motivo_cancelamento' => 'Paciente remarcou'])
            ->assertRedirect("/agendamentos/{$agendamento->id}")
            ->assertSessionHas('sucesso');

        $agendamento->refresh();
        $this->assertSame(StatusAgendamento::Cancelado, $agendamento->status);
        $this->assertSame($this->profissional->id, $agendamento->cancelado_por);
        $this->assertSame('Paciente remarcou', $agendamento->motivo_cancelamento);
        $this->assertNotNull($agendamento->cancelado_em);

        Event::assertDispatched(AgendamentoCancelado::class);
    }

    public function test_profissional_nao_cancela_apos_o_inicio_mas_admin_pode(): void
    {
        $emAndamento = $this->agendamentoDe($this->profissional, '2026-10-05 09:30', '2026-10-05 11:00');

        $this->actingAs($this->profissional)
            ->patch("/agendamentos/{$emAndamento->id}/cancelar")
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch("/agendamentos/{$emAndamento->id}/cancelar")
            ->assertSessionHas('sucesso');

        $this->assertSame(StatusAgendamento::Cancelado, $emAndamento->fresh()->status);
    }

    public function test_profissional_nao_cancela_agendamento_de_outro(): void
    {
        $alheio = $this->agendamentoDe(User::factory()->create());

        $this->actingAs($this->profissional)
            ->patch("/agendamentos/{$alheio->id}/cancelar")
            ->assertForbidden();

        $this->assertSame(StatusAgendamento::Agendado, $alheio->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Desativação de sala / profissional com agendamentos futuros
    // ------------------------------------------------------------------

    public function test_desativar_sala_com_agendamentos_futuros_pede_confirmacao(): void
    {
        $this->agendamentoDe($this->profissional);

        $this->actingAs($this->admin)
            ->patch("/admin/salas/{$this->sala->id}/status")
            ->assertRedirect("/admin/salas/{$this->sala->id}/desativar");

        $this->assertTrue($this->sala->fresh()->ativa);

        $this->actingAs($this->admin)
            ->get("/admin/salas/{$this->sala->id}/desativar")
            ->assertOk()
            ->assertSee('1 agendamento futuro')
            ->assertSee('Ana Souza');
    }

    public function test_desativar_sala_cancelando_agendamentos_futuros(): void
    {
        $futuro = $this->agendamentoDe($this->profissional);
        $passado = $this->agendamentoDe($this->profissional, '2026-10-01 14:00', '2026-10-01 15:00');

        $this->actingAs($this->admin)
            ->patch("/admin/salas/{$this->sala->id}/status", ['acao_agendamentos' => 'cancelar'])
            ->assertRedirect('/admin/salas');

        $this->assertFalse($this->sala->fresh()->ativa);
        $this->assertSame(StatusAgendamento::Cancelado, $futuro->fresh()->status);
        $this->assertSame('Sala desativada.', $futuro->fresh()->motivo_cancelamento);
        $this->assertSame(StatusAgendamento::Agendado, $passado->fresh()->status);
    }

    public function test_desativar_sala_mantendo_agendamentos(): void
    {
        $futuro = $this->agendamentoDe($this->profissional);

        $this->actingAs($this->admin)
            ->patch("/admin/salas/{$this->sala->id}/status", ['acao_agendamentos' => 'manter']);

        $this->assertFalse($this->sala->fresh()->ativa);
        $this->assertSame(StatusAgendamento::Agendado, $futuro->fresh()->status);
    }

    public function test_desativar_profissional_com_agendamentos_futuros(): void
    {
        $futuro = $this->agendamentoDe($this->profissional);

        $this->actingAs($this->admin)
            ->patch("/admin/profissionais/{$this->profissional->id}/status")
            ->assertRedirect("/admin/profissionais/{$this->profissional->id}/desativar");

        $this->assertTrue($this->profissional->fresh()->ativo);

        $this->actingAs($this->admin)
            ->patch("/admin/profissionais/{$this->profissional->id}/status", ['acao_agendamentos' => 'cancelar'])
            ->assertRedirect('/admin/profissionais');

        $this->assertFalse($this->profissional->fresh()->ativo);
        $this->assertSame(StatusAgendamento::Cancelado, $futuro->fresh()->status);
    }

    public function test_desativar_sem_agendamentos_futuros_e_direto(): void
    {
        $this->actingAs($this->admin)
            ->patch("/admin/salas/{$this->sala->id}/status")
            ->assertRedirect('/admin/salas');

        $this->assertFalse($this->sala->fresh()->ativa);
    }
}
