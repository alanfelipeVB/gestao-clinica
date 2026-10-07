<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PaginaInicialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'site_instagram' => ltrim(trim((string) $this->input('site_instagram')), '@') ?: null,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_nome' => ['nullable', 'string', 'max:100'],
            'site_titulo' => ['nullable', 'string', 'max:150'],
            'site_subtitulo' => ['nullable', 'string', 'max:300'],
            'site_sobre' => ['nullable', 'string', 'max:3000'],
            'site_endereco' => ['nullable', 'string', 'max:255'],
            'site_telefone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\-\s]+$/'],
            'site_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\-\s]+$/'],
            'site_email' => ['nullable', 'email', 'max:255'],
            'site_instagram' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9._]+$/'],
            'site_horario' => ['nullable', 'string', 'max:255'],
            // SVG não é aceito: pode conter scripts.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remover_logo' => ['nullable', 'boolean'],
            'favicon' => [
                'nullable', 'file', 'max:512',
                'mimetypes:image/png,image/webp,image/x-icon,image/vnd.microsoft.icon',
                'extensions:png,webp,ico',
            ],
            'remover_favicon' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'site_nome' => 'nome da clínica',
            'site_titulo' => 'título',
            'site_subtitulo' => 'subtítulo',
            'site_sobre' => 'texto "Sobre"',
            'site_endereco' => 'endereço',
            'site_telefone' => 'telefone',
            'site_whatsapp' => 'WhatsApp',
            'site_email' => 'e-mail',
            'site_instagram' => 'instagram',
            'site_horario' => 'horário de atendimento',
            'logo' => 'logo',
            'favicon' => 'favicon',
        ];
    }

    public function messages(): array
    {
        return [
            'site_telefone.regex' => 'O telefone deve conter apenas números, espaços, parênteses, + e -.',
            'site_whatsapp.regex' => 'O WhatsApp deve conter apenas números, espaços, parênteses, + e -.',
            'site_instagram.regex' => 'O instagram deve conter apenas letras, números, ponto e sublinhado.',
            'logo.max' => 'A logo pode ter no máximo 2 MB.',
            'favicon.max' => 'O favicon pode ter no máximo 512 KB.',
            'favicon.mimetypes' => 'O favicon deve ser PNG, WebP ou ICO.',
            'favicon.extensions' => 'O favicon deve ser PNG, WebP ou ICO.',
        ];
    }
}
