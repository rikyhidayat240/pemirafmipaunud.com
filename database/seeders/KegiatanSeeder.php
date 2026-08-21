<?php

namespace Database\Seeders;

use App\Models\Kegiatan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KegiatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kegiatan::factory()->createMany([
            [
                'nama' => 'Demo Pemilihan Ketua dan Wakil Ketua BEM FMIPA',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-03 06:00:00',
                'waktu_selesai' => '2025-11-12 00:00:00',
                'ruang_lingkup' => 'fakultas',
                'id_program_studi' => null,
                'foto' => 'foto-kegiatan/pemilihan-ketua-wakil-bem.jpg',
            ],
            [
                'nama' => 'Demo Pemilihan Ketua HIMAKI',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-03 06:00:00',
                'waktu_selesai' => '2025-11-12 00:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 1,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himaki.jpg',
            ],
            [
                'nama' => 'Demo Pemilihan Ketua HIMAFI',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-03 06:00:00',
                'waktu_selesai' => '2025-11-12 00:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 2,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himafi.jpg',
            ],
            [
                'nama' => 'Demo Pemilihan Ketua HIMABIO',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-03 06:00:00',
                'waktu_selesai' => '2025-11-12 00:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 3,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himabio.jpg',
            ],
            [
                'nama' => 'Demo Pemilihan Ketua HIMATIKA',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-03 06:00:00',
                'waktu_selesai' => '2025-11-12 00:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 4,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himatika.jpg',
            ],
            [
                'nama' => 'Demo Pemilihan Ketua HIMAFARMA',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-03 06:00:00',
                'waktu_selesai' => '2025-11-12 00:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 5,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himafarma.jpg',
            ],
            [
                'nama' => 'Demo Pemilihan Ketua HIMAIF',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-03 06:00:00',
                'waktu_selesai' => '2025-11-12 00:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 6,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himaif.jpg',
            ],
            [
                'nama' => 'Pemilihan Ketua dan Wakil Ketua BEM FMIPA',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-12 06:00:00',
                'waktu_selesai' => '2025-11-12 18:00:00',
                'ruang_lingkup' => 'fakultas',
                'id_program_studi' => null,
                'foto' => 'foto-kegiatan/pemilihan-ketua-wakil-bem.jpg',
            ],
            [
                'nama' => 'Pemilihan Ketua HIMAKI',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-12 06:00:00',
                'waktu_selesai' => '2025-11-12 18:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 1,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himaki.jpg',
            ],
            [
                'nama' => 'Pemilihan Ketua HIMAFI',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-12 06:00:00',
                'waktu_selesai' => '2025-11-12 18:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 2,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himafi.jpg',
            ],
            [
                'nama' => 'Pemilihan Ketua HIMABIO',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-12 06:00:00',
                'waktu_selesai' => '2025-11-12 18:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 3,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himabio.jpg',
            ],
            [
                'nama' => 'Pemilihan Ketua HIMATIKA',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-12 06:00:00',
                'waktu_selesai' => '2025-11-12 18:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 4,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himatika.jpg',
            ],
            [
                'nama' => 'Pemilihan Ketua HIMAFARMA',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-12 06:00:00',
                'waktu_selesai' => '2025-11-12 18:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 5,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himafarma.jpg',
            ],
            [
                'nama' => 'Pemilihan Ketua HIMAIF',
                'tahun' => 2025,
                'waktu_mulai' => '2025-11-12 06:00:00',
                'waktu_selesai' => '2025-11-12 18:00:00',
                'ruang_lingkup' => 'program studi',
                'id_program_studi' => 6,
                'foto' => 'foto-kegiatan/pemilihan-ketua-himaif.jpg',
            ],
        ]);
    }
}
