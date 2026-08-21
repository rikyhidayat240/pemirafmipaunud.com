<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kegiatan>
 */
class KegiatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->sentence(3),
            'tahun' => fake()->year(),
            'waktu_mulai' => fake()->dateTimeBetween('now', '+1 hour'),
            'waktu_selesai' => fake()->dateTimeBetween('+1 hour', '+12 hours'),
            'ruang_lingkup' => fake()->randomElement(['fakultas', 'program studi']),
            'id_program_studi' => fake()->optional()->numberBetween(1, 6),
        ];
    }
}
