<?php

namespace App\Models;

use Database\Factories\TutorialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Vídeo de tutorial enviado pelo administrador. O arquivo fica no disco privado "local".
 */
#[Table('tutoriais')]
#[Fillable(['titulo', 'descricao', 'arquivo', 'mime', 'tamanho', 'ordem', 'publicado', 'criado_por'])]
class Tutorial extends Model
{
    /** @use HasFactory<TutorialFactory> */
    use HasFactory;

    /** Pasta dos vídeos no disco "local". */
    public const PASTA = 'tutoriais';

    /** Tamanho máximo do vídeo, em kilobytes (500 MB). */
    public const TAMANHO_MAXIMO_KB = 512000;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publicado' => 'boolean',
            'ordem' => 'integer',
            'tamanho' => 'integer',
        ];
    }

    /**
     * Usuários que marcaram o tutorial como assistido.
     *
     * @return BelongsToMany<User, $this>
     */
    public function assistidoPor(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tutorial_visualizacoes')->withPivot('assistido_em');
    }

    public function tamanhoFormatado(): string
    {
        $mb = $this->tamanho / 1024 / 1024;

        return $mb >= 1 ? number_format($mb, 1, ',', '.').' MB' : number_format($this->tamanho / 1024, 0, ',', '.').' KB';
    }

    #[Scope]
    protected function publicados(Builder $query): void
    {
        $query->where('publicado', true);
    }

    #[Scope]
    protected function ordenados(Builder $query): void
    {
        $query->orderBy('ordem')->orderBy('titulo');
    }
}
