<?php

namespace App\Models;

use Database\Factories\SalaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'descricao', 'capacidade', 'cor', 'ativa'])]
class Sala extends Model
{
    /** @use HasFactory<SalaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacidade' => 'integer',
            'ativa' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Agendamento, $this>
     */
    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    #[Scope]
    protected function ativas(Builder $query): void
    {
        $query->where('ativa', true);
    }

    #[Scope]
    protected function busca(Builder $query, ?string $termo): void
    {
        $query->when($termo, fn (Builder $q) => $q->where('nome', 'like', "%{$termo}%"));
    }
}
