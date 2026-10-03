@php
    $ehAdmin = auth()->user()->isAdmin();
    $horas = fn (int $minutos) => intdiv($minutos, 60).'h'.str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT);
    $taxa = fn (?float $valor) => $valor === null ? '—' : number_format($valor, 1, ',', '').'%';
    $parametros = array_filter(['mes' => $mes->format('Y-m'), 'sala_id' => $salaId]);
    $mesAnterior = ['mes' => $mes->copy()->subMonth()->format('Y-m'), 'sala_id' => $salaId];
    $mesSeguinte = ['mes' => $mes->copy()->addMonth()->format('Y-m'), 'sala_id' => $salaId];
@endphp

<x-layouts.app title="Relatório">
    <x-page-header titulo="Relatório de atendimentos"
                   :subtitulo="$ehAdmin ? 'Atendimentos por profissional no mês.' : 'Seus atendimentos no mês.'">
        <x-slot:acoes>
            <a href="{{ route('relatorio.exportar', $parametros) }}" class="btn btn-outline-primary">
                <i class="bi bi-filetype-csv me-1"></i>Exportar CSV
            </a>
        </x-slot:acoes>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('relatorio.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="mes" class="form-label small text-secondary mb-1">Mês</label>
                    <div class="input-group">
                        <a href="{{ route('relatorio.index', array_filter($mesAnterior)) }}" class="btn btn-outline-secondary" title="Mês anterior" aria-label="Mês anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <input type="month" id="mes" name="mes" value="{{ $mes->format('Y-m') }}" class="form-control">
                        <a href="{{ route('relatorio.index', array_filter($mesSeguinte)) }}" class="btn btn-outline-secondary" title="Próximo mês" aria-label="Próximo mês">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label for="sala_id" class="form-label small text-secondary mb-1">Sala</label>
                    <select id="sala_id" name="sala_id" class="form-select">
                        <option value="">Todas as salas</option>
                        @foreach ($salas as $sala)
                            <option value="{{ $sala->id }}" @selected($salaId === $sala->id)>{{ $sala->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i>Aplicar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <h2 class="h5 mb-3">{{ ucfirst($mes->translatedFormat('F \d\e Y')) }}</h2>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <x-stat-card icone="check2-circle" rotulo="Atendimentos realizados" :valor="$totais['realizados']" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card icone="x-circle" rotulo="Não realizados" :valor="$totais['nao_realizados']" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card icone="percent" rotulo="Comparecimento" :valor="$taxa($totais['taxa'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card icone="hourglass-split" rotulo="Horas realizadas" :valor="$horas($totais['minutos_realizados'])" />
        </div>
    </div>

    @if ($totais['pendentes'] > 0)
        <div class="alert alert-warning small d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ $totais['pendentes'] }} {{ $totais['pendentes'] === 1 ? 'atendimento ainda não foi confirmado' : 'atendimentos ainda não foram confirmados' }}
            neste mês; os números podem mudar após a confirmação.
        </div>
    @endif

    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Profissional</th>
                        <th class="text-center d-none d-md-table-cell">Agendados</th>
                        <th class="text-center">Realizados</th>
                        <th class="text-center">Não realizados</th>
                        <th class="text-center d-none d-lg-table-cell">Pendentes</th>
                        <th class="text-center d-none d-lg-table-cell">A realizar</th>
                        <th class="text-center d-none d-md-table-cell">Cancelados</th>
                        <th class="text-center d-none d-sm-table-cell">Horas</th>
                        <th class="text-center">Comparecimento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($linhas as $linha)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $linha['profissional']->nome }}</div>
                                <div class="small text-secondary">
                                    {{ $linha['profissional']->profissao }}
                                    @unless ($linha['profissional']->ativo)
                                        <span class="badge text-bg-secondary">Inativo</span>
                                    @endunless
                                </div>
                            </td>
                            <td class="text-center d-none d-md-table-cell">{{ $linha['agendados'] }}</td>
                            <td class="text-center fw-semibold text-primary">{{ $linha['realizados'] }}</td>
                            <td class="text-center">{{ $linha['nao_realizados'] }}</td>
                            <td class="text-center d-none d-lg-table-cell">
                                @if ($linha['pendentes'] > 0)
                                    <span class="badge text-bg-warning">{{ $linha['pendentes'] }}</span>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="text-center d-none d-lg-table-cell">{{ $linha['futuros'] }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $linha['cancelados'] }}</td>
                            <td class="text-center d-none d-sm-table-cell text-nowrap">{{ $horas($linha['minutos_realizados']) }}</td>
                            <td class="text-center">{{ $taxa($linha['taxa']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-5">Nenhum profissional no período.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($ehAdmin && $linhas->count() > 1)
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td>Total</td>
                            <td class="text-center d-none d-md-table-cell">{{ $totais['agendados'] }}</td>
                            <td class="text-center text-primary">{{ $totais['realizados'] }}</td>
                            <td class="text-center">{{ $totais['nao_realizados'] }}</td>
                            <td class="text-center d-none d-lg-table-cell">{{ $totais['pendentes'] }}</td>
                            <td class="text-center d-none d-lg-table-cell">{{ $totais['futuros'] }}</td>
                            <td class="text-center d-none d-md-table-cell">{{ $totais['cancelados'] }}</td>
                            <td class="text-center d-none d-sm-table-cell text-nowrap">{{ $horas($totais['minutos_realizados']) }}</td>
                            <td class="text-center">{{ $taxa($totais['taxa']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        <div class="card-footer bg-white small text-secondary">
            <strong>Agendados</strong>: não cancelados ·
            <strong>Pendentes</strong>: já começaram e aguardam confirmação ·
            <strong>A realizar</strong>: ainda não começaram ·
            <strong>Comparecimento</strong>: realizados ÷ (realizados + não realizados)
        </div>
    </div>

    @unless ($ehAdmin)
        <div class="card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-list-check me-1"></i>Meus agendamentos no mês
            </div>
            <x-lista-agendamentos :agendamentos="$agendamentos" :mostrar-data="true" :mostrar-profissional="false"
                                  vazio="Nenhum agendamento neste mês." />
        </div>
    @endunless
</x-layouts.app>
