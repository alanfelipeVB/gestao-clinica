{{-- Página provisória para validar o layout. Será substituída pelo login/dashboard na etapa de autenticação. --}}
<x-layouts.app title="Prévia do layout">
    <x-page-header titulo="Prévia do layout" subtitulo="Página provisória para validar a interface base.">
        <x-slot:acoes>
            <button type="button" class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            <button type="button" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo agendamento</button>
        </x-slot:acoes>
    </x-page-header>

    <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        Exemplo de mensagem de sucesso.
    </div>

    <div class="row g-3 mb-4">
        @foreach ([['people', 'Profissionais', 12], ['door-open', 'Salas ativas', 5], ['calendar-check', 'Agendamentos hoje', 8], ['clock', 'Salas ocupadas agora', 2]] as [$icone, $rotulo, $valor])
            <div class="col-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="rounded-3 bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fs-4"
                              style="width: 3rem; height: 3rem;">
                            <i class="bi bi-{{ $icone }}"></i>
                        </span>
                        <div>
                            <div class="text-secondary small">{{ $rotulo }}</div>
                            <div class="fs-4 fw-semibold">{{ $valor }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Próximos agendamentos</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Sala</th>
                                <th>Profissional</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>14:00 – 15:00</td>
                                <td>Sala 01</td>
                                <td>Dra. Ana Souza</td>
                                <td><span class="badge text-bg-success">Agendado</span></td>
                            </tr>
                            <tr>
                                <td>15:00 – 16:30</td>
                                <td>Sala de procedimentos</td>
                                <td>Dr. João Lima</td>
                                <td><span class="badge text-bg-secondary">Cancelado</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Formulário de exemplo</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="demo-sala">Sala</label>
                        <select id="demo-sala" class="form-select">
                            <option>Sala 01</option>
                            <option>Sala 02</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="demo-desc">Descrição</label>
                        <input id="demo-desc" type="text" class="form-control is-invalid" value="">
                        <div class="invalid-feedback">Exemplo de mensagem de erro no campo.</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="demo-ativo" checked>
                        <label class="form-check-label" for="demo-ativo">Ativa</label>
                    </div>
                    <button type="button" class="btn btn-primary w-100">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
