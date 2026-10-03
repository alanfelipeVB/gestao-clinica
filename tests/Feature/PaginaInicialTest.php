<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SiteService;
use App\Support\Whatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaginaInicialTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function dados(array $sobrescrever = []): array
    {
        return [
            'site_nome' => 'Clínica Bem Viver',
            'site_titulo' => 'Saúde com acolhimento',
            'site_subtitulo' => 'Atendimento humanizado.',
            'site_sobre' => 'Somos uma clínica multidisciplinar.',
            'site_endereco' => 'Rua das Flores, 100 - Centro',
            'site_telefone' => '(11) 3333-4444',
            'site_whatsapp' => '(11) 98888-7777',
            'site_email' => 'contato@bemviver.com',
            'site_instagram' => '@clinicabemviver',
            'site_horario' => 'Seg a sex, 8h às 18h',
            ...$sobrescrever,
        ];
    }

    /**
     * PNG real de 1x1 pixel (o PHP local não tem a extensão GD para gerar imagens falsas).
     */
    private function imagem(string $nome): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($nome, $png);
    }

    public function test_pagina_publica_com_valores_padrao(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Cuidado e acolhimento em cada atendimento')
            ->assertSee('Entrar')
            ->assertDontSee('Fale conosco')
            ->assertDontSee('id="contato"', false);
    }

    public function test_usuario_logado_ve_atalho_para_o_sistema(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Ir para o sistema');
    }

    public function test_admin_edita_textos_e_a_pagina_reflete(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/pagina-inicial', $this->dados())
            ->assertRedirect('/admin/pagina-inicial')
            ->assertSessionHas('sucesso');

        $this->assertSame('clinicabemviver', app(SiteService::class)->textos()['site_instagram']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Clínica Bem Viver')
            ->assertSee('Saúde com acolhimento')
            ->assertSee('Somos uma clínica multidisciplinar.')
            ->assertSee('Rua das Flores, 100 - Centro')
            ->assertSee('https://wa.me/5511988887777', false)
            ->assertSee('Fale conosco')
            ->assertSee('instagram.com/clinicabemviver', false);

        // O nome da clínica também aparece no login.
        auth()->logout();
        $this->get('/login')->assertSee('Clínica Bem Viver');
    }

    public function test_validacao(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/pagina-inicial', $this->dados([
                'site_email' => 'nao-e-email',
                'site_whatsapp' => 'abc',
                'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            ]))
            ->assertSessionHasErrors(['site_email', 'site_whatsapp', 'logo']);
    }

    public function test_envio_troca_e_remocao_da_logo(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/pagina-inicial', $this->dados(['logo' => $this->imagem('logo.png')]))
            ->assertSessionHasNoErrors();

        $site = app(SiteService::class);
        $primeira = $site->logo();
        $this->assertNotNull($primeira);
        Storage::disk('local')->assertExists($primeira);

        // Logo é pública e aparece no site.
        $this->get('/')->assertSee(route('site.logo'), false);
        auth()->logout();
        $this->get(route('site.logo'))->assertOk();

        // Trocar apaga a anterior.
        $this->actingAs($this->admin)
            ->put('/admin/pagina-inicial', $this->dados(['logo' => $this->imagem('nova.png')]));
        Storage::disk('local')->assertMissing($primeira);
        $segunda = app(SiteService::class)->logo();

        // Remover.
        $this->actingAs($this->admin)
            ->put('/admin/pagina-inicial', $this->dados(['remover_logo' => '1']));
        $this->assertNull(app(SiteService::class)->logo());
        Storage::disk('local')->assertMissing($segunda);
        $this->get(route('site.logo'))->assertNotFound();
    }

    public function test_profissional_nao_edita_a_pagina_inicial(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/pagina-inicial', $this->dados())
            ->assertForbidden();
    }

    public function test_link_de_whatsapp(): void
    {
        $this->assertSame('https://wa.me/5511988887777', Whatsapp::link('(11) 98888-7777'));
        $this->assertSame('https://wa.me/551133334444?text=Ol%C3%A1', Whatsapp::link('11 3333-4444', 'Olá'));
        $this->assertSame('https://wa.me/351912345678', Whatsapp::link('+351 912 345 678'));
        $this->assertNull(Whatsapp::link('123'));
        $this->assertNull(Whatsapp::link(null));
    }
}
