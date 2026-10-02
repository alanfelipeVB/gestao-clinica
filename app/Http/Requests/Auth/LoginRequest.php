<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Tentativas de login permitidas por minuto (por e-mail + IP). */
    private const MAX_TENTATIVAS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Autentica o usuário, aplicando limite de tentativas e bloqueando contas inativas.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->garantirQueNaoEstaBloqueado();

        $credenciais = $this->only('email', 'password');

        if (! Auth::attempt([...$credenciais, 'ativo' => true], $this->boolean('remember'))) {
            RateLimiter::hit($this->chaveDeBloqueio());

            // Senha correta mas conta inativa: mensagem específica.
            $mensagem = Auth::validate($credenciais) ? trans('auth.inactive') : trans('auth.failed');

            throw ValidationException::withMessages(['email' => $mensagem]);
        }

        RateLimiter::clear($this->chaveDeBloqueio());
    }

    /**
     * @throws ValidationException
     */
    private function garantirQueNaoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->chaveDeBloqueio(), self::MAX_TENTATIVAS)) {
            return;
        }

        event(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->chaveDeBloqueio());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', ['seconds' => $segundos]),
        ]);
    }

    private function chaveDeBloqueio(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
