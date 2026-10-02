<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_de_login_e_exibida(): void
    {
        $this->get('/login')->assertOk()->assertSee('Acesse sua conta');
    }

    public function test_raiz_redireciona_visitante_para_login(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_com_credenciais_validas(): void
    {
        $user = User::factory()->create(['password' => 'senha-correta']);

        $this->post('/login', ['email' => $user->email, 'password' => 'senha-correta'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_com_senha_errada_falha(): void
    {
        $user = User::factory()->create(['password' => 'senha-correta']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'errada'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);

        $this->assertGuest();
    }

    public function test_usuario_inativo_nao_consegue_logar(): void
    {
        $user = User::factory()->inativo()->create(['password' => 'senha-correta']);

        $this->post('/login', ['email' => $user->email, 'password' => 'senha-correta'])
            ->assertSessionHasErrors(['email' => trans('auth.inactive')]);

        $this->assertGuest();
    }

    public function test_login_e_bloqueado_apos_cinco_tentativas(): void
    {
        $user = User::factory()->create(['password' => 'senha-correta']);

        foreach (range(1, 5) as $tentativa) {
            $this->post('/login', ['email' => $user->email, 'password' => 'errada']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'senha-correta'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('email'));
    }

    public function test_usuario_logado_e_redirecionado_ao_acessar_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_usuario_desativado_durante_a_sessao_e_desconectado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->update(['ativo' => false]);

        $this->get('/dashboard')
            ->assertRedirect('/login')
            ->assertSessionHas('erro');

        $this->assertGuest();
    }

    public function test_area_admin_bloqueia_profissional_e_libera_admin(): void
    {
        Route::middleware(['web', 'auth', 'perfil:admin'])
            ->get('/admin/rota-de-teste', fn () => 'ok');

        $this->get('/admin/rota-de-teste')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/admin/rota-de-teste')
            ->assertForbidden()
            ->assertSee('Acesso negado');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/rota-de-teste')
            ->assertOk()
            ->assertSee('ok');
    }
}
