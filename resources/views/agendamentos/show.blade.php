<x-layouts.app title="Agendamento">
    <x-page-header titulo="Detalhes do agendamento">
        <x-slot:acoes>
            <a href="{{ route('agendamentos.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
            @can('update', $agendamento)
                <a href="{{ route('agendamentos.edit', $agendamento) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
            @endcan
            @can('cancel', $agendamento)
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-cancelar">
                    <i class="bi bi-x-circle me-1"></i>Cancelar agendamento
                </button>
            @endcan
        </x-slot:acoes>
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100" style="border-left: 4px solid {{ $agendamento->sala->cor }};">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="text-secondary small">{{ ucfirst($agendamento->inicio->translatedFormat('l, d \d\e F \d\e Y')) }}</div>
                            <div class="fs-4 fw-semibold">{{ $agendamento->horario() }}</div>
                        </div>
                        @php([$rotulo, $badge] = $agendamento->rotuloSituacao())
                        <span class="badge fs-6 {{ $badge }}">{{ $rotulo }}</span>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-secondary fw-normal">Sala</dt>
                        <dd class="col-sm-9">{{ $agendamento->sala->nome }}</dd>

                        <dt class="col-sm-3 text-secondary fw-normal">Profissional</dt>
                        <dd class="col-sm-9">
                            {{ $agendamento->profissional->nome }}
                            @if ($agendamento->profissional->profissao)
                                <span class="text-secondary">— {{ $agendamento->profissional->profissao }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3 text-secondary fw-normal">Descrição</dt>
                        <dd class="col-sm-9" style="white-space: pre-line;">{{ $agendamento->descricao }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold">Histórico</div>
                <div class="card-body small">
                    @if ($agendamento->recorrencia)
                        <p class="mb-2">
                            <i class="bi bi-arrow-repeat text-primary me-1"></i>
                            Série {{ mb_strtolower($agendamento->recorrencia->frequencia->label()) }} ·
                            <a href="{{ route('agendamentos.index', ['recorrencia_id' => $agendamento->recorrencia_id, 'periodo' => 'todos']) }}">ver todos da série</a>
                        </p>
                    @endif
                    <p class="mb-2">
                        <i class="bi bi-plus-circle text-success me-1"></i>
                        Criado em {{ $agendamento->created_at->format('d/m/Y H:i') }}
                        por {{ $agendamento->criador->nome }}
                    </p>
                    @if ($agendamento->updated_at->gt($agendamento->created_at) && $agendamento->estaAgendado()
                        && ! $agendamento->situacao_marcada_em?->equalTo($agendamento->updated_at))
                        <p class="mb-2">
                            <i class="bi bi-pencil text-primary me-1"></i>
                            Atualizado em {{ $agendamento->updated_at->format('d/m/Y H:i') }}
                        </p>
                    @endif
                    @if ($agendamento->situacao_marcada_em)
                        <p class="mb-2">
                            <i class="bi bi-clipboard-check text-primary me-1"></i>
                            Atendimento marcado como "{{ $agendamento->situacao->label() }}"
                            em {{ $agendamento->situacao_marcada_em->format('d/m/Y H:i') }}
                            por {{ $agendamento->situacaoMarcadaPor->nome }}
                        </p>
                    @endif
                    @unless ($agendamento->estaAgendado())
                        <p class="mb-1">
                            <i class="bi bi-x-circle text-danger me-1"></i>
                            Cancelado em {{ $agendamento->cancelado_em->format('d/m/Y H:i') }}
                            por {{ $agendamento->canceladoPor->nome }}
                        </p>
                        @if ($agendamento->motivo_cancelamento)
                            <p class="text-secondary mb-0 ms-3">Motivo: {{ $agendamento->motivo_cancelamento }}</p>
                        @endif
                    @endunless
                </div>
            </div>
        </div>

        @if ($agendamento->estaAgendado() && $agendamento->jaIniciou())
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-clipboard-check me-1"></i>Atendimento
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            Situação:
                            <span class="badge {{ $agendamento->situacao->badge() }}">{{ $agendamento->situacao->label() }}</span>
                        </p>
                        @if ($agendamento->observacao_atendimento)
                            <p class="text-secondary small mb-3">Observação: {{ $agendamento->observacao_atendimento }}</p>
                        @endif

                        @can('registrarAtendimento', $agendamento)
                            <form method="POST" action="{{ route('agendamentos.atendimento', $agendamento) }}" class="row g-2 align-items-end">
                                @csrf
                                @method('PATCH')
                                <div class="col-12 col-md">
                                    <label for="observacao_atendimento" class="form-label small mb-1">Observação (opcional)</label>
                                    <input type="text" id="observacao_atendimento" name="observacao_atendimento" maxlength="255"
                                           value="{{ old('observacao_atendimento', $agendamento->observacao_atendimento) }}"
                                           class="form-control" placeholder="Ex.: paciente faltou, atendimento remarcado...">
                                </div>
                                <div class="col-12 col-md-auto d-flex gap-2">
                                    <button type="submit" name="situacao" value="realizado" class="btn btn-primary flex-grow-1">
                                        <i class="bi bi-check2-circle me-1"></i>Realizado
                                    </button>
                                    <button type="submit" name="situacao" value="nao_realizado" class="btn btn-outline-danger flex-grow-1">
                                        <i class="bi bi-x-circle me-1"></i>Não realizado
                                    </button>
                                </div>
                            </form>
                        @else
                            @if ($agendamento->situacao === \App\Enums\SituacaoAtendimento::Pendente)
                                <p class="text-secondary small mb-0">
                                    O prazo para o profissional registrar este atendimento terminou. Procure o administrador.
                                </p>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        @endif
    </div>

    @can('cancel', $agendamento)
        <div class="modal fade" id="modal-cancelar" tabindex="-1" aria-labelledby="modal-cancelar-titulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('agendamentos.cancelar', $agendamento) }}" class="modal-content">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="modal-cancelar-titulo">Cancelar agendamento</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ $agendamento->sala->nome }} em {{ $agendamento->inicio->format('d/m/Y') }},
                            {{ $agendamento->horario() }}. O horário ficará livre para outros profissionais.
                        </p>
                        @if ($agendamento->recorrencia)
                            <div class="mb-3">
                                <span class="form-label d-block">Este agendamento faz parte de uma série. Cancelar:</span>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="escopo" id="escopo-este" value="este" checked>
                                    <label class="form-check-label" for="escopo-este">Somente este</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="escopo" id="escopo-proximos" value="proximos">
                                    <label class="form-check-label" for="escopo-proximos">Este e os próximos da série</label>
                                </div>
                            </div>
                        @endif
                        <label for="motivo_cancelamento" class="form-label">Motivo (opcional)</label>
                        <input type="text" id="motivo_cancelamento" name="motivo_cancelamento" maxlength="255" class="form-control">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
</x-layouts.app>
