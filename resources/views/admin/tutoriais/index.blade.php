<x-layouts.app title="Gerenciar tutoriais">
    <x-page-header titulo="Gerenciar tutoriais" subtitulo="Vídeos disponibilizados para os profissionais.">
        <x-slot:acoes>
            <a href="{{ route('admin.tutoriais.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Novo tutorial
            </a>
        </x-slot:acoes>
    </x-page-header>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center d-none d-md-table-cell">Ordem</th>
                        <th>Tutorial</th>
                        <th class="d-none d-md-table-cell">Assistido por</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tutoriais as $tutorial)
                        <tr @class(['text-secondary' => ! $tutorial->publicado])>
                            <td class="text-center d-none d-md-table-cell">{{ $tutorial->ordem }}</td>
                            <td>
                                <div class="fw-semibold">{{ $tutorial->titulo }}</div>
                                <div class="small text-secondary">{{ $tutorial->tamanhoFormatado() }} · {{ str($tutorial->mime)->after('/')->upper() }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <a href="{{ route('admin.tutoriais.show', $tutorial) }}" class="text-decoration-none">
                                    {{ $tutorial->assistido_por_count }} de {{ $totalProfissionais }} profissionais
                                </a>
                            </td>
                            <td>
                                @if ($tutorial->publicado)
                                    <span class="badge text-bg-success">Publicado</span>
                                @else
                                    <span class="badge text-bg-secondary">Rascunho</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('tutoriais.show', $tutorial) }}" class="btn btn-sm btn-outline-secondary" title="Assistir" data-bs-toggle="tooltip">
                                    <i class="bi bi-play-fill"></i>
                                </a>
                                <a href="{{ route('admin.tutoriais.edit', $tutorial) }}" class="btn btn-sm btn-outline-primary" title="Editar" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.tutoriais.publicacao', $tutorial) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $tutorial->publicado ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            title="{{ $tutorial->publicado ? 'Ocultar' : 'Publicar' }}" data-bs-toggle="tooltip">
                                        <i class="bi bi-{{ $tutorial->publicado ? 'eye-slash' : 'eye' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.tutoriais.destroy', $tutorial) }}" class="d-inline"
                                      data-confirm="Excluir o tutorial &quot;{{ $tutorial->titulo }}&quot;? O vídeo será apagado definitivamente.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir" data-bs-toggle="tooltip">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                <i class="bi bi-collection-play fs-2 d-block mb-2"></i>
                                Nenhum tutorial cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
