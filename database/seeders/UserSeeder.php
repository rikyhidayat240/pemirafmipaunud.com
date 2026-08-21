<?php

namespace Database\Seeders;

use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin
        User::factory()->create([
            'nim' => '0000000000',
            'id_program_studi' => 1,
            'nama' => 'Admin DPM FMIPA',
            'angkatan' => 2000,
            'email' => 'dpmfmipaunud2025@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('AdminWebDPM'),
            'is_admin' => true,
        ]);

        // Create user for kotak kosong purpose
        User::factory()->createMany([
            [
                'nim' => '0000000001',
                'id_program_studi' => 1,
                'nama' => 'Kotak Kosong Caka BEM',
                'angkatan' => 2000,
                'is_admin' => true,
            ],
            [
                'nim' => '0000000002',
                'id_program_studi' => 1,
                'nama' => 'Kotak Kosong Cawaka BEM',
                'angkatan' => 2000,
                'is_admin' => true,
            ],
            [
                'nim' => '0000000051',
                'id_program_studi' => 1,
                'nama' => 'Kotak Kosong Kimia',
                'angkatan' => 2000,
                'is_admin' => true,
            ],
            [
                'nim' => '0000000052',
                'id_program_studi' => 2,
                'nama' => 'Kotak Kosong Fisika',
                'angkatan' => 2000,
                'is_admin' => true,
            ],
            [
                'nim' => '0000000053',
                'id_program_studi' => 3,
                'nama' => 'Kotak Kosong Biologi',
                'angkatan' => 2000,
                'is_admin' => true,
            ],
            [
                'nim' => '0000000054',
                'id_program_studi' => 4,
                'nama' => 'Kotak Kosong Matematika',
                'angkatan' => 2000,
                'is_admin' => true,
            ],
            [
                'nim' => '0000000055',
                'id_program_studi' => 5,
                'nama' => 'Kotak Kosong Farmasi',
                'angkatan' => 2000,
                'is_admin' => true,
            ],
            [
                'nim' => '0000000056',
                'id_program_studi' => 6,
                'nama' => 'Kotak Kosong Informatika',
                'angkatan' => 2000,
                'is_admin' => true,
            ],
        ]);

        // Create mahasiswa from Excel sheet
        User::factory()->createMany(User::getMahasiswaFromSheet(now()->year));
    }
}
