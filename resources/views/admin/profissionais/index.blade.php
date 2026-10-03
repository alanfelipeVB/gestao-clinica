<x-layouts.app title="Profissionais">
    <x-page-header titulo="Profissionais" subtitulo="Gerencie quem tem acesso ao sistema.">
        <x-slot:acoes>
            <a href="{{ route('admin.profissionais.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i>Novo profissional
            </a>
        </x-slot:acoes>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.profissionais.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="busca" class="form-label small text-secondary mb-1">Buscar</label>
                    <input type="search" id="busca" name="busca" value="{{ $filtros['busca'] ?? '' }}"
                           class="form-control" placeholder="Nome ou e-mail">
                </div>
                <div class="col-6 col-md-2">
                    <label for="status" class="form-label small text-secondary mb-1">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="ativos" @selected(($filtros['status'] ?? '') === 'ativos')>Ativos</option>
                        <option value="inativos" @selected(($filtros['status'] ?? '') === 'inativos')>Inativos</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="perfil" class="form-label small text-secondary mb-1">Perfil</label>
                    <select id="perfil" name="perfil" class="form-select">
                        <option value="">Todos</option>
                        @foreach (\App\Enums\PerfilUsuario::cases() as $perfil)
                            <option value="{{ $perfil->value }}" @selected(($filtros['perfil'] ?? '') === $perfil->value)>
                                {{ $perfil->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    @if (array_filter($filtros))
                        <a href="{{ route('admin.profissionais.index') }}" class="btn btn-link text-secondary">Limpar</a>
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
                        <th>Nome</th>
                        <th class="d-none d-md-table-cell">Contato</th>
                        <th class="d-none d-lg-table-cell">Profissão</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($profissionais as $profissional)
                        <tr @class(['text-secondary' => ! $profissional->ativo])>
                            <td>
                                <div class="fw-semibold">{{ $profissional->nome }}</div>
                                <div class="small text-secondary">
                                    {{ $profissional->email }}
                                    @if ($profissional->isAdmin())
                                        <span class="badge text-bg-primary ms-1">Admin</span>
                                    @endif
                                    @if ($profissional->exibir_no_site)
                                        <span class="badge text-bg-info ms-1" title="Exibido na página inicial">No site</span>
                                    @endif
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell small">
                                @if ($profissional->telefone)
                                    <div><i class="bi bi-telephone me-1"></i>{{ $profissional->telefone }}</div>
                                @endif
                                @if ($profissional->instagram)
                                    <div><i class="bi bi-instagram me-1"></i>{{ '@'.$profissional->instagram }}</div>
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell">{{ $profissional->profissao ?? '—' }}</td>
                            <td>
                                @if ($profissional->ativo)
                                    <span class="badge text-bg-success">Ativo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inativo</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.profissionais.edit', $profissional) }}"
                                   class="btn btn-sm btn-outline-primary" title="Editar" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                @unless ($profissional->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.profissionais.status', $profissional) }}" class="d-inline"
                                          data-confirm="{{ $profissional->ativo ? 'Desativar' : 'Ativar' }} {{ $profissional->nome }}?">
                                        @csrf
                                        @method('PATCH')
                                        @if ($profissional->ativo)
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Desativar" data-bs-toggle="tooltip">
                                                <i class="bi bi-person-x"></i>
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Ativar" data-bs-toggle="tooltip">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                        @endif
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                <i class="bi bi-people fs-2 d-block mb-2"></i>
                                Nenhum profissional encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($profissionais->hasPages())
            <div class="card-footer bg-white">
                {{ $profissionais->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
