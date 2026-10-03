<x-layouts.app title="Dashboard">
    <x-page-header :titulo="'Olá, '.auth()->user()->primeiroNome().'!'"
                   :subtitulo="ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y'))" />

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <a href="{{ route('agendamentos.create') }}" class="card h-100 text-decoration-none bg-primary text-white">
                <div class="card-body d-flex align-items-center gap-3 py-4">
                    <i class="bi bi-calendar-plus fs-1"></i>
                    <div>
                        <div class="fs-5 fw-semibold">Novo agendamento</div>
                        <div class="small text-white-50">Reserve uma sala para o seu atendimento.</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6">
            <a href="{{ route('agenda') }}" class="card h-100 text-decoration-none text-body">
                <div class="card-body d-flex align-items-center gap-3 py-4">
                    <i class="bi bi-calendar3 fs-1 text-primary"></i>
                    <div>
                        <div class="fs-5 fw-semibold">Ver agenda das salas</div>
                        <div class="small text-secondary">Consulte os horários livres por dia, semana ou mês.</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    @if ($totalPendentes > 0)
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-clipboard-check me-1"></i>Atendimentos para confirmar</span>
                <span class="badge text-bg-warning">{{ $totalPendentes }}</span>
            </div>
            <div class="card-body pb-0 small text-secondary">
                Informe se cada atendimento foi realizado. Você tem até {{ \App\Policies\AgendamentoPolicy::PRAZO_CORRECAO_DIAS }} dias após o término.
            </div>
            <x-lista-agendamentos :agendamentos="$pendentes" :mostrar-data="true" :mostrar-profissional="false" />
            @if ($totalPendentes > $pendentes->count())
                <div class="card-footer bg-white text-end">
                    <a href="{{ route('agendamentos.index', ['situacao' => 'pendente', 'periodo' => 'todos']) }}" class="small">Ver todos</a>
                </div>
            @endif
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-calendar-day me-1"></i>Meus agendamentos de hoje</span>
                    <span class="badge text-bg-primary">{{ $hoje->count() }}</span>
                </div>
                <x-lista-agendamentos :agendamentos="$hoje" :mostrar-profissional="false" vazio="Você não tem agendamentos hoje." />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-calendar-week me-1"></i>Meus próximos agendamentos</span>
                    <a href="{{ route('agendamentos.index') }}" class="small">Ver todos</a>
                </div>
                <x-lista-agendamentos :agendamentos="$proximos" :mostrar-data="true" :mostrar-profissional="false"
                                      vazio="Nenhum agendamento futuro." />
            </div>
        </div>
    </div>
</x-layouts.app>
