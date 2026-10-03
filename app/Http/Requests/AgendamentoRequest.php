<?php

namespace App\Http\Requests;

use App\Enums\FrequenciaRecorrencia;
use App\Models\Agendamento;
use App\Services\RecorrenciaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Validação de formato do agendamento (store/update).
 * As regras de negócio ficam no AgendamentoService.
 */
class AgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorização feita no controller pela AgendamentoPolicy.
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $regras = [
            'sala_id' => ['required', 'integer', 'exists:salas,id'],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'descricao' => ['required', 'string', 'max:1000'],
        ];

        // Somente o administrador escolhe o profissional responsável.
        if ($this->user()->isAdmin()) {
            $regras['user_id'] = ['required', 'integer', 'exists:users,id'];
        }

        // Recorrência: apenas na criação.
        if (! $this->route('agendamento')) {
            $regras += [
                'repetir' => ['nullable', 'boolean'],
                'frequencia' => ['exclude_unless:repetir,1', 'required', Rule::enum(FrequenciaRecorrencia::class)],
                'fim_tipo' => ['exclude_unless:repetir,1', 'required', Rule::in(['data', 'ocorrencias'])],
                'data_fim' => ['exclude_unless:repetir,1', 'exclude_unless:fim_tipo,data', 'required', 'date_format:Y-m-d', 'after:data'],
                'ocorrencias' => ['exclude_unless:repetir,1', 'exclude_unless:fim_tipo,ocorrencias', 'required', 'integer', 'min:2', 'max:'.RecorrenciaService::MAX_OCORRENCIAS],
                'confirmado' => ['nullable', 'boolean'],
            ];
        }

        return $regras;
    }

    public function messages(): array
    {
        return [
            'hora_fim.after' => 'O horário de término deve ser posterior ao horário de início.',
            'data_fim.after' => 'A data final da repetição deve ser posterior à data do primeiro agendamento.',
        ];
    }

    public function attributes(): array
    {
        return [
            'frequencia' => 'frequência',
            'fim_tipo' => 'término da repetição',
            'data_fim' => 'data final',
            'ocorrencias' => 'número de ocorrências',
        ];
    }

    public function repeteSerie(): bool
    {
        return $this->boolean('repetir') && ! $this->route('agendamento');
    }

    /**
     * Parâmetros da série no formato esperado pelo RecorrenciaService.
     *
     * @return array{frequencia: FrequenciaRecorrencia, data_fim: ?Carbon, ocorrencias: ?int}
     */
    public function dadosRecorrencia(): array
    {
        $porData = $this->validated('fim_tipo') === 'data';

        return [
            'frequencia' => FrequenciaRecorrencia::from($this->validated('frequencia')),
            'data_fim' => $porData ? Carbon::createFromFormat('Y-m-d', $this->validated('data_fim'))->startOfDay() : null,
            'ocorrencias' => $porData ? null : (int) $this->validated('ocorrencias'),
        ];
    }

    /**
     * Dados no formato esperado pelo AgendamentoService.
     *
     * @return array{user_id: int, sala_id: int, inicio: Carbon, fim: Carbon, descricao: string}
     */
    public function dados(): array
    {
        /** @var Agendamento|null $agendamento */
        $agendamento = $this->route('agendamento');

        $userId = $this->user()->isAdmin()
            ? (int) $this->validated('user_id')
            : ($agendamento->user_id ?? $this->user()->id);

        return [
            'user_id' => $userId,
            'sala_id' => (int) $this->validated('sala_id'),
            'inicio' => Carbon::createFromFormat('Y-m-d H:i', $this->validated('data').' '.$this->validated('hora_inicio'))->startOfMinute(),
            'fim' => Carbon::createFromFormat('Y-m-d H:i', $this->validated('data').' '.$this->validated('hora_fim'))->startOfMinute(),
            'descricao' => trim($this->validated('descricao')),
        ];
    }
}
