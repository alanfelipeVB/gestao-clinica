<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum FrequenciaRecorrencia: string
{
    case Semanal = 'semanal';
    case Quinzenal = 'quinzenal';
    case Mensal = 'mensal';

    public function label(): string
    {
        return match ($this) {
            self::Semanal => 'Semanal',
            self::Quinzenal => 'Quinzenal',
            self::Mensal => 'Mensal',
        };
    }

    /**
     * Data da n-ésima ocorrência (n = 0 é a primeira).
     * No mensal, retorna null quando o dia não existe no mês (ex.: dia 31 em abril).
     */
    public function ocorrencia(Carbon $primeira, int $n): ?Carbon
    {
        return match ($this) {
            self::Semanal => $primeira->copy()->addWeeks($n),
            self::Quinzenal => $primeira->copy()->addWeeks(2 * $n),
            self::Mensal => $this->mesmoDiaDoMes($primeira, $n),
        };
    }

    private function mesmoDiaDoMes(Carbon $primeira, int $n): ?Carbon
    {
        $mes = $primeira->copy()->startOfMonth()->addMonths($n);

        if ($primeira->day > $mes->daysInMonth) {
            return null;
        }

        return $mes->setDay($primeira->day)->setTime($primeira->hour, $primeira->minute);
    }
}
