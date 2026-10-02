<?php

namespace Tests\Feature\Admin;

use App\Models\Sala;
use App\Models\User;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidos(array $sobrescrever = []): array
    {
        return [
            'nome' => 'Sala 03',
            'descricao' => 'Sala com maca.',
            'capacidade' => 2,
            'cor' => '#2563EB',
            ...$sobrescrever,
        ];
    }

    public function test_profissional_nao_gerencia_salas(): void
    {
        $profissional = User::factory()->create();
        $sala = Sala::factory()->create();

        $this->actingAs($profissional)->get('/admin/salas')->assertForbidden();
        $this->actingAs($profissional)->post('/admin/salas', $this->dadosValidos())->assertForbidden();
        $this->actingAs($profissional)->patch("/admin/salas/{$sala->id}/status")->assertForbidden();

        $this->assertDatabaseMissing('salas', ['nome' => 'Sala 03']);
        $this->assertTrue($sala->fresh()->ativa);
    }

    public function test_lista_e_filtra_salas(): void
    {
        Sala::factory()->create(['nome' => 'Sala Azul']);
        Sala::factory()->inativa()->create(['nome' => 'Sala Fechada']);

        $this->actingAs($this->admin)->get('/admin/salas')
            ->assertOk()
            ->assertSee('Sala Azul')
            ->assertSee('Sala Fechada');

        $this->actingAs($this->admin)->get('/admin/salas?status=ativas')
            ->assertOk()
            ->assertSee('Sala Azul')
            ->assertDontSee('Sala Fechada');
    }

    public function test_admin_cadastra_sala(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/salas', $this->dadosValidos())
            ->assertRedirect('/admin/salas')
            ->assertSessionHas('sucesso');

        $this->assertDatabaseHas('salas', [
            'nome' => 'Sala 03',
            'capacidade' => 2,
            'cor' => '#2563eb',
            'ativa' => true,
        ]);
    }

    public function test_validacoes_da_sala(): void
    {
        Sala::factory()->create(['nome' => 'Sala 03']);

        $this->actingAs($this->admin)
            ->post('/admin/salas', $this->dadosValidos(['capacidade' => 0, 'cor' => 'azul']))
            ->assertSessionHasErrors(['nome', 'capacidade', 'cor']);

        $this->actingAs($this->admin)
            ->post('/admin/salas', $this->dadosValidos(['nome' => '']))
            ->assertSessionHasErrors('nome');
    }

    public function test_admin_edita_sala_mantendo_o_mesmo_nome(): void
    {
        $sala = Sala::factory()->create(['nome' => 'Sala 03']);

        $this->actingAs($this->admin)
            ->put("/admin/salas/{$sala->id}", $this->dadosValidos(['descricao' => 'Nova descrição', 'capacidade' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/salas');

        $sala->refresh();
        $this->assertSame('Nova descrição', $sala->descricao);
        $this->assertNull($sala->capacidade);
    }

    public function test_admin_desativa_e_reativa_sala(): void
    {
        $sala = Sala::factory()->create();

        $this->actingAs($this->admin)->patch("/admin/salas/{$sala->id}/status")->assertSessionHas('sucesso');
        $this->assertFalse($sala->fresh()->ativa);

        $this->actingAs($this->admin)->patch("/admin/salas/{$sala->id}/status");
        $this->assertTrue($sala->fresh()->ativa);
    }

    public function test_consulta_de_salas_mostra_apenas_ativas(): void
    {
        Sala::factory()->create(['nome' => 'Sala Disponível']);
        Sala::factory()->inativa()->create(['nome' => 'Sala em Reforma']);

        $this->actingAs(User::factory()->create())
            ->get('/salas')
            ->assertOk()
            ->assertSee('Sala Disponível')
            ->assertDontSee('Sala em Reforma');
    }

    public function test_sala_seeder_e_idempotente(): void
    {
        $this->seed(SalaSeeder::class);
        $this->seed(SalaSeeder::class);

        $this->assertSame(5, Sala::count());
    }
}
