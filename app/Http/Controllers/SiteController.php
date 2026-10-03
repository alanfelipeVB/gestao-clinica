<?php

namespace App\Http\Controllers;

use App\Services\SiteService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Página inicial pública da clínica.
 */
class SiteController extends Controller
{
    public function __construct(private readonly SiteService $site)
    {
    }

    public function inicio(): View
    {
        return view('site.inicio', [
            'textos' => $this->site->textos(),
            'urlLogo' => $this->site->urlLogo(),
        ]);
    }

    /**
     * Logo da clínica (pública). Fica no disco privado e é entregue por esta rota.
     */
    public function logo(): BinaryFileResponse
    {
        $caminho = $this->site->logo();
        abort_unless($caminho, 404);

        return response()->file(Storage::disk('local')->path($caminho), [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
