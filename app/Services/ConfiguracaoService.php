<?php

namespace App\Services;

use App\Models\Configuracao;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Leitura e gravação das configurações do sistema, com cache.
 */
class ConfiguracaoService
{
    public const ANTECEDENCIA_MAXIMA_DIAS = 'antecedencia_maxima_dias';

    private const CACHE_KEY = 'configuracoes';

    /**
     * Valores usados quando a chave não existe no banco.
     *
     * @var array<string, string>
     */
    private const PADROES = [
        self::ANTECEDENCIA_MAXIMA_DIAS => '30',
    ];

    public function get(string $chave): ?string
    {
        return $this->todas()[$chave] ?? self::PADROES[$chave] ?? null;
    }

    public function int(string $chave): int
    {
        return (int) $this->get($chave);
    }

    /**
     * Grava vários valores de uma vez e limpa o cache.
     *
     * @param  array<string, scalar|null>  $valores
     */
    public function salvar(array $valores): void
    {
        DB::transaction(function () use ($valores) {
            foreach ($valores as $chave => $valor) {
                Configuracao::updateOrCreate(['chave' => $chave], ['valor' => $valor]);
            }
        });

        Cache::forget(self::CACHE_KEY);
    }

    public function antecedenciaMaximaDias(): int
    {
        return $this->int(self::ANTECEDENCIA_MAXIMA_DIAS);
    }

    /**
     * @return array<string, string|null>
     */
    private function todas(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Configuracao::query()->pluck('valor', 'chave')->all(),
        );
    }
}
