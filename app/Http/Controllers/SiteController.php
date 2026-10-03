<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SiteService;
use Illuminate\Http\Request;
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
            'profissionais' => User::naPaginaInicial()->orderBy('nome')->get(),
        ]);
    }

    /**
     * Foto do profissional: pública para quem aparece na página inicial;
     * nos demais casos, apenas o administrador e o próprio profissional (prévia nos formulários).
     */
    public function foto(Request $request, User $user): BinaryFileResponse
    {
        $visitante = $request->user();
        $permitido = $user->apareceNaPaginaInicial()
            || ($visitante && ($visitante->isAdmin() || $visitante->is($user)));

        abort_unless($permitido && $user->foto && Storage::disk('local')->exists($user->foto), 404);

        return response()->file(Storage::disk('local')->path($user->foto), [
            'Cache-Control' => ($user->apareceNaPaginaInicial() ? 'public' : 'private').', max-age=86400',
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
