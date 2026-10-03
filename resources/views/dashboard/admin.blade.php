<x-layouts.app title="Dashboard">
    <x-page-header :titulo="'Olá, '.auth()->user()->primeiroNome().'!'"
                   :subtitulo="ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y'))">
        <x-slot:acoes>
            <a href="{{ route('agenda') }}" class="btn btn-outline-primary">
                <i class="bi bi-calendar3 me-1"></i>Agenda
            </a>
            <a href="{{ route('agendamentos.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Novo agendamento
            </a>
        </x-slot:acoes>
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <x-stat-card icone="people" rotulo="Profissionais ativos" :valor="$totais['profissionais']"
                         :href="route('admin.profissionais.index', ['status' => 'ativos'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card icone="door-open" rotulo="Salas ativas" :valor="$totais['salas']"
                         :href="route('admin.salas.index', ['status' => 'ativas'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card icone="calendar-check" rotulo="Agendamentos hoje" :valor="$totais['agendamentos_hoje']"
                         :href="route('agendamentos.index', ['data' => today()->format('Y-m-d'), 'periodo' => 'todos'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card icone="clock-history" rotulo="Salas ocupadas agora"
                         :valor="$totais['salas_ocupadas'].' / '.$totais['salas']" />
        </div>
    </div>

    @if ($totalPendentes > 0)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>
                <i class="bi bi-clipboard-check me-1"></i>
                <strong>{{ $totalPendentes }}</strong> {{ $totalPendentes === 1 ? 'atendimento aguarda' : 'atendimentos aguardam' }} confirmação (realizado ou não).
            </span>
            <a href="{{ route('agendamentos.index', ['situacao' => 'pendente', 'periodo' => 'todos']) }}" class="btn btn-sm btn-warning">Ver pendentes</a>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-broadcast me-1"></i>Agora nas salas</span>
            <span class="small text-secondary">Atualizado às {{ now()->format('H:i') }}</span>
        </div>
        <div class="card-body">
            @if ($salas->isEmpty())
                <p class="text-secondary small mb-0">Nenhuma sala ativa cadastrada.</p>
            @else
                <div class="row g-3">
                    @foreach ($salas as $situacao)
                        @php($sala = $situacao['sala'])
                        <div class="col-12 col-md-6 col-xl-4">
                            <div @class(['border rounded-3 p-3 h-100', 'bg-danger-subtle border-danger-subtle' => $situacao['atual']])
                                 style="border-left: 4px solid {{ $sala->cor }} !important;">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="fw-semibold">{{ $sala->nome }}</span>
                                    @if ($situacao['atual'])
                                        <span class="badge text-bg-danger">Ocupada</span>
                                    @else
                                        <span class="badge text-bg-success">Livre</span>
                                    @endif
                                </div>
                                <div class="small text-secondary">
                                    @if ($situacao['atual'])
                                        {{ $situacao['atual']->profissional->nome }} até {{ $situacao['atual']->fim->format('H:i') }}
                                    @elseif ($situacao['proximo'])
                                        Próximo: {{ $situacao['proximo']->horario() }} · {{ $situacao['proximo']->profissional->nome }}
                                    @else
                                        Sem mais agendamentos hoje.
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-calendar-day me-1"></i>Agendamentos de hoje
                </div>
                <x-lista-agendamentos :agendamentos="$hoje" vazio="Nenhum agendamento para hoje." />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-calendar-week me-1"></i>Próximos 7 dias</span>
                    <a href="{{ route('agendamentos.index') }}" class="small">Ver todos</a>
                </div>
                <x-lista-agendamentos :agendamentos="$proximos" :mostrar-data="true" vazio="Nenhum agendamento nos próximos dias." />
            </div>
        </div>
    </div>
</x-layouts.app>
