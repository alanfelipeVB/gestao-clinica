@props(['titulo', 'subtitulo' => null])

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">{{ $titulo }}</h1>
        @if ($subtitulo)
            <p class="text-secondary mb-0">{{ $subtitulo }}</p>
        @endif
    </div>

    @isset($acoes)
        <div class="d-flex flex-wrap gap-2">
            {{ $acoes }}
        </div>
    @endisset
</div>
