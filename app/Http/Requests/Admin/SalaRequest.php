<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação do cadastro (store) e da edição (update) de salas.
 */
class SalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome')),
            'cor' => mb_strtolower((string) $this->input('cor')),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100', Rule::unique('salas', 'nome')->ignore($this->route('sala'))],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'capacidade' => ['nullable', 'integer', 'min:1', 'max:999'],
            'cor' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe uma sala com este nome.',
            'cor.regex' => 'Escolha uma cor válida.',
        ];
    }
}
