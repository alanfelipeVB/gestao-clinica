{{-- Campos compartilhados entre cadastro e edição de salas. Espera: $sala --}}
<div class="card mb-3">
    <div class="card-body row g-3">
        <div class="col-12 col-md-6">
            <label for="nome" class="form-label">Nome da sala <span class="text-danger">*</span></label>
            <input type="text" id="nome" name="nome" value="{{ old('nome', $sala->nome) }}"
                   @class(['form-control', 'is-invalid' => $errors->has('nome')]) required maxlength="100"
                   placeholder="Ex.: Sala 03, Sala de procedimentos">
            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-6 col-md-3">
            <label for="capacidade" class="form-label">Capacidade</label>
            <input type="number" id="capacidade" name="capacidade" value="{{ old('capacidade', $sala->capacidade) }}"
                   @class(['form-control', 'is-invalid' => $errors->has('capacidade')]) min="1" max="999"
                   placeholder="Pessoas">
            @error('capacidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-6 col-md-3">
            <label for="cor" class="form-label">Cor na agenda <span class="text-danger">*</span></label>
            <input type="color" id="cor" name="cor" value="{{ old('cor', $sala->cor) }}"
                   @class(['form-control form-control-color w-100', 'is-invalid' => $errors->has('cor')])
                   title="Escolha a cor da sala">
            @error('cor') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="descricao" class="form-label">Descrição</label>
            <textarea id="descricao" name="descricao" rows="3" maxlength="1000"
                      @class(['form-control', 'is-invalid' => $errors->has('descricao')])
                      placeholder="Equipamentos, finalidade, observações...">{{ old('descricao', $sala->descricao) }}</textarea>
            @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.salas.index') }}" class="btn btn-light">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i>{{ $sala->exists ? 'Salvar alterações' : 'Cadastrar sala' }}
    </button>
</div>
