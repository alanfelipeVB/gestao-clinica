<?php

namespace App\Policies;

use App\Models\Agendamento;
use App\Models\User;

class AgendamentoPolicy
{
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

    private function ehDono(User $user, Agendamento $agendamento): bool
    {
        return $agendamento->user_id === $user->id;
    }
}
