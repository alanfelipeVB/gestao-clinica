<?php

namespace App\Models;

use App\Enums\PerfilUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nome', 'email', 'telefone', 'instagram', 'profissao', 'perfil', 'ativo', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'perfil' => PerfilUsuario::class,
            'ativo' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->perfil === PerfilUsuario::Admin;
    }

    public function isProfissional(): bool
    {
        return $this->perfil === PerfilUsuario::Profissional;
    }

    #[Scope]
    protected function ativos(Builder $query): void
    {
        $query->where('ativo', true);
    }

    #[Scope]
    protected function profissionais(Builder $query): void
    {
        $query->where('perfil', PerfilUsuario::Profissional);
    }
}
