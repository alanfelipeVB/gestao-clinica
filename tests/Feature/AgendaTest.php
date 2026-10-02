<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private User $bruno;

    private Sala $sala01;

    private Sala $sala02;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-05 10:00:00'));

        $this->ana = User::factory()->create(['nome' => 'Ana Souza']);
        $this->bruno = User::factory()->create(['nome' => 'Bruno Lima']);
        $this->sala01 = Sala::factory()->create(['nome' => 'Sala 01', 'cor' => '#0f766e']);
        $this->sala02 = Sala::factory()->create(['nome' => 'Sala 02', 'cor' => '#2563eb']);

        Agendamento::factory()->periodo('2026-10-06 14:00', '2026-10-06 15:00')->create([
            'user_id' => $this->ana->id, 'sala_id' => $this->sala01->id, 'descricao' => 'Avaliação da Ana',
        ]);
        Agendamento::factory()->periodo('2026-10-07 09:00', '2026-10-07 10:00')->create([
            'user_id' => $this->bruno->id, 'sala_id' => $this->sala02->id, 'descricao' => 'Sessão do Bruno',
        ]);
        Agendamento::factory()->cancelado()->periodo('2026-10-08 09:00', '2026-10-08 10:00')->create([
            'user_id' => $this->ana->id, 'sala_id' => $this->sala01->id, 'descricao' => 'Cancelado',
        ]);
        // Fora da semana consultada.
        Agendamento::factory()->periodo('2026-10-20 09:00', '2026-10-20 10:00')->create([
            'user_id' => $this->ana->id, 'sala_id' => $this->sala01->id, 'descricao' => 'Semana seguinte',
        ]);
    }

    private function eventos(User $user, array $parametros = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->getJson('/agenda/eventos?'.http_build_query([
            'start' => '2026-10-04T00:00:00-03:00',
            'end' => '2026-10-11T00:00:00-03:00',
            ...$parametros,
        ]));
    }

    public function test_tela_da_agenda_exige_login_e_renderiza(): void
    {
        $this->get('/agenda')->assertRedirect('/login');
        $this->getJson('/agenda/eventos')->assertUnauthorized();

        $this->actingAs($this->ana)->get('/agenda')
            ->assertOk()
            ->assertSee('id="calendario"', false)
            ->assertSee('Sala 01')
            ->assertSee('Bruno Lima');
    }

    public function test_retorna_apenas_agendamentos_ativos_do_periodo(): void
    {
        $resposta = $this->eventos($this->ana)->assertOk();

        $resposta->assertJsonCount(2);
        $resposta->assertJsonFragment(['start' => '2026-10-06T14:00:00', 'end' => '2026-10-06T15:00:00']);
        $resposta->assertJsonMissing(['descricao' => 'Cancelado']);
        $resposta->assertJsonMissing(['descricao' => 'Semana seguinte']);
    }

    public function test_filtra_por_sala_e_por_profissional(): void
    {
        $this->eventos($this->ana, ['sala_id' => $this->sala02->id])
            ->assertJsonCount(1)
            ->assertJsonPath('0.extendedProps.sala', 'Sala 02');

        $this->eventos($this->ana, ['user_id' => $this->ana->id])
            ->assertJsonCount(1)
            ->assertJsonPath('0.extendedProps.profissional', 'Ana Souza');
    }

    public function test_profissional_ve_colegas_apenas_como_ocupado(): void
    {
        $eventos = collect($this->eventos($this->ana)->json())->keyBy('extendedProps.profissional');

        // Próprio agendamento: detalhes completos.
        $proprio = $eventos['Ana Souza'];
        $this->assertSame('Ana Souza', $proprio['title']);
        $this->assertSame('Avaliação da Ana', $proprio['extendedProps']['descricao']);
        $this->assertNotNull($proprio['extendedProps']['url_detalhes']);
        $this->assertNotNull($proprio['extendedProps']['url_cancelar']);
        $this->assertSame('#0f766e', $proprio['backgroundColor']);

        // Agendamento do colega: sem descrição e sem links.
        $alheio = $eventos['Bruno Lima'];
        $this->assertSame('Ocupado — Bruno Lima', $alheio['title']);
        $this->assertNull($alheio['extendedProps']['descricao']);
        $this->assertNull($alheio['extendedProps']['url_detalhes']);
        $this->assertNull($alheio['extendedProps']['url_editar']);
        $this->assertNull($alheio['extendedProps']['url_cancelar']);
        $this->assertContains('evento-ocupado', $alheio['classNames']);
    }

    public function test_admin_ve_detalhes_de_todos(): void
    {
        $admin = User::factory()->admin()->create();

        $eventos = collect($this->eventos($admin)->json())->keyBy('extendedProps.profissional');

        $this->assertSame('Sessão do Bruno', $eventos['Bruno Lima']['extendedProps']['descricao']);
        $this->assertNotNull($eventos['Bruno Lima']['extendedProps']['url_editar']);
        $this->assertNotNull($eventos['Ana Souza']['extendedProps']['url_cancelar']);
    }

    public function test_valida_parametros_do_periodo(): void
    {
        $this->actingAs($this->ana)->getJson('/agenda/eventos')->assertUnprocessable();

        $this->eventos($this->ana, ['start' => '2026-10-01', 'end' => '2026-12-31'])->assertUnprocessable();
    }
}
