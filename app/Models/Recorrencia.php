<?php

namespace App\Models;

use App\Enums\FrequenciaRecorrencia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Série de agendamentos recorrentes. Cada ocorrência é um Agendamento com recorrencia_id.
 */
#[Table('recorrencias')]
#[Fillable(['user_id', 'sala_id', 'frequencia', 'data_inicio', 'data_fim', 'ocorrencias', 'criado_por'])]
class Recorrencia extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequencia' => FrequenciaRecorrencia::class,
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'ocorrencias' => 'integer',
        ];
    }

    /**
     * @return HasMany<Agendamento, $this>
     */
    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }
}
