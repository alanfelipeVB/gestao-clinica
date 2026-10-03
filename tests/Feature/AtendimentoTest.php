<?php

namespace Tests\Feature;

use App\Enums\SituacaoAtendimento;
use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Agora": segunda-feira, 05/10/2026 às 10:00.
 */
class AtendimentoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $ana;

    private Sala $sala;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-05 10:00:00'));

        $this->admin = User::factory()->admin()->create();
        $this->ana = User::factory()->create(['nome' => 'Ana Souza']);
        $this->sala = Sala::factory()->create();
    }

    private function agendamento(string $inicio, string $fim, ?User $dono = null): Agendamento
    {
        return Agendamento::factory()->periodo($inicio, $fim)
            ->create(['user_id' => ($dono ?? $this->ana)->id, 'sala_id' => $this->sala->id]);
    }

    public function test_novo_agendamento_comeca_pendente(): void
    {
        $this->actingAs($this->ana)->post('/agendamentos', [
            'sala_id' => $this->sala->id,
            'data' => '2026-10-06',
            'hora_inicio' => '14:00',
            'hora_fim' => '15:00',
            'descricao' => 'Atendimento',
        ])->assertSessionHasNoErrors();

        $this->assertSame(SituacaoAtendimento::Pendente, Agendamento::sole()->situacao);
    }

    public function test_profissional_marca_o_proprio_atendimento_como_realizado(): void
    {
        $agendamento = $this->agendamento('2026-10-05 08:00', '2026-10-05 09:00');

        $this->actingAs($this->ana)
            ->patch("/agendamentos/{$agendamento->id}/atendimento", ['situacao' => 'realizado'])
            ->assertSessionHas('sucesso');

        $agendamento->refresh();
        $this->assertSame(SituacaoAtendimento::Realizado, $agendamento->situacao);
        $this->assertSame($this->ana->id, $agendamento->situacao_marcada_por);
        $this->assertNotNull($agendamento->situacao_marcada_em);
    }

    public function test_marca_nao_realizado_com_observacao_e_pode_corrigir(): void
    {
        $agendamento = $this->agendamento('2026-10-05 08:00', '2026-10-05 09:00');

        $this->actingAs($this->ana)->patch("/agendamentos/{$agendamento->id}/atendimento", [
            'situacao' => 'nao_realizado',
            'observacao_atendimento' => 'Paciente faltou',
        ]);

        $this->assertSame(SituacaoAtendimento::NaoRealizado, $agendamento->fresh()->situacao);
        $this->assertSame('Paciente faltou', $agendamento->fresh()->observacao_atendimento);

        // Correção dentro do prazo.
        $this->actingAs($this->ana)->patch("/agendamentos/{$agendamento->id}/atendimento", ['situacao' => 'realizado']);
        $this->assertSame(SituacaoAtendimento::Realizado, $agendamento->fresh()->situacao);
    }

    public function test_em_andamento_ja_pode_ser_marcado(): void
    {
        $agendamento = $this->agendamento('2026-10-05 09:30', '2026-10-05 11:00');

        $this->actingAs($this->ana)
            ->patch("/agendamentos/{$agendamento->id}/atendimento", ['situacao' => 'realizado'])
            ->assertSessionHas('sucesso');
    }

    public function test_nao_marca_antes_do_inicio(): void
    {
        $agendamento = $this->agendamento('2026-10-06 14:00', '2026-10-06 15:00');

        $this->actingAs($this->ana)
            ->patch("/agendamentos/{$agendamento->id}/atendimento", ['situacao' => 'realizado'])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch("/agendamentos/{$agendamento->id}/atendimento", ['situacao' => 'realizado'])
            ->assertForbidden();

        $this->assertSame(SituacaoAtendimento::Pendente, $agendamento->fresh()->situacao);
    }

    public function test_nao_marca_agendamento_cancelado(): void
    {
        $cancelado = Agendamento::factory()->cancelado()->periodo('2026-10-05 08:00', '2026-10-05 09:00')
            ->create(['user_id' => $this->ana->id]);

        $this->actingAs($this->admin)
            ->patch("/agendamentos/{$cancelado->id}/atendimento", ['situacao' => 'realizado'])
            ->assertForbidden();
    }

    public function test_profissional_nao_marca_atendimento_de_outro(): void
    {
        $doBruno = $this->agendamento('2026-10-05 08:00', '2026-10-05 09:00', User::factory()->create());

        $this->actingAs($this->ana)
            ->patch("/agendamentos/{$doBruno->id}/atendimento", ['situacao' => 'realizado'])
            ->assertForbidden();
    }

    public function test_apos_7_dias_apenas_admin_altera(): void
    {
        // Terminou em 27/09 às 09:00 → prazo do profissional até 04/10 às 09:00.
        $antigo = $this->agendamento('2026-09-27 08:00', '2026-09-27 09:00');

        $this->actingAs($this->ana)
            ->patch("/agendamentos/{$antigo->id}/atendimento", ['situacao' => 'realizado'])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch("/agendamentos/{$antigo->id}/atendimento", ['situacao' => 'nao_realizado'])
            ->assertSessionHas('sucesso');

        $antigo->refresh();
        $this->assertSame(SituacaoAtendimento::NaoRealizado, $antigo->situacao);
        $this->assertSame($this->admin->id, $antigo->situacao_marcada_por);
    }

    public function test_situacao_invalida_e_rejeitada(): void
    {
        $agendamento = $this->agendamento('2026-10-05 08:00', '2026-10-05 09:00');

        foreach (['pendente', 'outro', ''] as $valor) {
            $this->actingAs($this->ana)
                ->patch("/agendamentos/{$agendamento->id}/atendimento", ['situacao' => $valor])
                ->assertSessionHasErrors('situacao');
        }
    }

    public function test_detalhes_exibem_botoes_somente_quando_permitido(): void
    {
        $passado = $this->agendamento('2026-10-05 08:00', '2026-10-05 09:00');
        $futuro = $this->agendamento('2026-10-06 14:00', '2026-10-06 15:00');

        $this->actingAs($this->ana)->get("/agendamentos/{$passado->id}")
            ->assertOk()
            ->assertSee('Pendente de confirmação')
            ->assertSee('Não realizado');

        $this->actingAs($this->ana)->get("/agendamentos/{$futuro->id}")
            ->assertOk()
            ->assertDontSee('Pendente de confirmação');
    }

    public function test_dashboard_do_profissional_lista_pendentes(): void
    {
        $this->agendamento('2026-10-05 08:00', '2026-10-05 09:00');
        Agendamento::factory()->realizado()->periodo('2026-10-02 08:00', '2026-10-02 09:00')
            ->create(['user_id' => $this->ana->id]);

        $this->actingAs($this->ana)->get('/dashboard')
            ->assertOk()
            ->assertSee('Atendimentos para confirmar');

        $this->actingAs($this->admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('aguarda confirmação');
    }

    public function test_filtro_por_situacao_na_lista(): void
    {
        $this->agendamento('2026-10-05 08:00', '2026-10-05 09:00')->update(['descricao' => 'Pendente aqui']);
        Agendamento::factory()->realizado()->periodo('2026-10-02 08:00', '2026-10-02 09:00')
            ->create(['user_id' => $this->ana->id, 'descricao' => 'Já realizado']);

        $this->actingAs($this->admin)->get('/agendamentos?periodo=todos&situacao=pendente')
            ->assertOk()
            ->assertSee('Pendente aqui')
            ->assertDontSee('Já realizado');
    }
}
