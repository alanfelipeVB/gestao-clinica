<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use App\Services\RelatorioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Agora": 15/10/2026 às 12:00. Mês do relatório: outubro/2026.
 *
 * Ana (Sala 01 e 02): 2 realizados (60 + 90 min), 1 não realizado, 1 pendente,
 *                     1 futuro, 1 cancelado; + 1 realizado em setembro e 1 em novembro (fora do mês).
 * Bruno (Sala 02):    1 realizado (30 min).
 * Carla:              ativa, sem agendamentos.
 */
class RelatorioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $ana;

    private User $bruno;

    private Sala $sala01;

    private Sala $sala02;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

        $this->admin = User::factory()->admin()->create(['nome' => 'Admin']);
        $this->ana = User::factory()->create(['nome' => 'Ana Souza']);
        $this->bruno = User::factory()->create(['nome' => 'Bruno Lima']);
        User::factory()->create(['nome' => 'Carla Mendes']);
        User::factory()->inativo()->create(['nome' => 'Inativo Sem Agenda']);

        $this->sala01 = Sala::factory()->create(['nome' => 'Sala 01']);
        $this->sala02 = Sala::factory()->create(['nome' => 'Sala 02']);

        $criar = fn (User $u, Sala $s, string $i, string $f) => Agendamento::factory()->periodo($i, $f)
            ->state(['user_id' => $u->id, 'sala_id' => $s->id]);

        $criar($this->ana, $this->sala01, '2026-10-01 08:00', '2026-10-01 09:00')->realizado()->create();
        $criar($this->ana, $this->sala02, '2026-10-02 08:00', '2026-10-02 09:30')->realizado()->create();
        $criar($this->ana, $this->sala01, '2026-10-05 08:00', '2026-10-05 09:00')->naoRealizado()->create();
        $criar($this->ana, $this->sala01, '2026-10-14 08:00', '2026-10-14 09:00')->create();                 // pendente
        $criar($this->ana, $this->sala01, '2026-10-20 08:00', '2026-10-20 09:00')->create();                 // futuro
        $criar($this->ana, $this->sala01, '2026-10-21 08:00', '2026-10-21 09:00')->cancelado()->create();
        $criar($this->ana, $this->sala01, '2026-09-30 08:00', '2026-09-30 09:00')->realizado()->create();     // setembro
        $criar($this->ana, $this->sala01, '2026-11-01 08:00', '2026-11-01 09:00')->create();                 // novembro
        $criar($this->bruno, $this->sala02, '2026-10-03 10:00', '2026-10-03 10:30')->realizado()->create();
    }

    public function test_numeros_por_profissional_no_mes(): void
    {
        $linhas = app(RelatorioService::class)
            ->mensal(Carbon::parse('2026-10-01'))
            ->keyBy(fn ($l) => $l['profissional']->nome);

        // Profissionais ativos sem agendamento aparecem; inativos sem agenda e admins sem agenda, não.
        $this->assertSame(['Ana Souza', 'Bruno Lima', 'Carla Mendes'], $linhas->keys()->all());

        $ana = $linhas['Ana Souza'];
        $this->assertSame(5, $ana['agendados']);
        $this->assertSame(2, $ana['realizados']);
        $this->assertSame(1, $ana['nao_realizados']);
        $this->assertSame(1, $ana['pendentes']);
        $this->assertSame(1, $ana['futuros']);
        $this->assertSame(1, $ana['cancelados']);
        $this->assertSame(150, $ana['minutos_realizados']);
        $this->assertSame(66.7, $ana['taxa']);

        $this->assertSame(1, $linhas['Bruno Lima']['realizados']);
        $this->assertSame(100.0, $linhas['Bruno Lima']['taxa']);

        $this->assertSame(0, $linhas['Carla Mendes']['agendados']);
        $this->assertNull($linhas['Carla Mendes']['taxa']);
    }

    public function test_totais_e_filtro_por_sala(): void
    {
        $servico = app(RelatorioService::class);

        $totais = $servico->totais($servico->mensal(Carbon::parse('2026-10-01')));
        $this->assertSame(3, $totais['realizados']);
        $this->assertSame(180, $totais['minutos_realizados']);
        $this->assertSame(75.0, $totais['taxa']);

        $sala02 = $servico->mensal(Carbon::parse('2026-10-01'), $this->sala02->id)->keyBy(fn ($l) => $l['profissional']->nome);
        $this->assertSame(1, $sala02['Ana Souza']['realizados']);
        $this->assertSame(0, $sala02['Ana Souza']['nao_realizados']);
        $this->assertSame(1, $sala02['Bruno Lima']['realizados']);
    }

    public function test_outro_mes(): void
    {
        $setembro = app(RelatorioService::class)->mensal(Carbon::parse('2026-09-01'))
            ->keyBy(fn ($l) => $l['profissional']->nome);

        $this->assertSame(1, $setembro['Ana Souza']['realizados']);
        $this->assertSame(0, $setembro['Bruno Lima']['realizados']);
    }

    public function test_admin_ve_todos_os_profissionais(): void
    {
        $this->actingAs($this->admin)->get('/relatorio?mes=2026-10')
            ->assertOk()
            ->assertSee('Outubro de 2026')
            ->assertSee('Ana Souza')
            ->assertSee('Bruno Lima')
            ->assertSee('Carla Mendes')
            ->assertSee('Total')
            ->assertSee('75,0%');
    }

    public function test_profissional_ve_apenas_os_proprios_numeros(): void
    {
        $this->actingAs($this->ana)->get('/relatorio?mes=2026-10')
            ->assertOk()
            ->assertSee('Ana Souza')
            ->assertSee('Meus agendamentos no mês')
            ->assertSee('66,7%')
            ->assertDontSee('Bruno Lima')
            ->assertDontSee('Carla Mendes');
    }

    public function test_exportacao_csv(): void
    {
        $resposta = $this->actingAs($this->admin)->get('/relatorio/exportar?mes=2026-10')->assertOk();

        $this->assertStringContainsString('relatorio-atendimentos-2026-10.csv', $resposta->headers->get('content-disposition'));

        $csv = $resposta->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Profissional;Profissão;Agendados;Realizados', $csv);
        $this->assertStringContainsString('"Ana Souza";', $csv);
        $this->assertStringContainsString(';2,5;66,7', $csv);
        $this->assertStringContainsString('Total;', $csv);

        $doProfissional = $this->actingAs($this->bruno)->get('/relatorio/exportar?mes=2026-10')->streamedContent();
        $this->assertStringContainsString('"Bruno Lima"', $doProfissional);
        $this->assertStringNotContainsString('Ana Souza', $doProfissional);
    }

    public function test_mes_invalido_e_rejeitado(): void
    {
        $this->actingAs($this->admin)->get('/relatorio?mes=2026-13')->assertSessionHasErrors('mes');
    }
}
