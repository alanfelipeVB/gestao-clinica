<x-layouts.app title="Gerenciar salas">
    <x-page-header titulo="Gerenciar salas" subtitulo="Cadastre e organize as salas disponíveis para agendamento.">
        <x-slot:acoes>
            <a href="{{ route('admin.salas.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nova sala
            </a>
        </x-slot:acoes>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.salas.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="busca" class="form-label small text-secondary mb-1">Buscar</label>
                    <input type="search" id="busca" name="busca" value="{{ $filtros['busca'] ?? '' }}"
                           class="form-control" placeholder="Nome da sala">
                </div>
                <div class="col-6 col-md-3">
                    <label for="status" class="form-label small text-secondary mb-1">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todas</option>
                        <option value="ativas" @selected(($filtros['status'] ?? '') === 'ativas')>Ativas</option>
                        <option value="inativas" @selected(($filtros['status'] ?? '') === 'inativas')>Inativas</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    @if (array_filter($filtros))
                        <a href="{{ route('admin.salas.index') }}" class="btn btn-link text-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sala</th>
                        <th class="d-none d-md-table-cell">Descrição</th>
                        <th class="text-center">Capacidade</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salas as $sala)
                        <tr @class(['text-secondary' => ! $sala->ativa])>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle flex-shrink-0" style="width: .85rem; height: .85rem; background: {{ $sala->cor }};"></span>
                                    <span class="fw-semibold">{{ $sala->nome }}</span>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell small">{{ Str::limit($sala->descricao, 80) ?: '—' }}</td>
                            <td class="text-center">{{ $sala->capacidade ?? '—' }}</td>
                            <td>
                                @if ($sala->ativa)
                                    <span class="badge text-bg-success">Ativa</span>
                                @else
                                    <span class="badge text-bg-secondary">Inativa</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.salas.edit', $sala) }}"
                                   class="btn btn-sm btn-outline-primary" title="Editar" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form method="POST" action="{{ route('admin.salas.status', $sala) }}" class="d-inline"
                                      data-confirm="{{ $sala->ativa ? 'Desativar' : 'Ativar' }} {{ $sala->nome }}?">
                                    @csrf
                                    @method('PATCH')
                                    @if ($sala->ativa)
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Desativar" data-bs-toggle="tooltip">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Ativar" data-bs-toggle="tooltip">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                <i class="bi bi-door-closed fs-2 d-block mb-2"></i>
                                Nenhuma sala encontrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($salas->hasPages())
            <div class="card-footer bg-white">
                {{ $salas->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
