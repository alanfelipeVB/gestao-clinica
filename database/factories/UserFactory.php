<?php

namespace Database\Factories;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('(##) 9####-####'),
            'instagram' => '@'.fake()->userName(),
            'profissao' => fake()->randomElement(['Psicólogo(a)', 'Fisioterapeuta', 'Nutricionista', 'Fonoaudiólogo(a)', 'Dermatologista']),
            'perfil' => PerfilUsuario::Profissional,
            'ativo' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Usuário com perfil de administrador.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => PerfilUsuario::Admin,
            'profissao' => null,
        ]);
    }

    /**
     * Usuário desativado.
     */
    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => [
            'ativo' => false,
        ]);
    }
}
