<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfiguracaoRequest;
use App\Services\ConfiguracaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function __construct(private readonly ConfiguracaoService $configuracoes)
    {
    }

    public function edit(): View
    {
        return view('admin.configuracoes.edit', [
            'antecedenciaMaximaDias' => $this->configuracoes->antecedenciaMaximaDias(),
        ]);
    }

    public function update(ConfiguracaoRequest $request): RedirectResponse
    {
        $this->configuracoes->salvar($request->validated());

        return redirect()
            ->route('admin.configuracoes.edit')
            ->with('sucesso', 'Configurações salvas.');
    }
}
