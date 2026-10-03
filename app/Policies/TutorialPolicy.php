<?php

namespace App\Policies;

use App\Models\Tutorial;
use App\Models\User;

class TutorialPolicy
{
    /**
     * Assistir: qualquer usuário, se publicado; o administrador vê também os rascunhos.
     */
    public function view(User $user, Tutorial $tutorial): bool
    {
        return $tutorial->publicado || $user->isAdmin();
    }

    /**
     * Cadastrar, editar, publicar e excluir: somente o administrador.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
