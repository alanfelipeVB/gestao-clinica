<?php

namespace App\Support;

/**
 * Links de conversa do WhatsApp (https://wa.me/<número>?text=<mensagem>).
 */
class Whatsapp
{
    /**
     * Retorna o link ou null quando o número é inválido.
     * Números brasileiros sem DDI (10 ou 11 dígitos) recebem o 55.
     */
    public static function link(?string $numero, ?string $mensagem = null): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $numero);

        if (strlen($digitos) === 10 || strlen($digitos) === 11) {
            $digitos = '55'.$digitos;
        }

        if (strlen($digitos) < 12 || strlen($digitos) > 15) {
            return null;
        }

        return 'https://wa.me/'.$digitos.($mensagem ? '?text='.rawurlencode($mensagem) : '');
    }
}
