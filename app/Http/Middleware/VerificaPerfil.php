<?php

namespace App\Http\Middleware;

use App\Enums\PerfilUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe a rota aos perfis informados. Uso: ->middleware('perfil:admin').
 */
class VerificaPerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $perfil = $request->user()?->perfil;

        $permitidos = array_map(fn (string $p) => PerfilUsuario::from($p), $perfis);

        abort_unless(in_array($perfil, $permitidos, true), 403, 'Você não tem permissão para acessar esta página.');

        return $next($request);
    }
}
