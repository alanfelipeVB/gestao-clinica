{{-- Lista compacta de agendamentos para os dashboards. --}}
@props(['agendamentos', 'vazio' => 'Nenhum agendamento.', 'mostrarData' => false, 'mostrarProfissional' => true])

@if ($agendamentos->isEmpty())
    <div class="text-center text-secondary py-4 small">
        <i class="bi bi-calendar-x fs-3 d-block mb-1"></i>
        {{ $vazio }}
    </div>
@else
    <ul class="list-group list-group-flush">
        @foreach ($agendamentos as $agendamento)
            <li class="list-group-item d-flex align-items-center gap-3 px-3">
                <span class="rounded-pill flex-shrink-0" style="width: 4px; align-self: stretch; background: {{ $agendamento->sala->cor }};"></span>
                <div class="text-nowrap small" style="min-width: 5.5rem;">
                    @if ($mostrarData)
                        <div class="text-secondary">{{ ucfirst($agendamento->inicio->translatedFormat('D, d/m')) }}</div>
                    @endif
                    <div class="fw-semibold">{{ $agendamento->horario() }}</div>
                </div>
                <div class="flex-grow-1 small text-truncate">
                    <div class="fw-semibold text-truncate">{{ $agendamento->sala->nome }}</div>
                    <div class="text-secondary text-truncate">
                        {{ $mostrarProfissional ? $agendamento->profissional->nome : Str::limit($agendamento->descricao, 50) }}
                    </div>
                </div>
                <a href="{{ route('agendamentos.show', $agendamento) }}" class="btn btn-sm btn-light" title="Detalhes" aria-label="Detalhes">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        @endforeach
    </ul>
@endif
