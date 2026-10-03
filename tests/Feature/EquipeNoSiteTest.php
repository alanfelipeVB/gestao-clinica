<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EquipeNoSiteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->admin = User::factory()->admin()->create(['nome' => 'Administradora Geral']);
    }

    /**
     * PNG real de 1x1 pixel (o PHP local não tem a extensão GD).
     */
    private function foto(string $nome = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nome, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosDoProfissional(User $user, array $sobrescrever = []): array
    {
        return [
            'nome' => $user->nome,
            'email' => $user->email,
            'telefone' => $user->telefone,
            'profissao' => $user->profissao,
            'perfil' => $user->perfil->value,
            ...$sobrescrever,
        ];
    }

    public function test_somente_profissionais_marcados_e_ativos_aparecem(): void
    {
        User::factory()->create(['nome' => 'Ana Exibida', 'exibir_no_site' => true, 'bio' => 'Psicóloga clínica.']);
        User::factory()->create(['nome' => 'Bruno Oculto']);
        User::factory()->inativo()->create(['nome' => 'Carla Inativa', 'exibir_no_site' => true]);
        $this->admin->update(['exibir_no_site' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Nossa equipe')
            ->assertSee('Ana Exibida')
            ->assertSee('Psicóloga clínica.')
            ->assertDontSee('Bruno Oculto')
            ->assertDontSee('Carla Inativa')
            ->assertDontSee('Administradora Geral');
    }

    public function test_sem_profissionais_marcados_a_secao_nao_aparece(): void
    {
        User::factory()->create();

        $this->get('/')->assertOk()->assertDontSee('Nossa equipe');
    }

    public function test_whatsapp_so_aparece_com_autorizacao_do_profissional(): void
    {
        $ana = User::factory()->create([
            'nome' => 'Dra. Ana Souza',
            'telefone' => '(11) 98888-7777',
            'exibir_no_site' => true,
        ]);

        $this->get('/')->assertSee('Dra. Ana Souza')->assertDontSee('wa.me/5511988887777', false);

        $ana->update(['publicar_whatsapp' => true]);

        $this->get('/')
            ->assertSee('https://wa.me/5511988887777?text=Ol%C3%A1%2C%20Ana', false);
    }

    public function test_profissional_autoriza_whatsapp_e_edita_bio_e_foto_no_perfil(): void
    {
        $ana = User::factory()->create(['telefone' => '(11) 98888-7777']);

        $this->actingAs($ana)
            ->put('/perfil', [
                'nome' => $ana->nome,
                'telefone' => $ana->telefone,
                'bio' => 'Especialista em ansiedade.',
                'publicar_whatsapp' => '1',
                'foto' => $this->foto(),
            ])
            ->assertSessionHasNoErrors();

        $ana->refresh();
        $this->assertTrue($ana->publicar_whatsapp);
        $this->assertSame('Especialista em ansiedade.', $ana->bio);
        Storage::disk('local')->assertExists($ana->foto);

        // O próprio profissional não consegue se colocar na página inicial.
        $this->actingAs($ana)->put('/perfil', ['nome' => $ana->nome, 'exibir_no_site' => '1']);
        $this->assertFalse($ana->fresh()->exibir_no_site);
    }

    public function test_admin_controla_exibicao_mas_nao_o_whatsapp(): void
    {
        $ana = User::factory()->create();

        $this->actingAs($this->admin)
            ->put("/admin/profissionais/{$ana->id}", $this->dadosDoProfissional($ana, [
                'exibir_no_site' => '1',
                'publicar_whatsapp' => '1',
                'bio' => 'Bio cadastrada pelo admin.',
                'foto' => $this->foto(),
            ]))
            ->assertSessionHasNoErrors();

        $ana->refresh();
        $this->assertTrue($ana->exibir_no_site);
        $this->assertFalse($ana->publicar_whatsapp);
        $this->assertSame('Bio cadastrada pelo admin.', $ana->bio);
        $this->assertNotNull($ana->foto);
    }

    public function test_troca_e_remocao_da_foto(): void
    {
        $ana = User::factory()->create();

        $this->actingAs($this->admin)->put("/admin/profissionais/{$ana->id}", $this->dadosDoProfissional($ana, ['foto' => $this->foto()]));
        $primeira = $ana->fresh()->foto;

        $this->actingAs($this->admin)->put("/admin/profissionais/{$ana->id}", $this->dadosDoProfissional($ana, ['foto' => $this->foto('nova.png')]));
        Storage::disk('local')->assertMissing($primeira);
        $segunda = $ana->fresh()->foto;

        $this->actingAs($this->admin)->put("/admin/profissionais/{$ana->id}", $this->dadosDoProfissional($ana, ['remover_foto' => '1']));
        $this->assertNull($ana->fresh()->foto);
        Storage::disk('local')->assertMissing($segunda);
    }

    public function test_acesso_a_foto(): void
    {
        $ana = User::factory()->create();
        $this->actingAs($ana)->put('/perfil', ['nome' => $ana->nome, 'foto' => $this->foto()]);
        $ana->refresh();
        auth()->logout();

        // Fora do site: só o admin e o próprio profissional.
        $this->get(route('profissionais.foto', $ana))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('profissionais.foto', $ana))->assertNotFound();
        $this->actingAs($ana)->get(route('profissionais.foto', $ana))->assertOk();
        $this->actingAs($this->admin)->get(route('profissionais.foto', $ana))->assertOk();

        // Exibida no site: pública.
        $ana->update(['exibir_no_site' => true]);
        auth()->logout();
        $this->get(route('profissionais.foto', $ana))->assertOk();
    }

    public function test_validacao_da_foto_e_bio(): void
    {
        $ana = User::factory()->create();

        $this->actingAs($ana)
            ->put('/perfil', [
                'nome' => $ana->nome,
                'bio' => str_repeat('a', 501),
                'foto' => UploadedFile::fake()->create('foto.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors(['bio', 'foto']);
    }
}
