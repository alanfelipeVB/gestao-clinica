<?php

namespace App\Policies;

use App\Models\Agendamento;
use App\Models\User;

class AgendamentoPolicy
{
    /** Dias após o término em que o profissional ainda pode registrar/corrigir o atendimento. */
    public const PRAZO_CORRECAO_DIAS = 7;

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Detalhes completos: administrador ou o próprio profissional.
     */
    public function view(User $user, Agendamento $agendamento): bool
    {
        return $user->isAdmin() || $this->ehDono($user, $agendamento);
    }

    public function create(User $user): bool
    {
        return $user->ativo;
    }

    /**
     * Edição: apenas agendamentos ativos que ainda não começaram.
     */
    public function update(User $user, Agendamento $agendamento): bool
    {
        return $agendamento->estaAgendado()
            && ! $agendamento->jaIniciou()
            && ($user->isAdmin() || $this->ehDono($user, $agendamento));
    }

    /**
     * Cancelamento: o profissional até o horário de início; o administrador a qualquer momento.
     */
    public function cancel(User $user, Agendamento $agendamento): bool
    {
        if (! $agendamento->estaAgendado()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->ehDono($user, $agendamento) && ! $agendamento->jaIniciou();
    }

    /**
     * Registro do atendimento (realizado / não realizado): a partir do início do agendamento.
     * O profissional pode registrar ou corrigir até PRAZO_CORRECAO_DIAS após o término;
     * depois disso, somente o administrador.
     */
    public function registrarAtendimento(User $user, Agendamento $agendamento): bool
    {
        if (! $agendamento->estaAgendado() || ! $agendamento->jaIniciou()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->ehDono($user, $agendamento)
            && $agendamento->fim->copy()->addDays(self::PRAZO_CORRECAO_DIAS)->isFuture();
    }

    private function ehDono(User $user, Agendamento $agendamento): bool
    {
        return $agendamento->user_id === $user->id;
    }
}
