<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Agora": segunda-feira, 05/10/2026 às 10:00.
 *
 * Sala 01: Ana 09:30–11:00 (em andamento) → ocupada
 * Sala 02: Bruno 14:00–15:00 (mais tarde) → livre, com próximo
 * Sala 03: nada hoje → livre
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $ana;

    private User $bruno;

    private Sala $sala01;

    private Sala $sala02;

    private Sala $sala03;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-05 10:00:00'));

        $this->admin = User::factory()->admin()->create();
        $this->ana = User::factory()->create(['nome' => 'Ana Souza']);
        $this->bruno = User::factory()->create(['nome' => 'Bruno Lima']);
        User::factory()->inativo()->create();

        $this->sala01 = Sala::factory()->create(['nome' => 'Sala 01']);
        $this->sala02 = Sala::factory()->create(['nome' => 'Sala 02']);
        $this->sala03 = Sala::factory()->create(['nome' => 'Sala 03']);
        Sala::factory()->inativa()->create();

        $this->criar($this->ana, $this->sala01, '2026-10-05 09:30', '2026-10-05 11:00', 'Atendimento em andamento');
        $this->criar($this->bruno, $this->sala02, '2026-10-05 14:00', '2026-10-05 15:00', 'Sessão da tarde');
        $this->criar($this->ana, $this->sala02, '2026-10-05 08:00', '2026-10-05 09:00', 'Já terminou');
        $this->criar($this->ana, $this->sala01, '2026-10-07 09:00', '2026-10-07 10:00', 'Quarta da Ana');
        $this->criar($this->bruno, $this->sala03, '2026-10-08 09:00', '2026-10-08 10:00', 'Quinta do Bruno');
        $this->criar($this->ana, $this->sala03, '2026-10-25 09:00', '2026-10-25 10:00', 'Daqui a 20 dias');
        Agendamento::factory()->cancelado()->periodo('2026-10-05 16:00', '2026-10-05 17:00')
            ->create(['user_id' => $this->ana->id, 'sala_id' => $this->sala03->id, 'descricao' => 'Cancelado hoje']);
    }

    private function criar(User $user, Sala $sala, string $inicio, string $fim, string $descricao): Agendamento
    {
        return Agendamento::factory()->periodo($inicio, $fim)
            ->create(['user_id' => $user->id, 'sala_id' => $sala->id, 'descricao' => $descricao]);
    }

    public function test_totais_do_admin(): void
    {
        $totais = app(DashboardService::class)->totais();

        $this->assertSame([
            'profissionais' => 2,     // ativos com perfil profissional
            'salas' => 3,             // ativas
            'agendamentos_hoje' => 3, // inclui o que já terminou; exclui cancelado
            'salas_ocupadas' => 1,
        ], $totais);
    }

    public function test_situacao_das_salas(): void
    {
        $situacao = app(DashboardService::class)->situacaoDasSalas()->keyBy(fn ($s) => $s['sala']->nome);

        $this->assertSame(['Sala 01', 'Sala 02', 'Sala 03'], $situacao->keys()->all());

        $this->assertSame('Ana Souza', $situacao['Sala 01']['atual']->profissional->nome);

        $this->assertNull($situacao['Sala 02']['atual']);
        $this->assertSame('14:00', $situacao['Sala 02']['proximo']->inicio->format('H:i'));

        $this->assertNull($situacao['Sala 03']['atual']);
        $this->assertNull($situacao['Sala 03']['proximo']);
    }

    public function test_dashboard_do_admin(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Agora nas salas')
            ->assertSee('Ocupada')
            ->assertSee('Ana Souza até 11:00')
            ->assertSee('Próximo: 14:00 – 15:00 · Bruno Lima')
            ->assertSee('Sem mais agendamentos hoje.')
            ->assertSee('Agendamentos de hoje')
            ->assertSee('Próximos 7 dias');
    }

    public function test_listas_de_hoje_e_proximos_do_admin(): void
    {
        $servico = app(DashboardService::class);

        $this->assertSame(
            ['Já terminou', 'Atendimento em andamento', 'Sessão da tarde'],
            $servico->agendamentosDeHoje()->pluck('descricao')->all(),
        );

        $this->assertSame(
            ['Quarta da Ana', 'Quinta do Bruno'],
            $servico->proximosAgendamentos()->pluck('descricao')->all(),
        );
    }

    public function test_dashboard_do_profissional_mostra_apenas_os_proprios(): void
    {
        $resposta = $this->actingAs($this->ana)->get('/dashboard')
            ->assertOk()
            ->assertSee('Novo agendamento')
            ->assertSee('Meus agendamentos de hoje')
            ->assertSee('Atendimento em andamento')
            ->assertSee('Quarta da Ana')
            ->assertSee('Daqui a 20 dias')
            ->assertDontSee('Sessão da tarde')
            ->assertDontSee('Quinta do Bruno')
            ->assertDontSee('Cancelado hoje')
            ->assertDontSee('Agora nas salas');
    }
}
