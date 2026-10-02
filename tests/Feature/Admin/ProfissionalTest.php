<?php

namespace Tests\Feature\Admin;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfissionalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['nome' => 'Admin Principal']);
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidos(array $sobrescrever = []): array
    {
        return [
            'nome' => 'Ana Souza',
            'email' => 'ana@clinica.test',
            'telefone' => '(11) 98888-7777',
            'instagram' => '@ana.souza',
            'profissao' => 'Psicóloga',
            'perfil' => 'profissional',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
            ...$sobrescrever,
        ];
    }

    public function test_profissional_nao_acessa_o_cadastro(): void
    {
        $profissional = User::factory()->create();

        $this->actingAs($profissional)->get('/admin/profissionais')->assertForbidden();
        $this->actingAs($profissional)->post('/admin/profissionais', $this->dadosValidos())->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'ana@clinica.test']);
    }

    public function test_lista_e_filtra_profissionais(): void
    {
        User::factory()->create(['nome' => 'Carlos Ativo']);
        User::factory()->inativo()->create(['nome' => 'Beatriz Inativa']);

        $this->actingAs($this->admin)->get('/admin/profissionais')
            ->assertOk()
            ->assertSee('Carlos Ativo')
            ->assertSee('Beatriz Inativa');

        $this->actingAs($this->admin)->get('/admin/profissionais?status=inativos')
            ->assertOk()
            ->assertSee('Beatriz Inativa')
            ->assertDontSee('Carlos Ativo');

        $this->actingAs($this->admin)->get('/admin/profissionais?busca=carlos')
            ->assertOk()
            ->assertSee('Carlos Ativo')
            ->assertDontSee('Beatriz Inativa');
    }

    public function test_admin_cadastra_profissional(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/profissionais', $this->dadosValidos(['email' => 'ANA@Clinica.test']))
            ->assertRedirect('/admin/profissionais')
            ->assertSessionHas('sucesso');

        $ana = User::firstWhere('email', 'ana@clinica.test');

        $this->assertNotNull($ana);
        $this->assertSame(PerfilUsuario::Profissional, $ana->perfil);
        $this->assertTrue($ana->ativo);
        $this->assertSame('ana.souza', $ana->instagram);
        $this->assertTrue(Hash::check('senha-segura', $ana->password));
    }

    public function test_validacoes_do_cadastro(): void
    {
        User::factory()->create(['email' => 'ana@clinica.test']);

        $this->actingAs($this->admin)
            ->post('/admin/profissionais', $this->dadosValidos([
                'nome' => '',
                'password' => 'curta',
                'password_confirmation' => 'diferente',
                'perfil' => 'superusuario',
                'telefone' => 'abc',
            ]))
            ->assertSessionHasErrors(['nome', 'email', 'password', 'perfil', 'telefone']);
    }

    public function test_admin_edita_sem_alterar_senha(): void
    {
        $ana = User::factory()->create(['password' => 'senha-antiga']);

        $this->actingAs($this->admin)
            ->put("/admin/profissionais/{$ana->id}", $this->dadosValidos([
                'email' => $ana->email,
                'nome' => 'Ana Atualizada',
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect('/admin/profissionais');

        $ana->refresh();
        $this->assertSame('Ana Atualizada', $ana->nome);
        $this->assertTrue(Hash::check('senha-antiga', $ana->password));
    }

    public function test_admin_redefine_senha(): void
    {
        $ana = User::factory()->create(['password' => 'senha-antiga']);

        $this->actingAs($this->admin)
            ->put("/admin/profissionais/{$ana->id}", $this->dadosValidos([
                'email' => $ana->email,
                'password' => 'nova-senha-123',
                'password_confirmation' => 'nova-senha-123',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nova-senha-123', $ana->fresh()->password));
    }

    public function test_admin_pode_cadastrar_outro_admin(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/profissionais', $this->dadosValidos(['perfil' => 'admin']))
            ->assertSessionHasNoErrors();

        $this->assertTrue(User::firstWhere('email', 'ana@clinica.test')->isAdmin());
    }

    public function test_admin_nao_remove_o_proprio_perfil_de_admin(): void
    {
        $this->actingAs($this->admin)
            ->put("/admin/profissionais/{$this->admin->id}", $this->dadosValidos([
                'email' => $this->admin->email,
                'perfil' => 'profissional',
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasErrors('perfil');

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_admin_desativa_e_reativa_profissional(): void
    {
        $ana = User::factory()->create();

        $this->actingAs($this->admin)->patch("/admin/profissionais/{$ana->id}/status")
            ->assertSessionHas('sucesso');
        $this->assertFalse($ana->fresh()->ativo);

        $this->actingAs($this->admin)->patch("/admin/profissionais/{$ana->id}/status");
        $this->assertTrue($ana->fresh()->ativo);
    }

    public function test_admin_nao_desativa_a_propria_conta(): void
    {
        $this->actingAs($this->admin)->patch("/admin/profissionais/{$this->admin->id}/status")
            ->assertSessionHas('erro');

        $this->assertTrue($this->admin->fresh()->ativo);
    }
}
