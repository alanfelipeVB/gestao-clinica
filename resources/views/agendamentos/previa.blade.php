@php
    use App\Services\RecorrenciaService;

    $rotulos = [
        RecorrenciaService::LIVRE => ['Livre', 'text-bg-success', 'check-circle'],
        RecorrenciaService::CONFLITO => ['Conflito', 'text-bg-danger', 'x-circle'],
        RecorrenciaService::IGNORADA => ['Não será criada', 'text-bg-secondary', 'slash-circle'],
    ];
@endphp

<x-layouts.app title="Confirmar repetição">
    <x-page-header titulo="Confirmar agendamentos recorrentes"
                   subtitulo="Confira as datas. Somente as datas livres serão agendadas." />

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-sm-3 text-secondary fw-normal">Sala</dt>
                <dd class="col-sm-9">{{ $sala->nome }}</dd>
                <dt class="col-sm-3 text-secondary fw-normal">Profissional</dt>
                <dd class="col-sm-9">{{ $profissional->nome }}</dd>
                <dt class="col-sm-3 text-secondary fw-normal">Horário</dt>
                <dd class="col-sm-9">{{ $dados['inicio']->format('H:i') }} – {{ $dados['fim']->format('H:i') }}</dd>
                <dt class="col-sm-3 text-secondary fw-normal">Repetição</dt>
                <dd class="col-sm-9">
                    {{ $serie['frequencia']->label() }},
                    @if ($serie['data_fim'])
                        até {{ $serie['data_fim']->format('d/m/Y') }}
                    @else
                        {{ $serie['ocorrencias'] }} ocorrências
                    @endif
                </dd>
                <dt class="col-sm-3 text-secondary fw-normal">Descrição</dt>
                <dd class="col-sm-9 mb-0">{{ $dados['descricao'] }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Datas da série</span>
            <span class="small text-secondary">{{ $livres }} de {{ $previa->count() }} livres</span>
        </div>
        <ul class="list-group list-group-flush">
            @foreach ($previa as $item)
                @php([$rotulo, $badge, $icone] = $rotulos[$item['situacao']])
                <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                    <i class="bi bi-{{ $icone }} {{ $item['situacao'] === RecorrenciaService::LIVRE ? 'text-success' : 'text-secondary' }}"></i>
                    <span class="fw-semibold" style="min-width: 9rem;">{{ $item['referencia'] }}</span>
                    @if ($item['inicio'])
                        <span class="text-secondary small">{{ $item['inicio']->format('H:i') }} – {{ $item['fim']->format('H:i') }}</span>
                    @endif
                    <span class="badge {{ $badge }}">{{ $rotulo }}</span>
                    @if ($item['motivo'])
                        <span class="small text-secondary w-100 ms-4">{{ $item['motivo'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <form method="POST" action="{{ route('agendamentos.store') }}" class="d-flex flex-wrap justify-content-end gap-2">
        @csrf
        @foreach ($entrada as $campo => $valor)
            <input type="hidden" name="{{ $campo }}" value="{{ $valor }}">
        @endforeach
        <input type="hidden" name="confirmado" value="1">

        {{-- Volta ao formulário com os campos preenchidos (estado mantido pelo navegador). --}}
        <button type="button" class="btn btn-light" onclick="history.back()">Voltar e ajustar</button>
        @if ($livres > 0)
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>Confirmar {{ $livres }} {{ $livres === 1 ? 'agendamento' : 'agendamentos' }}
            </button>
        @else
            <button type="button" class="btn btn-primary" disabled>Nenhuma data livre</button>
        @endif
    </form>
</x-layouts.app>
