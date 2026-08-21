<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    protected static ?string $nimPattern = '2#085#10##';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nim' => fake()->unique()->numerify(static::$nimPattern),
            'id_program_studi' => fake()->numberBetween(1, 6),
            'nama' => fake()->name(),
            'angkatan' => fake()->numberBetween(2020, 2025),
            'email' => null,
            'email_verified_at' => null,
            // 'password' => static::$password ??= Hash::make('musmafmipa'),
            'password' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
