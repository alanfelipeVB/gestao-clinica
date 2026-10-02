<x-layouts.app title="Meu perfil">
    <x-page-header titulo="Meu perfil" :subtitulo="$user->email.' · '.$user->perfil->label()" />

    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('perfil.update') }}" class="card h-100" novalidate>
                @csrf
                @method('PUT')
                <div class="card-header bg-white fw-semibold">Dados pessoais</div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label for="nome" class="form-label">Nome completo <span class="text-danger">*</span></label>
                        <input type="text" id="nome" name="nome" value="{{ old('nome', $user->nome) }}" maxlength="255" required
                               @class(['form-control', 'is-invalid' => $errors->has('nome')])>
                        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="profissao" class="form-label">Profissão</label>
                        <input type="text" id="profissao" name="profissao" value="{{ old('profissao', $user->profissao) }}" maxlength="100"
                               @class(['form-control', 'is-invalid' => $errors->has('profissao')])>
                        @error('profissao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" value="{{ old('telefone', $user->telefone) }}" maxlength="20"
                               placeholder="(00) 90000-0000" @class(['form-control', 'is-invalid' => $errors->has('telefone')])>
                        @error('telefone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="instagram" class="form-label">Instagram</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">@</span>
                            <input type="text" id="instagram" name="instagram" value="{{ old('instagram', $user->instagram) }}" maxlength="31"
                                   @class(['form-control', 'is-invalid' => $errors->has('instagram')])>
                            @error('instagram') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                        <div class="form-text">
                            @if ($user->isAdmin())
                                Altere o e-mail em <a href="{{ route('admin.profissionais.edit', $user) }}">Profissionais</a>.
                            @else
                                Para alterar o e-mail, procure o administrador.
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salvar dados
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <form method="POST" action="{{ route('perfil.senha') }}" class="card h-100" novalidate>
                @csrf
                @method('PUT')
                <div class="card-header bg-white fw-semibold">Alterar senha</div>
                <div class="card-body">
                    @if ($errors->senha->any())
                        <div class="alert alert-danger small py-2">Não foi possível alterar a senha. Verifique os campos.</div>
                    @endif
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Senha atual</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required
                               @class(['form-control', 'is-invalid' => $errors->senha->has('current_password')])>
                        @if ($errors->senha->has('current_password'))
                            <div class="invalid-feedback">{{ $errors->senha->first('current_password') }}</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Nova senha</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" required
                               @class(['form-control', 'is-invalid' => $errors->senha->has('password')])>
                        @if ($errors->senha->has('password'))
                            <div class="invalid-feedback">{{ $errors->senha->first('password') }}</div>
                        @endif
                        <div class="form-text">Mínimo de 8 caracteres.</div>
                    </div>
                    <div>
                        <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required
                               class="form-control">
                    </div>
                </div>
                <div class="card-footer bg-white text-end">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-key me-1"></i>Alterar senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
