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
            'bio' => ['nullable', 'string', 'max:500'],
            'publicar_whatsapp' => ['nullable', 'boolean'],
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
     * Dados do perfil, sem o arquivo da foto.
     *
     * @return array<string, mixed>
     */
    public function dados(): array
    {
        return [
            ...$this->safe()->except(['foto', 'remover_foto']),
            'publicar_whatsapp' => $this->boolean('publicar_whatsapp'),
        ];
    }
}
