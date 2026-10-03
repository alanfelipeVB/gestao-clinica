@php($ehAdmin = auth()->user()->isAdmin())

<x-layouts.app title="Agendamentos">
    <x-page-header titulo="Agendamentos"
                   :subtitulo="$ehAdmin ? 'Todos os agendamentos da clínica.' : 'Seus agendamentos de salas.'">
        <x-slot:acoes>
            <a href="{{ route('agendamentos.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Novo agendamento
            </a>
        </x-slot:acoes>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('agendamentos.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="periodo" class="form-label small text-secondary mb-1">Período</label>
                    <select id="periodo" name="periodo" class="form-select">
                        <option value="proximos" @selected($filtros['periodo'] === 'proximos')>Próximos</option>
                        <option value="anteriores" @selected($filtros['periodo'] === 'anteriores')>Anteriores</option>
                        <option value="todos" @selected($filtros['periodo'] === 'todos')>Todos</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="data" class="form-label small text-secondary mb-1">Data</label>
                    <input type="date" id="data" name="data" value="{{ $filtros['data'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="sala_id" class="form-label small text-secondary mb-1">Sala</label>
                    <select id="sala_id" name="sala_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($salas as $sala)
                            <option value="{{ $sala->id }}" @selected((int) ($filtros['sala_id'] ?? 0) === $sala->id)>{{ $sala->nome }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($ehAdmin)
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="user_id" class="form-label small text-secondary mb-1">Profissional</label>
                        <select id="user_id" name="user_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($profissionais as $profissional)
                                <option value="{{ $profissional->id }}" @selected((int) ($filtros['user_id'] ?? 0) === $profissional->id)>{{ $profissional->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="status" class="form-label small text-secondary mb-1">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\StatusAgendamento::cases() as $status)
                            <option value="{{ $status->value }}" @selected(($filtros['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="situacao" class="form-label small text-secondary mb-1">Atendimento</label>
                    <select id="situacao" name="situacao" class="form-select">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\SituacaoAtendimento::cases() as $situacao)
                            <option value="{{ $situacao->value }}" @selected(($filtros['situacao'] ?? '') === $situacao->value)>{{ $situacao->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div @class(['col-12 d-flex gap-2', 'col-md-8 col-xl-12 justify-content-xl-end' => $ehAdmin, 'col-md-4 col-xl-2' => ! $ehAdmin])>
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    @if (count(array_filter(Arr::except($filtros, 'periodo'))) || $filtros['periodo'] !== 'proximos')
                        <a href="{{ route('agendamentos.index') }}" class="btn btn-link text-secondary">Limpar</a>
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
                        <th>Data e horário</th>
                        <th>Sala</th>
                        @if ($ehAdmin)
                            <th class="d-none d-md-table-cell">Profissional</th>
                        @endif
                        <th class="d-none d-xl-table-cell">Descrição</th>
                        <th class="d-none d-sm-table-cell">Situação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agendamentos as $agendamento)
                        <tr @class(['text-secondary' => ! $agendamento->estaAgendado()])>
                            <td class="text-nowrap">
                                <div class="small text-secondary">{{ $agendamento->inicio->format('d/m/Y') }}</div>
                                <div class="fw-semibold">{{ $agendamento->horario() }}</div>
                            </td>
                            <td>
                                <span class="d-inline-flex align-items-center gap-2">
                                    <span class="rounded-circle flex-shrink-0" style="width: .7rem; height: .7rem; background: {{ $agendamento->sala->cor }};"></span>
                                    {{ $agendamento->sala->nome }}
                                </span>
                                @if ($ehAdmin)
                                    <div class="small text-secondary d-md-none">{{ $agendamento->profissional->nome }}</div>
                                @endif
                                {{-- Em telas pequenas, o status aparece aqui. --}}
                                <div class="d-sm-none">
                                    <span class="badge {{ $agendamento->rotuloSituacao()[1] }}">{{ $agendamento->rotuloSituacao()[0] }}</span>
                                </div>
                            </td>
                            @if ($ehAdmin)
                                <td class="d-none d-md-table-cell">{{ $agendamento->profissional->nome }}</td>
                            @endif
                            <td class="d-none d-xl-table-cell small">{{ Str::limit($agendamento->descricao, 60) }}</td>
                            <td class="d-none d-sm-table-cell"><span class="badge {{ $agendamento->rotuloSituacao()[1] }}">{{ $agendamento->rotuloSituacao()[0] }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('agendamentos.show', $agendamento) }}" class="btn btn-sm btn-outline-secondary"
                                   title="Detalhes" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $agendamento)
                                    <a href="{{ route('agendamentos.edit', $agendamento) }}" class="btn btn-sm btn-outline-primary d-none d-sm-inline-block"
                                       title="Editar" data-bs-toggle="tooltip">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $ehAdmin ? 6 : 5 }}" class="text-center text-secondary py-5">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                Nenhum agendamento encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($agendamentos->hasPages())
            <div class="card-footer bg-white">
                {{ $agendamentos->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
