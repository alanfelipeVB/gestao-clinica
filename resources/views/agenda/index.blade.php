<x-layouts.app title="Agenda">
    <x-page-header titulo="Agenda" subtitulo="Clique em um horário livre para agendar ou em um agendamento para ver os detalhes.">
        <x-slot:acoes>
            <a href="{{ route('agendamentos.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Novo agendamento
            </a>
        </x-slot:acoes>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="filtro-sala" class="form-label small text-secondary mb-1">Sala</label>
                    <select id="filtro-sala" class="form-select">
                        <option value="">Todas as salas</option>
                        @foreach ($salas as $sala)
                            <option value="{{ $sala->id }}" @selected((int) ($filtros['sala_id'] ?? 0) === $sala->id)>{{ $sala->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="filtro-profissional" class="form-label small text-secondary mb-1">Profissional</label>
                    <select id="filtro-profissional" class="form-select">
                        <option value="">Todos os profissionais</option>
                        @foreach ($profissionais as $profissional)
                            <option value="{{ $profissional->id }}" @selected((int) ($filtros['user_id'] ?? 0) === $profissional->id)>{{ $profissional->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="filtro-data" class="form-label small text-secondary mb-1">Ir para a data</label>
                    <input type="date" id="filtro-data" class="form-control" value="{{ $filtros['data'] ?? '' }}">
                </div>
            </div>

            @if ($salas->isNotEmpty())
                <div class="d-flex flex-wrap gap-3 mt-3 small text-secondary">
                    @foreach ($salas as $sala)
                        <span class="d-inline-flex align-items-center gap-1">
                            <span class="rounded-circle" style="width: .7rem; height: .7rem; background: {{ $sala->cor }};"></span>
                            {{ $sala->nome }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <div class="btn-group btn-group-sm" role="group" aria-label="Modo de visualização da agenda">
                    <button type="button" class="btn btn-outline-primary" data-modo-agenda="salas" aria-pressed="false">
                        <i class="bi bi-layout-three-columns me-1"></i>Salas lado a lado
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-modo-agenda="calendario" aria-pressed="false">
                        <i class="bi bi-calendar3 me-1"></i>Calendário
                    </button>
                </div>
            </div>

            {{-- Modo "Salas lado a lado": um calendário de dia por sala. --}}
            <div id="salas-lado-a-lado" class="d-none"
                 data-salas='@json($salas->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome, 'cor' => $s->cor])->values())'>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <div class="btn-group" role="group" aria-label="Navegar entre os dias">
                        <button type="button" class="btn btn-outline-secondary" data-salas-acao="anterior" aria-label="Dia anterior">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-salas-acao="proximo" aria-label="Próximo dia">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-salas-acao="hoje">Hoje</button>
                    <h2 id="titulo-salas" class="h6 mb-0 ms-md-2 fw-semibold"></h2>
                </div>
                <div id="colunas-salas" class="agenda-salas"></div>
            </div>

            {{-- Modo "Calendário": dia, semana, mês e lista. --}}
            <div id="calendario"
                 data-eventos-url="{{ route('agenda.eventos') }}"
                 data-criar-url="{{ route('agendamentos.create') }}"
                 data-data-inicial="{{ $filtros['data'] ?? '' }}"></div>
        </div>
    </div>

    {{-- Detalhes do agendamento (preenchido via JavaScript). --}}
    <div class="modal fade" id="modal-evento" tabindex="-1" aria-labelledby="modal-evento-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 d-flex align-items-center gap-2" id="modal-evento-titulo">
                        <span class="rounded-circle" data-campo="cor" style="width: .9rem; height: .9rem;"></span>
                        <span data-campo="sala"></span>
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-secondary small" data-campo="data"></div>
                    <div class="fs-5 fw-semibold mb-3" data-campo="horario"></div>
                    <dl class="row mb-0">
                        <dt class="col-4 text-secondary fw-normal">Profissional</dt>
                        <dd class="col-8" data-campo="profissional"></dd>
                        <dt class="col-4 text-secondary fw-normal" data-bloco="descricao">Descrição</dt>
                        <dd class="col-8" data-bloco="descricao" data-campo="descricao" style="white-space: pre-line;"></dd>
                        <dt class="col-4 text-secondary fw-normal" data-bloco="situacao">Atendimento</dt>
                        <dd class="col-8" data-bloco="situacao" data-campo="situacao"></dd>
                    </dl>
                    <p class="text-secondary small mb-0" data-bloco="privado">
                        <i class="bi bi-lock me-1"></i>Horário reservado por outro profissional.
                    </p>

                    <form method="POST" class="mt-3 d-none" data-bloco="cancelar" id="form-cancelar-evento">
                        @csrf
                        @method('PATCH')
                        <label for="evento-motivo" class="form-label small">Motivo do cancelamento (opcional)</label>
                        <input type="text" id="evento-motivo" name="motivo_cancelamento" maxlength="255" class="form-control form-control-sm">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" form="form-cancelar-evento" class="btn btn-outline-danger me-auto" data-bloco="cancelar">
                        Cancelar agendamento
                    </button>
                    <a href="#" class="btn btn-outline-primary" data-bloco="editar">Editar</a>
                    <a href="#" class="btn btn-primary" data-bloco="detalhes">Ver detalhes</a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/agenda.js')
    @endpush
</x-layouts.app>
