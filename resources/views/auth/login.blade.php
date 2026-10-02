<x-layouts.guest title="Entrar">
    <h2 class="h5 mb-1">Acesse sua conta</h2>
    <p class="text-secondary small mb-4">Informe seu e-mail e senha para continuar.</p>

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <div class="input-group has-validation">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       @class(['form-control', 'is-invalid' => $errors->has('email')])
                       autocomplete="username" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Senha</label>
            <div class="input-group has-validation">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password"
                       @class(['form-control', 'is-invalid' => $errors->has('password')])
                       autocomplete="current-password" required>
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Manter conectado</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
        </button>
    </form>

    <p class="text-secondary small text-center mt-4 mb-0">
        Esqueceu a senha? Procure o administrador da clínica.
    </p>
</x-layouts.guest>
