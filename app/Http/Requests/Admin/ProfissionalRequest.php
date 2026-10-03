<?php

namespace App\Http\Requests\Admin;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Validação do cadastro (store) e da edição (update) de profissionais.
 */
class ProfissionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'instagram' => ltrim(trim((string) $this->input('instagram')), '@') ?: null,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $profissional = $this->profissionalEmEdicao();

        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($profissional)],
            'telefone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\-\s]+$/'],
            'instagram' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9._]+$/'],
            'profissao' => ['nullable', 'string', 'max:100'],
            'perfil' => ['required', Rule::enum(PerfilUsuario::class)],
            'password' => [$profissional ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            // Página inicial. "Publicar WhatsApp" é decisão do próprio profissional (Meu perfil).
            'bio' => ['nullable', 'string', 'max:500'],
            'exibir_no_site' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remover_foto' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'bio' => 'mini biografia',
            'foto' => 'foto',
        ];
    }

    public function messages(): array
    {
        return [
            'telefone.regex' => 'O telefone deve conter apenas números, espaços, parênteses, + e -.',
            'instagram.regex' => 'O instagram deve conter apenas letras, números, ponto e sublinhado.',
            'foto.max' => 'A foto pode ter no máximo 2 MB.',
        ];
    }

    /**
     * O administrador não pode remover o próprio perfil de administrador.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $profissional = $this->profissionalEmEdicao();

                if ($profissional?->is($this->user()) && $this->input('perfil') !== PerfilUsuario::Admin->value) {
                    $validator->errors()->add('perfil', 'Você não pode remover o seu próprio perfil de administrador.');
                }
            },
        ];
    }

    /**
     * Dados prontos para gravar (senha vazia na edição mantém a atual).
     *
     * @return array<string, mixed>
     */
    public function dados(): array
    {
        // A foto é gravada à parte pelo FotoPerfilService.
        $dados = $this->safe()->except(['password', 'foto', 'remover_foto']);
        $dados['exibir_no_site'] = $this->boolean('exibir_no_site');

        if ($this->filled('password')) {
            $dados['password'] = $this->input('password');
        }

        return $dados;
    }

    private function profissionalEmEdicao(): ?User
    {
        return $this->route('profissional');
    }
}
