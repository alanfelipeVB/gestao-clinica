<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Parâmetro do sistema ajustável pelo administrador.
 * Acesse sempre via App\Services\ConfiguracaoService (com cache).
 */
#[Table('configuracoes')]
#[Fillable(['chave', 'valor'])]
class Configuracao extends Model
{
}
