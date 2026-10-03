<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaginaInicialRequest;
use App\Services\SiteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaginaInicialController extends Controller
{
    public function __construct(private readonly SiteService $site)
    {
    }

    public function edit(): View
    {
        return view('admin.pagina-inicial.edit', [
            'textos' => $this->site->textos(),
            'urlLogo' => $this->site->urlLogo(),
        ]);
    }

    public function update(PaginaInicialRequest $request): RedirectResponse
    {
        $this->site->salvarTextos($request->safe()->except(['logo', 'remover_logo']));

        if ($request->hasFile('logo')) {
            $this->site->trocarLogo($request->file('logo'));
        } elseif ($request->boolean('remover_logo')) {
            $this->site->removerLogo();
        }

        return redirect()
            ->route('admin.pagina-inicial.edit')
            ->with('sucesso', 'Página inicial atualizada.');
    }
}
