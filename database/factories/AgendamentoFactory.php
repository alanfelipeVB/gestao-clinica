<?php

namespace Database\Factories;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Agendamento>
 */
class AgendamentoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = now()->addDay()->setTime(14, 0);

        return [
            'user_id' => User::factory(),
            'sala_id' => Sala::factory(),
            'inicio' => $inicio,
            'fim' => $inicio->copy()->addHour(),
            'descricao' => fake()->sentence(),
            'status' => StatusAgendamento::Agendado,
            'criado_por' => fn (array $attributes) => $attributes['user_id'],
        ];
    }

    /**
     * Define o período, ex.: ->periodo('2026-10-10 14:00', '2026-10-10 15:00').
     */
    public function periodo(string $inicio, string $fim): static
    {
        return $this->state(fn (array $attributes) => [
            'inicio' => Carbon::parse($inicio),
            'fim' => Carbon::parse($fim),
        ]);
    }

    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusAgendamento::Cancelado,
            'cancelado_por' => fn (array $attributes) => $attributes['user_id'],
            'cancelado_em' => now(),
        ]);
    }
}
