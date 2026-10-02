<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_de_perfil_exige_login_e_e_exibida(): void
    {
        $this->get('/perfil')->assertRedirect('/login');

        $user = User::factory()->create(['nome' => 'Ana Souza']);

        $this->actingAs($user)->get('/perfil')
            ->assertOk()
            ->assertSee('Meu perfil')
            ->assertSee('Ana Souza');
    }

    public function test_usuario_atualiza_os_proprios_dados_sem_alterar_email_e_perfil(): void
    {
        $user = User::factory()->create(['email' => 'ana@clinica.test']);

        $this->actingAs($user)
            ->put('/perfil', [
                'nome' => 'Ana Souza Lima',
                'telefone' => '(11) 97777-6666',
                'instagram' => '@ana.lima',
                'profissao' => 'Fisioterapeuta',
                'email' => 'outro@clinica.test',
                'perfil' => 'admin',
            ])
            ->assertRedirect('/perfil')
            ->assertSessionHas('sucesso');

        $user->refresh();
        $this->assertSame('Ana Souza Lima', $user->nome);
        $this->assertSame('ana.lima', $user->instagram);
        $this->assertSame('ana@clinica.test', $user->email);
        $this->assertTrue($user->isProfissional());
    }

    public function test_validacao_dos_dados_pessoais(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/perfil', ['nome' => '', 'telefone' => 'abc'])
            ->assertSessionHasErrors(['nome', 'telefone']);
    }

    public function test_usuario_troca_a_propria_senha(): void
    {
        $user = User::factory()->create(['password' => 'senha-antiga']);

        $this->actingAs($user)
            ->put('/perfil/senha', [
                'current_password' => 'senha-antiga',
                'password' => 'senha-nova-123',
                'password_confirmation' => 'senha-nova-123',
            ])
            ->assertRedirect('/perfil')
            ->assertSessionHas('sucesso');

        $this->assertTrue(Hash::check('senha-nova-123', $user->fresh()->password));
    }

    public function test_troca_de_senha_exige_senha_atual_correta_e_confirmacao(): void
    {
        $user = User::factory()->create(['password' => 'senha-antiga']);

        $this->actingAs($user)
            ->put('/perfil/senha', [
                'current_password' => 'errada',
                'password' => 'senha-nova-123',
                'password_confirmation' => 'diferente',
            ])
            ->assertSessionHasErrorsIn('senha', ['current_password', 'password']);

        $this->actingAs($user)
            ->put('/perfil/senha', [
                'current_password' => 'senha-antiga',
                'password' => 'senha-antiga',
                'password_confirmation' => 'senha-antiga',
            ])
            ->assertSessionHasErrorsIn('senha', ['password']);

        $this->assertTrue(Hash::check('senha-antiga', $user->fresh()->password));
    }

    public function test_menu_do_usuario_tem_link_para_o_perfil(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertSee(route('perfil.edit'));
    }
}
