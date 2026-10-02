<?php

namespace App\Events;

use App\Models\Agendamento;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ponto de extensão para notificações / e-mail de confirmação.
 */
class AgendamentoCriado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Agendamento $agendamento)
    {
    }
}
