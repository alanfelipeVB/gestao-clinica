<?php

namespace App\Enums;

/**
 * Resultado do atendimento, marcado após o início do agendamento.
 */
enum SituacaoAtendimento: string
{
    case Pendente = 'pendente';
    case Realizado = 'realizado';
    case NaoRealizado = 'nao_realizado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente de confirmação',
            self::Realizado => 'Realizado',
            self::NaoRealizado => 'Não realizado',
        };
    }

    /**
     * Classe de badge do Bootstrap.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Pendente => 'text-bg-warning',
            self::Realizado => 'text-bg-primary',
            self::NaoRealizado => 'text-bg-danger',
        };
    }
}
