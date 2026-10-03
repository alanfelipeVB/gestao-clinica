<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Conteúdo da página inicial pública (textos e logo), guardado nas configurações.
 */
class SiteService
{
    /** Pasta da logo no disco privado "local" (servida pela rota site.logo). */
    public const PASTA = 'site';

    private const LOGO = 'site_logo';

    /**
     * Campos de texto editáveis e seus valores padrão.
     *
     * @var array<string, string>
     */
    public const CAMPOS = [
        'site_nome' => '',
        'site_titulo' => 'Cuidado e acolhimento em cada atendimento',
        'site_subtitulo' => 'Uma equipe de profissionais dedicada à sua saúde e bem-estar.',
        'site_sobre' => '',
        'site_endereco' => '',
        'site_telefone' => '',
        'site_whatsapp' => '',
        'site_email' => '',
        'site_instagram' => '',
        'site_horario' => '',
    ];

    public function __construct(private readonly ConfiguracaoService $configuracoes)
    {
    }

    /**
     * Textos da página, com os padrões aplicados.
     *
     * @return array<string, string>
     */
    public function textos(): array
    {
        $textos = [];

        foreach (self::CAMPOS as $chave => $padrao) {
            $textos[$chave] = (string) ($this->configuracoes->get($chave) ?: $padrao);
        }

        $textos['site_nome'] = $textos['site_nome'] ?: (string) config('app.name');

        return $textos;
    }

    /**
     * @param  array<string, ?string>  $textos
     */
    public function salvarTextos(array $textos): void
    {
        $this->configuracoes->salvar(array_intersect_key($textos, self::CAMPOS));
    }

    public function logo(): ?string
    {
        $caminho = $this->configuracoes->get(self::LOGO);

        return $caminho && Storage::disk('local')->exists($caminho) ? $caminho : null;
    }

    /**
     * URL pública da logo (com versão para invalidar cache do navegador) ou null.
     */
    public function urlLogo(): ?string
    {
        $caminho = $this->logo();

        return $caminho ? route('site.logo', ['v' => substr(md5($caminho), 0, 8)]) : null;
    }

    public function trocarLogo(UploadedFile $arquivo): void
    {
        $anterior = $this->logo();

        $this->configuracoes->salvar([self::LOGO => $arquivo->store(self::PASTA, 'local')]);

        if ($anterior) {
            Storage::disk('local')->delete($anterior);
        }
    }

    public function removerLogo(): void
    {
        if ($anterior = $this->logo()) {
            Storage::disk('local')->delete($anterior);
        }

        $this->configuracoes->salvar([self::LOGO => null]);
    }
}
