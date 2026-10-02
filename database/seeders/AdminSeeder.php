<?php

namespace Database\Seeders;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Cria o administrador inicial a partir de config/clinica.php (.env).
     * Idempotente: se o e-mail já existir, nada é alterado.
     */
    public function run(): void
    {
        $dados = config('clinica.admin');

        if (blank($dados['email']) || blank($dados['senha'])) {
            throw new RuntimeException('Defina ADMIN_EMAIL e ADMIN_SENHA no .env antes de rodar o seeder.');
        }

        $admin = User::firstOrCreate(
            ['email' => $dados['email']],
            [
                'nome' => $dados['nome'],
                'perfil' => PerfilUsuario::Admin,
                'ativo' => true,
                'password' => $dados['senha'],
            ],
        );

        $this->command?->info(
            $admin->wasRecentlyCreated
                ? "Administrador {$admin->email} criado."
                : "Administrador {$admin->email} já existe; nenhuma alteração feita."
        );
    }
}
