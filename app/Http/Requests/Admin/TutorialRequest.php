<?php

namespace App\Http\Requests\Admin;

use App\Models\Tutorial;
use Illuminate\Foundation\Http\FormRequest;

class TutorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $editando = (bool) $this->route('tutorial');

        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:999'],
            'publicado' => ['nullable', 'boolean'],
            // Vídeo obrigatório no cadastro; na edição, só se for trocar.
            'video' => [
                $editando ? 'nullable' : 'required',
                'file',
                'mimetypes:video/mp4,video/webm',
                'extensions:mp4,webm',
                'max:'.Tutorial::TAMANHO_MAXIMO_KB,
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'video' => 'vídeo',
            'ordem' => 'ordem',
        ];
    }

    public function messages(): array
    {
        return [
            'video.mimetypes' => 'O vídeo deve estar no formato MP4 ou WebM.',
            'video.extensions' => 'O vídeo deve ter extensão .mp4 ou .webm.',
            'video.max' => 'O vídeo pode ter no máximo 500 MB.',
            'video.uploaded' => 'Não foi possível enviar o vídeo. Verifique se ele não ultrapassa o limite de upload do servidor.',
        ];
    }

    /**
     * Dados do formulário, sem o arquivo.
     *
     * @return array<string, mixed>
     */
    public function dados(): array
    {
        return [
            'titulo' => $this->validated('titulo'),
            'descricao' => $this->validated('descricao'),
            'ordem' => (int) ($this->validated('ordem') ?? 0),
            'publicado' => $this->boolean('publicado'),
        ];
    }
}
