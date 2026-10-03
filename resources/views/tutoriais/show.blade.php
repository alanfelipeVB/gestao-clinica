<x-layouts.app :title="$tutorial->titulo">
    <x-page-header :titulo="$tutorial->titulo">
        <x-slot:acoes>
            <a href="{{ route('tutoriais.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Tutoriais
            </a>
        </x-slot:acoes>
    </x-page-header>

    @unless ($tutorial->publicado)
        <div class="alert alert-warning small">
            <i class="bi bi-eye-slash me-1"></i>Rascunho: este tutorial ainda não está visível para os profissionais.
        </div>
    @endunless

    <div class="card mb-3">
        <div class="card-body p-2 p-md-3">
            <video class="w-100 rounded bg-dark" style="max-height: 70vh;" controls preload="metadata"
                   controlslist="nodownload" src="{{ route('tutoriais.video', $tutorial) }}">
                Seu navegador não consegue reproduzir este vídeo.
            </video>
        </div>
    </div>

    <div class="card">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1" style="white-space: pre-line;">{{ $tutorial->descricao ?: 'Sem descrição.' }}</div>

            <form method="POST" action="{{ route('tutoriais.assistido', $tutorial) }}">
                @csrf
                @if ($assistidoEm)
                    <input type="hidden" name="assistido" value="0">
                    <div class="text-success small mb-1">
                        <i class="bi bi-check2-circle me-1"></i>Assistido em {{ \Illuminate\Support\Carbon::parse($assistidoEm)->format('d/m/Y H:i') }}
                    </div>
                    <button type="submit" class="btn btn-sm btn-link text-secondary p-0">Desmarcar</button>
                @else
                    <input type="hidden" name="assistido" value="1">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Marcar como assistido
                    </button>
                @endif
            </form>
        </div>
    </div>
</x-layouts.app>
