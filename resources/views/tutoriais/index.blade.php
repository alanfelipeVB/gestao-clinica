<x-layouts.app title="Tutoriais">
    <x-page-header titulo="Tutoriais" subtitulo="Vídeos de orientação disponibilizados pela clínica." />

    @php($assistidos = $tutoriais->where('assistido', true)->count())

    @if ($tutoriais->isNotEmpty())
        <div class="mb-3">
            <div class="d-flex justify-content-between small text-secondary mb-1">
                <span>Seu progresso</span>
                <span>{{ $assistidos }} de {{ $tutoriais->count() }} assistidos</span>
            </div>
            <div class="progress" role="progressbar" aria-label="Progresso nos tutoriais"
                 aria-valuenow="{{ $assistidos }}" aria-valuemin="0" aria-valuemax="{{ $tutoriais->count() }}" style="height: .5rem;">
                <div class="progress-bar" style="width: {{ $tutoriais->count() ? round($assistidos * 100 / $tutoriais->count()) : 0 }}%"></div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        @forelse ($tutoriais as $tutorial)
            <div class="col-12 col-md-6 col-xl-4">
                <a href="{{ route('tutoriais.show', $tutorial) }}" class="card h-100 text-decoration-none text-body">
                    <div class="card-body d-flex gap-3">
                        <span @class([
                            'rounded-3 d-inline-flex align-items-center justify-content-center fs-3 flex-shrink-0',
                            'bg-success-subtle text-success' => $tutorial->assistido,
                            'bg-primary-subtle text-primary-emphasis' => ! $tutorial->assistido,
                        ]) style="width: 3.5rem; height: 3.5rem;">
                            <i class="bi bi-{{ $tutorial->assistido ? 'check2-circle' : 'play-fill' }}"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="h6 mb-1">{{ $tutorial->titulo }}</h2>
                            @if ($tutorial->descricao)
                                <p class="small text-secondary mb-2">{{ Str::limit($tutorial->descricao, 110) }}</p>
                            @endif
                            @if ($tutorial->assistido)
                                <span class="badge text-bg-success">Assistido</span>
                            @else
                                <span class="badge text-bg-light border text-secondary">Não assistido</span>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center text-secondary py-5">
                        <i class="bi bi-collection-play fs-2 d-block mb-2"></i>
                        Nenhum tutorial disponível no momento.
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</x-layouts.app>
