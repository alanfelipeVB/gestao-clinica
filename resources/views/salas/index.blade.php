<x-layouts.app title="Salas">
    <x-page-header titulo="Salas" subtitulo="Salas disponíveis para agendamento." />

    <div class="row g-3">
        @forelse ($salas as $sala)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100" style="border-top: 4px solid {{ $sala->cor }};">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h6 mb-1">{{ $sala->nome }}</h2>
                        @if ($sala->capacidade)
                            <div class="small text-secondary mb-2">
                                <i class="bi bi-people me-1"></i>Capacidade: {{ $sala->capacidade }} {{ Str::plural('pessoa', $sala->capacidade) }}
                            </div>
                        @endif
                        <p class="text-secondary small mb-0 flex-grow-1">{{ $sala->descricao ?: 'Sem descrição.' }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center text-secondary py-5">
                        <i class="bi bi-door-closed fs-2 d-block mb-2"></i>
                        Nenhuma sala disponível no momento.
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</x-layouts.app>
