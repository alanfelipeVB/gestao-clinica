{{-- Confirmação de desativação quando existem agendamentos futuros.
     Espera: $titulo, $nome, $agendamentos, $action, $voltar, $mostrarProfissional (bool) --}}
<x-layouts.app :title="$titulo">
    <x-page-header :titulo="$titulo" :subtitulo="$nome" />

    <div class="alert alert-warning d-flex gap-2">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            Existem <strong>{{ $agendamentos->count() }} {{ $agendamentos->count() === 1 ? 'agendamento futuro' : 'agendamentos futuros' }}</strong>
            vinculados. Escolha o que fazer com eles antes de desativar.
        </div>
    </div>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>{{ $mostrarProfissional ? 'Profissional' : 'Sala' }}</th>
                        <th class="d-none d-md-table-cell">Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($agendamentos as $agendamento)
                        <tr>
                            <td>{{ $agendamento->inicio->format('d/m/Y') }}</td>
                            <td>{{ $agendamento->horario() }}</td>
                            <td>{{ $mostrarProfissional ? $agendamento->profissional->nome : $agendamento->sala->nome }}</td>
                            <td class="d-none d-md-table-cell small">{{ Str::limit($agendamento->descricao, 60) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2">
        <a href="{{ $voltar }}" class="btn btn-light">Voltar sem desativar</a>

        <form method="POST" action="{{ $action }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="acao_agendamentos" value="manter">
            <button type="submit" class="btn btn-outline-secondary">Desativar e manter agendamentos</button>
        </form>

        <form method="POST" action="{{ $action }}" data-confirm="Cancelar {{ $agendamentos->count() }} agendamento(s) e desativar?">
            @csrf
            @method('PATCH')
            <input type="hidden" name="acao_agendamentos" value="cancelar">
            <button type="submit" class="btn btn-danger">Desativar e cancelar agendamentos</button>
        </form>
    </div>
</x-layouts.app>
