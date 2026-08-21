<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kandidat>
 */
class KandidatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_kegiatan' => fake()->numberBetween(1, 7),
            'no_urut' => fake()->numerify('0#'),
            'visi' => fake()->paragraph(),
            'misi' => fake()->paragraphs(3, true),
        ];
    }
}
