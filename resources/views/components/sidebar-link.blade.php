@props([
    'rota',
    'icone',
    // Padrão de rotas que marcam o item como ativo (ex.: "admin.salas.*").
    'ativo' => null,
])

@php
    $existe = Route::has($rota);
    $padrao = $ativo ?? (str_contains($rota, '.') ? Str::beforeLast($rota, '.').'.*' : $rota);
    $estaAtivo = $existe && request()->routeIs($padrao);
@endphp

<li class="nav-item">
    @if ($existe)
        <a href="{{ route($rota) }}" @class(['nav-link', 'active' => $estaAtivo])
           @if ($estaAtivo) aria-current="page" @endif>
            <i class="bi bi-{{ $icone }}"></i>
            <span>{{ $slot }}</span>
        </a>
    @else
        {{-- Rota ainda não implementada: item exibido desabilitado. --}}
        <span class="nav-link disabled" aria-disabled="true">
            <i class="bi bi-{{ $icone }}"></i>
            <span>{{ $slot }}</span>
        </span>
    @endif
</li>
