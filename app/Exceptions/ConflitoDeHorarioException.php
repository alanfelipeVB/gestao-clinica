<?php

namespace App\Exceptions;

use App\Models\Agendamento;

/**
 * O período solicitado se sobrepõe a um agendamento existente
 * (na mesma sala ou do mesmo profissional).
 */
class ConflitoDeHorarioException extends RegraAgendamentoException
{
    public function __construct(string $mensagem, public readonly Agendamento $conflitante)
    {
        parent::__construct($mensagem, 'hora_inicio');
    }

    public static function daSala(Agendamento $conflitante): self
    {
        return new self(sprintf(
            '%s já está reservada em %s das %s às %s por %s. Escolha outro horário ou outra sala.',
            $conflitante->sala->nome,
            $conflitante->inicio->format('d/m/Y'),
            $conflitante->inicio->format('H:i'),
            $conflitante->fim->format('H:i'),
            $conflitante->profissional->nome,
        ), $conflitante);
    }

    public static function doProfissional(Agendamento $conflitante): self
    {
        return new self(sprintf(
            '%s já tem um agendamento em %s das %s às %s na %s. Um profissional não pode estar em duas salas ao mesmo tempo.',
            $conflitante->profissional->nome,
            $conflitante->inicio->format('d/m/Y'),
            $conflitante->inicio->format('H:i'),
            $conflitante->fim->format('H:i'),
            $conflitante->sala->nome,
        ), $conflitante);
    }
}
