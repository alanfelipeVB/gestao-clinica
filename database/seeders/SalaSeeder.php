<?php

namespace Database\Seeders;

use App\Models\Sala;
use Illuminate\Database\Seeder;

/**
 * Salas de exemplo para o ambiente de desenvolvimento.
 * Idempotente: salas já existentes (pelo nome) não são alteradas.
 */
class SalaSeeder extends Seeder
{
    public function run(): void
    {
        $salas = [
            ['nome' => 'Sala 01', 'descricao' => 'Sala de atendimento individual.', 'capacidade' => 2, 'cor' => '#0f766e'],
            ['nome' => 'Sala 02', 'descricao' => 'Sala de atendimento individual.', 'capacidade' => 2, 'cor' => '#2563eb'],
            ['nome' => 'Sala de procedimentos', 'descricao' => 'Equipada com maca e materiais para procedimentos.', 'capacidade' => 3, 'cor' => '#dc2626'],
            ['nome' => 'Sala de avaliação', 'descricao' => 'Avaliações iniciais e retornos.', 'capacidade' => 2, 'cor' => '#d97706'],
            ['nome' => 'Sala de atendimento', 'descricao' => 'Atendimentos em grupo ou familiares.', 'capacidade' => 6, 'cor' => '#7c3aed'],
        ];

        foreach ($salas as $sala) {
            Sala::firstOrCreate(['nome' => $sala['nome']], $sala);
        }
    }
}
