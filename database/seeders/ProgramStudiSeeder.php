<?php

namespace Database\Seeders;

use App\Models\ProgramStudi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramStudiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProgramStudi::factory()->createMany([
            ['kode' => '51', 'nama' => 'Kimia'],
            ['kode' => '52', 'nama' => 'Fisika'],
            ['kode' => '53', 'nama' => 'Biologi'],
            ['kode' => '54', 'nama' => 'Matematika'],
            ['kode' => '55', 'nama' => 'Farmasi'],
            ['kode' => '56', 'nama' => 'Informatika'],
        ]);
    }
}
