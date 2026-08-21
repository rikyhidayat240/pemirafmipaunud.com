<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgramStudi>
 */
class ProgramStudiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->randomElement(['51', '52', '53', '54', '55', '56']),
            'nama' => fake()->randomElement(['Informatika', 'Matematika', 'Fisika', 'Kimia', 'Biologi', 'Farmasi']),
        ];
    }
}
