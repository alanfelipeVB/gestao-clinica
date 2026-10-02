<?php

namespace App\Enums;

enum StatusAgendamento: string
{
    case Agendado = 'agendado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Agendado => 'Agendado',
            self::Cancelado => 'Cancelado',
        };
    }

    /**
     * Classe de badge do Bootstrap.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Agendado => 'text-bg-success',
            self::Cancelado => 'text-bg-secondary',
        };
    }
}
