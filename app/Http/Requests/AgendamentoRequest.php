<?php

namespace App\Http\Requests;

use App\Models\Agendamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

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

        return $regras;
    }

    public function messages(): array
    {
        return [
            'hora_fim.after' => 'O horário de término deve ser posterior ao horário de início.',
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
