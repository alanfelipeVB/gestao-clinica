<?php

namespace App\Http\Requests\Admin;

use App\Services\ConfiguracaoService;
use Illuminate\Foundation\Http\FormRequest;

class ConfiguracaoRequest extends FormRequest
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
        return [
            ConfiguracaoService::ANTECEDENCIA_MAXIMA_DIAS => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function attributes(): array
    {
        return [
            ConfiguracaoService::ANTECEDENCIA_MAXIMA_DIAS => 'antecedência máxima',
        ];
    }
}
