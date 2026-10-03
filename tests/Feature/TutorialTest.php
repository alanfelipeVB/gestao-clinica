<?php

namespace Tests\Feature;

use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TutorialTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $profissional;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->admin()->create();
        $this->profissional = User::factory()->create(['nome' => 'Ana Souza']);
    }

    private function video(string $nome = 'aula.mp4', int $kb = 2048, string $mime = 'video/mp4'): UploadedFile
    {
        return UploadedFile::fake()->create($nome, $kb, $mime);
    }

    private function tutorialComArquivo(array $atributos = []): Tutorial
    {
        $tutorial = Tutorial::factory()->create(['criado_por' => $this->admin->id, ...$atributos]);
        Storage::disk('local')->put($tutorial->arquivo, 'conteudo-do-video');

        return $tutorial;
    }

    // ------------------------------------------------------------------
    // Administração
    // ------------------------------------------------------------------

    public function test_admin_envia_tutorial(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/tutoriais', [
                'titulo' => 'Como usar a agenda',
                'descricao' => 'Passo a passo.',
                'ordem' => 1,
                'publicado' => '1',
                'video' => $this->video(),
            ])
            ->assertRedirect('/admin/tutoriais')
            ->assertSessionHas('sucesso');

        $tutorial = Tutorial::sole();
        $this->assertSame('Como usar a agenda', $tutorial->titulo);
        $this->assertTrue($tutorial->publicado);
        $this->assertSame('video/mp4', $tutorial->mime);
        $this->assertStringStartsWith('tutoriais/', $tutorial->arquivo);
        Storage::disk('local')->assertExists($tutorial->arquivo);
    }

    public function test_validacao_do_video(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/tutoriais', ['titulo' => 'Sem vídeo'])
            ->assertSessionHasErrors('video');

        $this->actingAs($this->admin)
            ->post('/admin/tutoriais', ['titulo' => 'PDF', 'video' => UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('video');

        $this->actingAs($this->admin)
            ->post('/admin/tutoriais', ['titulo' => 'Grande', 'video' => $this->video('grande.mp4', Tutorial::TAMANHO_MAXIMO_KB + 1)])
            ->assertSessionHasErrors('video');

        $this->assertSame(0, Tutorial::count());
    }

    public function test_admin_troca_o_video_e_o_antigo_e_apagado(): void
    {
        $tutorial = $this->tutorialComArquivo();
        $antigo = $tutorial->arquivo;

        $this->actingAs($this->admin)
            ->put("/admin/tutoriais/{$tutorial->id}", [
                'titulo' => 'Título novo',
                'publicado' => '1',
                'video' => $this->video('nova.webm', 1024, 'video/webm'),
            ])
            ->assertSessionHasNoErrors();

        $tutorial->refresh();
        $this->assertSame('Título novo', $tutorial->titulo);
        $this->assertSame('video/webm', $tutorial->mime);
        Storage::disk('local')->assertMissing($antigo);
        Storage::disk('local')->assertExists($tutorial->arquivo);
    }

    public function test_edicao_sem_video_mantem_o_arquivo(): void
    {
        $tutorial = $this->tutorialComArquivo();
        $arquivo = $tutorial->arquivo;

        $this->actingAs($this->admin)
            ->put("/admin/tutoriais/{$tutorial->id}", ['titulo' => 'Só o título', 'publicado' => '0'])
            ->assertSessionHasNoErrors();

        $this->assertSame($arquivo, $tutorial->fresh()->arquivo);
        $this->assertFalse($tutorial->fresh()->publicado);
        Storage::disk('local')->assertExists($arquivo);
    }

    public function test_admin_publica_oculta_e_exclui(): void
    {
        $tutorial = $this->tutorialComArquivo(['publicado' => false]);

        $this->actingAs($this->admin)->patch("/admin/tutoriais/{$tutorial->id}/publicacao");
        $this->assertTrue($tutorial->fresh()->publicado);

        $this->actingAs($this->admin)->delete("/admin/tutoriais/{$tutorial->id}")->assertRedirect('/admin/tutoriais');

        $this->assertSame(0, Tutorial::count());
        Storage::disk('local')->assertMissing($tutorial->arquivo);
    }

    public function test_profissional_nao_gerencia_tutoriais(): void
    {
        $tutorial = $this->tutorialComArquivo();

        $this->actingAs($this->profissional)->get('/admin/tutoriais')->assertForbidden();
        $this->actingAs($this->profissional)->post('/admin/tutoriais', ['titulo' => 'X', 'video' => $this->video()])->assertForbidden();
        $this->actingAs($this->profissional)->delete("/admin/tutoriais/{$tutorial->id}")->assertForbidden();

        $this->assertSame(1, Tutorial::count());
    }

    // ------------------------------------------------------------------
    // Profissionais
    // ------------------------------------------------------------------

    public function test_profissional_ve_apenas_tutoriais_publicados(): void
    {
        $publicado = $this->tutorialComArquivo(['titulo' => 'Tutorial publicado']);
        $rascunho = $this->tutorialComArquivo(['titulo' => 'Rascunho secreto', 'publicado' => false]);

        $this->actingAs($this->profissional)->get('/tutoriais')
            ->assertOk()
            ->assertSee('Tutorial publicado')
            ->assertDontSee('Rascunho secreto');

        $this->actingAs($this->profissional)->get("/tutoriais/{$publicado->id}")->assertOk();
        $this->actingAs($this->profissional)->get("/tutoriais/{$rascunho->id}")->assertForbidden();
        $this->actingAs($this->profissional)->get("/tutoriais/{$rascunho->id}/video")->assertForbidden();

        // O admin pode pré-visualizar o rascunho.
        $this->actingAs($this->admin)->get("/tutoriais/{$rascunho->id}")->assertOk()->assertSee('Rascunho');
    }

    public function test_video_exige_login_e_e_entregue_com_o_tipo_correto(): void
    {
        $tutorial = $this->tutorialComArquivo();

        $this->get("/tutoriais/{$tutorial->id}/video")->assertRedirect('/login');

        $resposta = $this->actingAs($this->profissional)->get("/tutoriais/{$tutorial->id}/video")->assertOk();
        $this->assertSame('video/mp4', $resposta->headers->get('content-type'));
        $this->assertSame('bytes', $resposta->headers->get('accept-ranges'));
    }

    public function test_marcar_e_desmarcar_como_assistido(): void
    {
        $tutorial = $this->tutorialComArquivo(['titulo' => 'Boas-vindas']);

        $this->actingAs($this->profissional)
            ->post("/tutoriais/{$tutorial->id}/assistido", ['assistido' => '1'])
            ->assertSessionHas('sucesso');

        $this->assertTrue($tutorial->assistidoPor()->whereKey($this->profissional->id)->exists());

        $this->actingAs($this->profissional)->get('/tutoriais')->assertSee('1 de 1 assistidos');

        // Admin vê quem assistiu.
        $this->actingAs($this->admin)->get("/admin/tutoriais/{$tutorial->id}")
            ->assertOk()
            ->assertSee('1 de 1 profissionais ativos assistiram')
            ->assertSee('Ana Souza');

        $this->actingAs($this->profissional)->post("/tutoriais/{$tutorial->id}/assistido", ['assistido' => '0']);
        $this->assertFalse($tutorial->assistidoPor()->whereKey($this->profissional->id)->exists());
    }
}
