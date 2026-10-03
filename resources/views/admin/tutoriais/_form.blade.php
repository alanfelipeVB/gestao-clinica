{{-- Campos compartilhados entre cadastro e edição. Espera: $tutorial --}}
@php($editando = $tutorial->exists)

<div class="card mb-3">
    <div class="card-body row g-3">
        <div class="col-12 col-md-9">
            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
            <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $tutorial->titulo) }}" maxlength="150" required
                   @class(['form-control', 'is-invalid' => $errors->has('titulo')])>
            @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-6 col-md-3">
            <label for="ordem" class="form-label">Ordem</label>
            <input type="number" id="ordem" name="ordem" value="{{ old('ordem', $tutorial->ordem ?? 0) }}" min="0" max="999"
                   @class(['form-control', 'is-invalid' => $errors->has('ordem')])>
            @error('ordem') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Menor aparece primeiro.</div>
        </div>

        <div class="col-12">
            <label for="descricao" class="form-label">Descrição</label>
            <textarea id="descricao" name="descricao" rows="3" maxlength="2000"
                      @class(['form-control', 'is-invalid' => $errors->has('descricao')])>{{ old('descricao', $tutorial->descricao) }}</textarea>
            @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="video" class="form-label">
                {{ $editando ? 'Substituir vídeo' : 'Vídeo' }}
                @unless ($editando) <span class="text-danger">*</span> @endunless
            </label>
            <input type="file" id="video" name="video" accept="video/mp4,video/webm,.mp4,.webm" @required(! $editando)
                   @class(['form-control', 'is-invalid' => $errors->has('video')])>
            @error('video') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">
                MP4 ou WebM, até 500 MB.
                @if ($editando)
                    Deixe em branco para manter o vídeo atual ({{ $tutorial->tamanhoFormatado() }}).
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="form-check form-switch">
                <input type="hidden" name="publicado" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="publicado" name="publicado" value="1"
                       @checked(old('publicado', $tutorial->publicado))>
                <label class="form-check-label" for="publicado">Publicado (visível para os profissionais)</label>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.tutoriais.index') }}" class="btn btn-light">Cancelar</a>
    <button type="submit" class="btn btn-primary" id="botao-enviar-tutorial">
        <i class="bi bi-cloud-upload me-1"></i>{{ $editando ? 'Salvar alterações' : 'Enviar tutorial' }}
    </button>
</div>

@push('scripts')
    <script>
        // Vídeos grandes demoram para enviar: evita clique duplo e mostra que o envio está em andamento.
        document.getElementById('botao-enviar-tutorial').closest('form').addEventListener('submit', (event) => {
            const botao = document.getElementById('botao-enviar-tutorial');
            botao.disabled = true;
            botao.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';
        });
    </script>
@endpush
