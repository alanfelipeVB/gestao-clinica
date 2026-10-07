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

    private const FAVICON = 'site_favicon';

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
        return $this->arquivo(self::LOGO);
    }

    /**
     * URL pública da logo (com versão para invalidar cache do navegador) ou null.
     */
    public function urlLogo(): ?string
    {
        return $this->url(self::LOGO, 'site.logo');
    }

    public function trocarLogo(UploadedFile $arquivo): void
    {
        $this->trocarArquivo(self::LOGO, $arquivo);
    }

    public function removerLogo(): void
    {
        $this->removerArquivo(self::LOGO);
    }

    public function favicon(): ?string
    {
        return $this->arquivo(self::FAVICON);
    }

    public function urlFavicon(): ?string
    {
        return $this->url(self::FAVICON, 'site.favicon');
    }

    public function trocarFavicon(UploadedFile $arquivo): void
    {
        $this->trocarArquivo(self::FAVICON, $arquivo);
    }

    public function removerFavicon(): void
    {
        $this->removerArquivo(self::FAVICON);
    }

    /**
     * Ícone da aba do navegador: favicon próprio → logo → ícone padrão do sistema.
     *
     * @return array{url: string, tipo: ?string}
     */
    public function iconeDaAba(): array
    {
        foreach ([[self::FAVICON, 'site.favicon'], [self::LOGO, 'site.logo']] as [$chave, $rota]) {
            if ($caminho = $this->arquivo($chave)) {
                return ['url' => $this->url($chave, $rota), 'tipo' => Storage::disk('local')->mimeType($caminho) ?: null];
            }
        }

        return ['url' => asset('favicon.svg'), 'tipo' => 'image/svg+xml'];
    }

    private function arquivo(string $chave): ?string
    {
        $caminho = $this->configuracoes->get($chave);

        return $caminho && Storage::disk('local')->exists($caminho) ? $caminho : null;
    }

    private function url(string $chave, string $rota): ?string
    {
        $caminho = $this->arquivo($chave);

        return $caminho ? route($rota, ['v' => substr(md5($caminho), 0, 8)]) : null;
    }

    private function trocarArquivo(string $chave, UploadedFile $arquivo): void
    {
        $anterior = $this->arquivo($chave);

        $this->configuracoes->salvar([$chave => $arquivo->store(self::PASTA, 'local')]);

        if ($anterior) {
            Storage::disk('local')->delete($anterior);
        }
    }

    private function removerArquivo(string $chave): void
    {
        if ($anterior = $this->arquivo($chave)) {
            Storage::disk('local')->delete($anterior);
        }

        $this->configuracoes->salvar([$chave => null]);
    }
}
