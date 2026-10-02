<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Violação de uma regra de negócio de agendamento.
 * Renderizada como erro de validação no campo indicado.
 */
class RegraAgendamentoException extends RuntimeException
{
    public function __construct(string $mensagem, public readonly string $campo = 'agendamento')
    {
        parent::__construct($mensagem);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'errors' => [$this->campo => [$this->getMessage()]],
            ], 422);
        }

        return back()->withInput()->withErrors([$this->campo => $this->getMessage()]);
    }
}
