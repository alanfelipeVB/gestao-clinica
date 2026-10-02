<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dados que o próprio usuário pode alterar. E-mail e perfil são gerenciados pelo administrador.
 */
class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'instagram' => ltrim(trim((string) $this->input('instagram')), '@') ?: null,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\-\s]+$/'],
            'instagram' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9._]+$/'],
            'profissao' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'telefone.regex' => 'O telefone deve conter apenas números, espaços, parênteses, + e -.',
            'instagram.regex' => 'O instagram deve conter apenas letras, números, ponto e sublinhado.',
        ];
    }
}
