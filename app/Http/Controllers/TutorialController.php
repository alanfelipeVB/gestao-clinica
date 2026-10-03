<?php

namespace App\Http\Controllers;

use App\Models\Tutorial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Tutoriais publicados, disponíveis para todos os usuários.
 */
class TutorialController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $tutoriais = Tutorial::publicados()
            ->ordenados()
            ->withExists(['assistidoPor as assistido' => fn ($q) => $q->where('users.id', $userId)])
            ->get();

        return view('tutoriais.index', ['tutoriais' => $tutoriais]);
    }

    public function show(Request $request, Tutorial $tutorial): View
    {
        Gate::authorize('view', $tutorial);

        return view('tutoriais.show', [
            'tutorial' => $tutorial,
            'assistidoEm' => $tutorial->assistidoPor()->whereKey($request->user()->id)->first()?->pivot->assistido_em,
        ]);
    }

    /**
     * Entrega o vídeo apenas para usuários autenticados (com suporte a avançar/voltar via Range).
     */
    public function video(Tutorial $tutorial): BinaryFileResponse
    {
        Gate::authorize('view', $tutorial);

        $disco = Storage::disk('local');
        abort_unless($disco->exists($tutorial->arquivo), 404, 'Arquivo de vídeo não encontrado.');

        return response()->file($disco->path($tutorial->arquivo), [
            'Content-Type' => $tutorial->mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Marca ou desmarca o tutorial como assistido pelo usuário.
     */
    public function marcarAssistido(Request $request, Tutorial $tutorial): RedirectResponse
    {
        Gate::authorize('view', $tutorial);

        $assistido = $request->validate(['assistido' => ['required', 'boolean']])['assistido'];

        if ($assistido) {
            $tutorial->assistidoPor()->syncWithoutDetaching([$request->user()->id => ['assistido_em' => now()]]);
        } else {
            $tutorial->assistidoPor()->detach($request->user()->id);
        }

        return back()->with('sucesso', $assistido ? 'Tutorial marcado como assistido.' : 'Marcação de assistido removida.');
    }
}
