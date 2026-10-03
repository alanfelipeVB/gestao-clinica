<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TutorialRequest;
use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TutorialController extends Controller
{
    public function index(): View
    {
        return view('admin.tutoriais.index', [
            'tutoriais' => Tutorial::ordenados()->withCount('assistidoPor')->get(),
            'totalProfissionais' => User::ativos()->profissionais()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tutoriais.create', ['tutorial' => new Tutorial(['publicado' => true])]);
    }

    public function store(TutorialRequest $request): RedirectResponse
    {
        $tutorial = Tutorial::create([
            ...$request->dados(),
            ...$this->salvarVideo($request->file('video')),
            'criado_por' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.tutoriais.index')
            ->with('sucesso', "Tutorial \"{$tutorial->titulo}\" cadastrado.");
    }

    /**
     * Quem já assistiu (e quem ainda não) entre os profissionais ativos.
     */
    public function show(Tutorial $tutorial): View
    {
        $assistiram = $tutorial->assistidoPor()->get()->keyBy('id');

        $profissionais = User::ativos()->profissionais()->orderBy('nome')->get(['id', 'nome', 'profissao'])
            ->map(fn (User $u) => [
                'profissional' => $u,
                'assistido_em' => $assistiram->get($u->id)?->pivot->assistido_em,
            ]);

        return view('admin.tutoriais.show', [
            'tutorial' => $tutorial,
            'profissionais' => $profissionais,
        ]);
    }

    public function edit(Tutorial $tutorial): View
    {
        return view('admin.tutoriais.edit', ['tutorial' => $tutorial]);
    }

    public function update(TutorialRequest $request, Tutorial $tutorial): RedirectResponse
    {
        $videoAntigo = null;
        $dados = $request->dados();

        if ($request->hasFile('video')) {
            $videoAntigo = $tutorial->arquivo;
            $dados = [...$dados, ...$this->salvarVideo($request->file('video'))];
        }

        $tutorial->update($dados);

        if ($videoAntigo) {
            Storage::disk('local')->delete($videoAntigo);
        }

        return redirect()
            ->route('admin.tutoriais.index')
            ->with('sucesso', "Tutorial \"{$tutorial->titulo}\" atualizado.");
    }

    public function destroy(Tutorial $tutorial): RedirectResponse
    {
        $arquivo = $tutorial->arquivo;
        $titulo = $tutorial->titulo;

        $tutorial->delete();
        Storage::disk('local')->delete($arquivo);

        return redirect()
            ->route('admin.tutoriais.index')
            ->with('sucesso', "Tutorial \"{$titulo}\" excluído.");
    }

    public function alternarPublicacao(Request $request, Tutorial $tutorial): RedirectResponse
    {
        $tutorial->update(['publicado' => ! $tutorial->publicado]);

        return back()->with('sucesso', $tutorial->publicado
            ? "\"{$tutorial->titulo}\" publicado para os profissionais."
            : "\"{$tutorial->titulo}\" ocultado dos profissionais.");
    }

    /**
     * @return array{arquivo: string, mime: string, tamanho: int}
     */
    private function salvarVideo(UploadedFile $video): array
    {
        return [
            'arquivo' => $video->store(Tutorial::PASTA, 'local'),
            'mime' => $video->getMimeType(),
            'tamanho' => $video->getSize(),
        ];
    }
}
