<?php

namespace Tests\Feature;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_profissional_e_o_perfil_padrao_da_factory(): void
    {
        $user = User::factory()->create();

        $this->assertSame(PerfilUsuario::Profissional, $user->perfil);
        $this->assertTrue($user->isProfissional());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->ativo);
    }

    public function test_estado_admin_da_factory(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isAdmin());
        $this->assertSame('admin', $user->getRawOriginal('perfil'));
    }

    public function test_senha_e_armazenada_com_hash(): void
    {
        $user = User::factory()->create(['password' => 'segredo123']);

        $this->assertNotSame('segredo123', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('segredo123', $user->password));
    }

    public function test_scopes_ativos_e_profissionais(): void
    {
        User::factory()->admin()->create();
        User::factory()->count(2)->create();
        User::factory()->inativo()->create();

        $this->assertSame(3, User::profissionais()->count());
        $this->assertSame(2, User::profissionais()->ativos()->count());
    }

    public function test_primeiro_nome_ignora_titulos(): void
    {
        $this->assertSame('Ana', (new User(['nome' => 'Dra. Ana Ribeiro']))->primeiroNome());
        $this->assertSame('João', (new User(['nome' => 'Dr João Martins']))->primeiroNome());
        $this->assertSame('Maria', (new User(['nome' => 'Maria Souza']))->primeiroNome());
        $this->assertSame('Dra.', (new User(['nome' => 'Dra.']))->primeiroNome());
    }

    public function test_admin_seeder_cria_administrador_a_partir_da_config(): void
    {
        config([
            'clinica.admin.nome' => 'Admin Teste',
            'clinica.admin.email' => 'admin@teste.local',
            'clinica.admin.senha' => 'senha-forte',
        ]);

        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class); // idempotente

        $this->assertSame(1, User::where('email', 'admin@teste.local')->count());

        $admin = User::firstWhere('email', 'admin@teste.local');
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->ativo);
        $this->assertTrue(Hash::check('senha-forte', $admin->password));
    }

    public function test_admin_seeder_exige_email_e_senha(): void
    {
        config(['clinica.admin.email' => null, 'clinica.admin.senha' => null]);

        $this->expectException(RuntimeException::class);

        $this->seed(AdminSeeder::class);
    }
}
