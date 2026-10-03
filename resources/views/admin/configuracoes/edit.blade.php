<x-layouts.app title="Configurações">
    <x-page-header titulo="Configurações" subtitulo="Regras gerais aplicadas aos agendamentos." />

    <form method="POST" action="{{ route('admin.configuracoes.update') }}" novalidate>
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-calendar-range me-1"></i>Agendamentos
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="antecedencia_maxima_dias" class="form-label">Antecedência máxima</label>
                        <div class="input-group has-validation">
                            <input type="number" id="antecedencia_maxima_dias" name="antecedencia_maxima_dias"
                                   value="{{ old('antecedencia_maxima_dias', $antecedenciaMaximaDias) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('antecedencia_maxima_dias')])
                                   min="1" max="365" required>
                            <span class="input-group-text">dias</span>
                            @error('antecedencia_maxima_dias') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">
                            Até quantos dias à frente os profissionais podem agendar uma sala (de 1 a 365).
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="antecedencia_recorrencia_dias" class="form-label">Antecedência para recorrência</label>
                        <div class="input-group has-validation">
                            <input type="number" id="antecedencia_recorrencia_dias" name="antecedencia_recorrencia_dias"
                                   value="{{ old('antecedencia_recorrencia_dias', $antecedenciaRecorrenciaDias) }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('antecedencia_recorrencia_dias')])
                                   min="7" max="365" required>
                            <span class="input-group-text">dias</span>
                            @error('antecedencia_recorrencia_dias') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">
                            Até quantos dias à frente uma série recorrente pode gerar agendamentos (de 7 a 365).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>Salvar configurações
            </button>
        </div>
    </form>
</x-layouts.app>
