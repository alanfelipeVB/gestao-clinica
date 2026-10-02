<?php

namespace App\Models;

use App\Enums\StatusAgendamento;
use Database\Factories\AgendamentoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'sala_id', 'inicio', 'fim', 'descricao', 'status',
    'criado_por', 'cancelado_por', 'cancelado_em', 'motivo_cancelamento',
])]
class Agendamento extends Model
{
    /** @use HasFactory<AgendamentoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fim' => 'datetime',
            'cancelado_em' => 'datetime',
            'status' => StatusAgendamento::class,
        ];
    }

    /**
     * Profissional responsável.
     *
     * @return BelongsTo<User, $this>
     */
    public function profissional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Sala, $this>
     */
    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    public function estaAgendado(): bool
    {
        return $this->status === StatusAgendamento::Agendado;
    }

    public function jaIniciou(): bool
    {
        return $this->inicio->lessThanOrEqualTo(now());
    }

    /**
     * Texto do período, ex.: "14:00 – 15:30".
     */
    public function horario(): string
    {
        return $this->inicio->format('H:i').' – '.$this->fim->format('H:i');
    }

    #[Scope]
    protected function agendados(Builder $query): void
    {
        $query->where('status', StatusAgendamento::Agendado);
    }

    /**
     * Agendamentos que ainda não começaram.
     */
    #[Scope]
    protected function futuros(Builder $query): void
    {
        $query->where('inicio', '>', now());
    }
}
