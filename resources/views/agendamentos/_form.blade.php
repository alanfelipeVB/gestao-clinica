{{-- Campos compartilhados entre criação e edição. Espera: $agendamento, $salas, $profissionais, $horarios, $dataLimite, $preenchimento --}}
@php
    $editando = $agendamento->exists;
    $valor = fn (string $campo, $padrao = null) => old($campo, $preenchimento[$campo] ?? $padrao);

    $salaAtual = (int) old('sala_id', $agendamento->sala_id);
    $userAtual = (int) old('user_id', $agendamento->user_id);
    $data = $valor('data', $agendamento->inicio?->format('Y-m-d'));
    $horaInicio = $valor('hora_inicio', $agendamento->inicio?->format('H:i') ?? '08:00');
    $horaFim = $valor('hora_fim', $agendamento->fim?->format('H:i') ?? '09:00');
@endphp

<div class="card mb-3">
    <div class="card-body row g-3">
        @if (auth()->user()->isAdmin())
            <div class="col-12 col-md-6">
                <label for="user_id" class="form-label">Profissional <span class="text-danger">*</span></label>
                <select id="user_id" name="user_id" @class(['form-select', 'is-invalid' => $errors->has('user_id')]) required>
                    @foreach ($profissionais as $profissional)
                        <option value="{{ $profissional->id }}" @selected($userAtual === $profissional->id)>
                            {{ $profissional->nome }}{{ $profissional->profissao ? ' — '.$profissional->profissao : '' }}{{ $profissional->ativo ? '' : ' (inativo)' }}
                        </option>
                    @endforeach
                </select>
                @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endif

        <div class="col-12 col-md-6">
            <label for="sala_id" class="form-label">Sala <span class="text-danger">*</span></label>
            <select id="sala_id" name="sala_id" @class(['form-select', 'is-invalid' => $errors->has('sala_id')]) required>
                <option value="">Selecione a sala</option>
                @foreach ($salas as $sala)
                    <option value="{{ $sala->id }}" @selected($salaAtual === $sala->id)>
                        {{ $sala->nome }}{{ $sala->capacidade ? ' ('.$sala->capacidade.' pessoas)' : '' }}{{ $sala->ativa ? '' : ' (inativa)' }}
                    </option>
                @endforeach
            </select>
            @error('sala_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
            <label for="data" class="form-label">Data <span class="text-danger">*</span></label>
            <input type="date" id="data" name="data" value="{{ $data }}"
                   min="{{ today()->format('Y-m-d') }}" max="{{ $dataLimite->format('Y-m-d') }}"
                   @class(['form-control', 'is-invalid' => $errors->has('data')]) required>
            @error('data') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Até {{ $dataLimite->format('d/m/Y') }}.</div>
        </div>

        <div class="col-6 col-md-4">
            <label for="hora_inicio" class="form-label">Início <span class="text-danger">*</span></label>
            <select id="hora_inicio" name="hora_inicio" @class(['form-select', 'is-invalid' => $errors->has('hora_inicio')]) required>
                @foreach ($horarios as $horario)
                    <option value="{{ $horario }}" @selected($horaInicio === $horario)>{{ $horario }}</option>
                @endforeach
            </select>
            @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-6 col-md-4">
            <label for="hora_fim" class="form-label">Término <span class="text-danger">*</span></label>
            <select id="hora_fim" name="hora_fim" @class(['form-select', 'is-invalid' => $errors->has('hora_fim')]) required>
                @foreach ($horarios as $horario)
                    <option value="{{ $horario }}" @selected($horaFim === $horario)>{{ $horario }}</option>
                @endforeach
            </select>
            @error('hora_fim') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
            <textarea id="descricao" name="descricao" rows="3" maxlength="1000" required
                      @class(['form-control', 'is-invalid' => $errors->has('descricao')])
                      placeholder="O que será realizado na sala (ex.: atendimento e avaliação de paciente).">{{ old('descricao', $agendamento->descricao) }}</textarea>
            @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

@unless ($editando)
    @php
        $repetir = (bool) old('repetir');
        $fimTipo = old('fim_tipo', 'ocorrencias');
    @endphp
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-check form-switch">
                <input type="hidden" name="repetir" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="repetir" name="repetir" value="1" @checked($repetir)>
                <label class="form-check-label fw-semibold" for="repetir">
                    <i class="bi bi-arrow-repeat me-1"></i>Repetir este agendamento
                </label>
            </div>

            <div id="campos-repeticao" class="row g-3 mt-1 @unless ($repetir) d-none @endunless">
                <div class="col-12 col-md-4">
                    <label for="frequencia" class="form-label">Frequência</label>
                    <select id="frequencia" name="frequencia" @class(['form-select', 'is-invalid' => $errors->has('frequencia')])>
                        @foreach (\App\Enums\FrequenciaRecorrencia::cases() as $frequencia)
                            <option value="{{ $frequencia->value }}" @selected(old('frequencia', 'semanal') === $frequencia->value)>{{ $frequencia->label() }}</option>
                        @endforeach
                    </select>
                    @error('frequencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-8">
                    <span class="form-label d-block">Termina</span>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="radio" name="fim_tipo" id="fim_ocorrencias" value="ocorrencias" @checked($fimTipo === 'ocorrencias')>
                            <label for="fim_ocorrencias" class="form-check-label">Após</label>
                            <input type="number" name="ocorrencias" value="{{ old('ocorrencias', 4) }}" min="2" max="{{ \App\Services\RecorrenciaService::MAX_OCORRENCIAS }}"
                                   @class(['form-control form-control-sm', 'is-invalid' => $errors->has('ocorrencias')]) style="width: 5rem;" aria-label="Número de ocorrências">
                            <span>ocorrências</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="radio" name="fim_tipo" id="fim_data" value="data" @checked($fimTipo === 'data')>
                            <label for="fim_data" class="form-check-label">Em</label>
                            <input type="date" name="data_fim" value="{{ old('data_fim') }}" max="{{ $dataLimiteRecorrencia->format('Y-m-d') }}"
                                   @class(['form-control form-control-sm', 'is-invalid' => $errors->has('data_fim')]) aria-label="Data final">
                        </div>
                    </div>
                    @error('ocorrencias') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                    @error('data_fim') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                    <div class="form-text">
                        As repetições podem ir até {{ $dataLimiteRecorrencia->format('d/m/Y') }}.
                        Antes de salvar, você verá as datas e quais estão livres.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const chave = document.getElementById('repetir');
                const campos = document.getElementById('campos-repeticao');
                const botao = document.getElementById('botao-salvar');
                const atualizar = () => {
                    campos.classList.toggle('d-none', !chave.checked);
                    botao.innerHTML = chave.checked
                        ? '<i class="bi bi-calendar-week me-1"></i>Ver datas'
                        : '<i class="bi bi-check-lg me-1"></i>Agendar';
                };
                chave.addEventListener('change', atualizar);
                atualizar();
            });
        </script>
    @endpush
@endunless

<div class="d-flex justify-content-end gap-2">
    <a href="{{ $editando ? route('agendamentos.show', $agendamento) : route('agendamentos.index') }}" class="btn btn-light">Voltar</a>
    <button type="submit" class="btn btn-primary" id="botao-salvar">
        <i class="bi bi-check-lg me-1"></i>{{ $editando ? 'Salvar alterações' : 'Agendar' }}
    </button>
</div>
