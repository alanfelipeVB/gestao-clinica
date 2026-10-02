<?php

namespace Tests\Feature\Admin;

use App\Models\Configuracao;
use App\Models\User;
use App\Services\ConfiguracaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_cria_antecedencia_padrao_de_30_dias(): void
    {
        $this->assertSame(30, app(ConfiguracaoService::class)->antecedenciaMaximaDias());
        $this->assertDatabaseHas('configuracoes', ['chave' => 'antecedencia_maxima_dias', 'valor' => '30']);
    }

    public function test_usa_valor_padrao_quando_a_chave_nao_existe(): void
    {
        Configuracao::query()->delete();

        $this->assertSame(30, app(ConfiguracaoService::class)->antecedenciaMaximaDias());
    }

    public function test_profissional_nao_acessa_configuracoes(): void
    {
        $profissional = User::factory()->create();

        $this->actingAs($profissional)->get('/admin/configuracoes')->assertForbidden();
        $this->actingAs($profissional)
            ->put('/admin/configuracoes', ['antecedencia_maxima_dias' => 90])
            ->assertForbidden();

        $this->assertSame(30, app(ConfiguracaoService::class)->antecedenciaMaximaDias());
    }

    public function test_admin_altera_antecedencia_e_o_cache_e_atualizado(): void
    {
        $admin = User::factory()->admin()->create();
        $servico = app(ConfiguracaoService::class);

        // Lê antes para popular o cache.
        $this->assertSame(30, $servico->antecedenciaMaximaDias());

        $this->actingAs($admin)->get('/admin/configuracoes')
            ->assertOk()
            ->assertSee('Antecedência máxima');

        $this->actingAs($admin)
            ->put('/admin/configuracoes', ['antecedencia_maxima_dias' => 60])
            ->assertRedirect('/admin/configuracoes')
            ->assertSessionHas('sucesso');

        $this->assertSame(60, $servico->antecedenciaMaximaDias());
    }

    public function test_validacao_da_antecedencia(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([0, 366, 'abc', ''] as $valorInvalido) {
            $this->actingAs($admin)
                ->put('/admin/configuracoes', ['antecedencia_maxima_dias' => $valorInvalido])
                ->assertSessionHasErrors('antecedencia_maxima_dias');
        }

        $this->assertSame(30, app(ConfiguracaoService::class)->antecedenciaMaximaDias());
    }
}
