<?php

namespace Database\Factories;

use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tutorial>
 */
class TutorialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(4),
            'descricao' => fake()->optional()->paragraph(),
            'arquivo' => Tutorial::PASTA.'/'.fake()->uuid().'.mp4',
            'mime' => 'video/mp4',
            'tamanho' => fake()->numberBetween(1_000_000, 50_000_000),
            'ordem' => 0,
            'publicado' => true,
            'criado_por' => User::factory()->admin(),
        ];
    }

    public function rascunho(): static
    {
        return $this->state(fn (array $attributes) => ['publicado' => false]);
    }
}
