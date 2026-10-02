<?php

namespace Database\Factories;

use App\Models\Sala;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sala>
 */
class SalaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => 'Sala '.fake()->unique()->numerify('###'),
            'descricao' => fake()->optional()->sentence(),
            'capacidade' => fake()->optional()->numberBetween(1, 10),
            'cor' => fake()->hexColor(),
            'ativa' => true,
        ];
    }

    public function inativa(): static
    {
        return $this->state(fn (array $attributes) => [
            'ativa' => false,
        ]);
    }
}
