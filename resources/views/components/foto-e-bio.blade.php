{{-- Foto e mini biografia exibidas na página inicial. --}}
@props(['user'])

<div class="row g-3 align-items-start">
    <div class="col-12 col-md-auto text-center">
        @if ($user->urlFoto())
            <img src="{{ $user->urlFoto() }}" alt="Foto de {{ $user->nome }}" class="rounded-circle border"
                 style="width: 6rem; height: 6rem; object-fit: cover;">
        @else
            <span class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fs-3 fw-semibold"
                  style="width: 6rem; height: 6rem;">
                {{ $user->exists ? $user->iniciais() : '?' }}
            </span>
        @endif
    </div>
    <div class="col">
        <label for="foto" class="form-label">{{ $user->foto ? 'Trocar foto' : 'Foto' }}</label>
        <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp"
               @class(['form-control', 'is-invalid' => $errors->has('foto')])>
        @error('foto') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">PNG, JPG ou WebP, até 2 MB. Prefira uma foto quadrada.</div>
        @if ($user->foto)
            <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" id="remover_foto" name="remover_foto" value="1">
                <label class="form-check-label small" for="remover_foto">Remover foto atual</label>
            </div>
        @endif
    </div>
    <div class="col-12">
        <label for="bio" class="form-label">Mini biografia</label>
        <textarea id="bio" name="bio" rows="3" maxlength="500"
                  @class(['form-control', 'is-invalid' => $errors->has('bio')])
                  placeholder="Formação, especialidades, abordagem...">{{ old('bio', $user->bio) }}</textarea>
        @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Até 500 caracteres.</div>
    </div>
</div>
