<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_renderiza_menu_geral(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Gestão Clínica')
            ->assertSee('Dashboard')
            ->assertSee('Agenda')
            ->assertDontSee('Administração');
    }

    public function test_admin_ve_secao_de_administracao_e_seu_nome(): void
    {
        $admin = User::factory()->admin()->create(['nome' => 'Maria Admin']);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Maria Admin')
            ->assertSee('Administração')
            ->assertSee('Profissionais')
            ->assertSee('Salas');
    }

    public function test_profissional_nao_ve_secao_de_administracao(): void
    {
        $profissional = User::factory()->create(['nome' => 'João Profissional']);

        $this->actingAs($profissional)
            ->get('/')
            ->assertOk()
            ->assertSee('João Profissional')
            ->assertDontSee('Administração');
    }
}
