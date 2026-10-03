<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Fotos dos profissionais (disco privado "local", servidas pela rota profissionais.foto).
 */
class FotoPerfilService
{
    public const PASTA = 'fotos';

    public function trocar(User $user, UploadedFile $arquivo): void
    {
        $anterior = $user->foto;

        $user->update(['foto' => $arquivo->store(self::PASTA, 'local')]);

        if ($anterior) {
            Storage::disk('local')->delete($anterior);
        }
    }

    public function remover(User $user): void
    {
        if ($user->foto) {
            Storage::disk('local')->delete($user->foto);
        }

        $user->update(['foto' => null]);
    }

    /**
     * Aplica o upload ou a remoção vindos de um formulário.
     */
    public function aplicar(User $user, ?UploadedFile $arquivo, bool $remover): void
    {
        if ($arquivo) {
            $this->trocar($user, $arquivo);
        } elseif ($remover) {
            $this->remover($user);
        }
    }
}
