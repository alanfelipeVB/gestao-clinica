<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_ve_secao_de_administracao_e_seu_nome(): void
    {
        $admin = User::factory()->admin()->create(['nome' => 'Maria Admin']);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Gestão Clínica')
            ->assertSee('Maria Admin')
            ->assertSee('Administração')
            ->assertSee('Profissionais')
            ->assertSee('Salas')
            ->assertSee('Sair');
    }

    public function test_profissional_nao_ve_secao_de_administracao(): void
    {
        $profissional = User::factory()->create(['nome' => 'João Profissional']);

        $this->actingAs($profissional)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('João Profissional')
            ->assertSee('Agenda')
            ->assertDontSee('Administração');
    }
}
