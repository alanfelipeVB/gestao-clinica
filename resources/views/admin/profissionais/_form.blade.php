{{-- Campos compartilhados entre cadastro e edição de profissionais. Espera: $profissional --}}
@php
    $editando = $profissional->exists;
    $ehOProprioUsuario = $editando && $profissional->is(auth()->user());
    $perfilAtual = old('perfil', $profissional->perfil?->value);
@endphp

<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Dados pessoais</div>
    <div class="card-body row g-3">
        <div class="col-12 col-md-6">
            <label for="nome" class="form-label">Nome completo <span class="text-danger">*</span></label>
            <input type="text" id="nome" name="nome" value="{{ old('nome', $profissional->nome) }}"
                   @class(['form-control', 'is-invalid' => $errors->has('nome')]) required maxlength="255">
            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
            <label for="profissao" class="form-label">Profissão</label>
            <input type="text" id="profissao" name="profissao" value="{{ old('profissao', $profissional->profissao) }}"
                   @class(['form-control', 'is-invalid' => $errors->has('profissao')]) maxlength="100"
                   placeholder="Ex.: Psicóloga, Fisioterapeuta">
            @error('profissao') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $profissional->email) }}"
                   @class(['form-control', 'is-invalid' => $errors->has('email')]) required maxlength="255">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-6 col-md-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input type="tel" id="telefone" name="telefone" value="{{ old('telefone', $profissional->telefone) }}"
                   @class(['form-control', 'is-invalid' => $errors->has('telefone')]) maxlength="20"
                   placeholder="(00) 90000-0000">
            @error('telefone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-6 col-md-3">
            <label for="instagram" class="form-label">Instagram</label>
            <div class="input-group has-validation">
                <span class="input-group-text">@</span>
                <input type="text" id="instagram" name="instagram" value="{{ old('instagram', $profissional->instagram) }}"
                       @class(['form-control', 'is-invalid' => $errors->has('instagram')]) maxlength="31">
                @error('instagram') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Acesso</div>
    <div class="card-body row g-3">
        <div class="col-12 col-md-4">
            <label for="perfil" class="form-label">Perfil <span class="text-danger">*</span></label>
            <select id="perfil" name="perfil" @class(['form-select', 'is-invalid' => $errors->has('perfil')])
                    @disabled($ehOProprioUsuario)>
                @foreach (\App\Enums\PerfilUsuario::cases() as $perfil)
                    <option value="{{ $perfil->value }}" @selected($perfilAtual === $perfil->value)>{{ $perfil->label() }}</option>
                @endforeach
            </select>
            @if ($ehOProprioUsuario)
                {{-- Campo desabilitado não é enviado: mantém o perfil atual. --}}
                <input type="hidden" name="perfil" value="{{ $profissional->perfil->value }}">
                <div class="form-text">Você não pode alterar o seu próprio perfil.</div>
            @endif
            @error('perfil') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
            <label for="password" class="form-label">
                {{ $editando ? 'Nova senha' : 'Senha' }}
                @unless ($editando) <span class="text-danger">*</span> @endunless
            </label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                   @class(['form-control', 'is-invalid' => $errors->has('password')]) @required(! $editando)>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">
                {{ $editando ? 'Preencha apenas para redefinir a senha. ' : '' }}Mínimo de 8 caracteres.
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label for="password_confirmation" class="form-label">Confirmar senha</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                   class="form-control" @required(! $editando)>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.profissionais.index') }}" class="btn btn-light">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i>{{ $editando ? 'Salvar alterações' : 'Cadastrar profissional' }}
    </button>
</div>
