<?php

namespace App\Enums;

enum PerfilUsuario: string
{
    case Admin = 'admin';
    case Profissional = 'profissional';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Profissional => 'Profissional',
        };
    }
}
