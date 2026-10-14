<?php

namespace Tests\Feature;

use App\Models\Kandidat;
use App\Models\Kegiatan;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityVoteTest extends TestCase
{
    use RefreshDatabase;

    private function createVoter(array $attributes = []): User
    {
        return User::forceCreate(array_merge([
            'nim'                 => '2108561001',
            'id_program_studi'    => 1,
            'nama'                => 'Mahasiswa Uji',
            'angkatan'            => 2021,
            'email'               => 'test@student.unud.ac.id',
            'email_verified_at'   => now(),
            'password'            => bcrypt('password'),
            'status'              => 'aktif',
            'is_admin'            => false,
        ], $attributes));
    }

    private function createEventsAndCandidates(): array
    {
        $kegiatanBem = Kegiatan::create([
            'nama'             => 'Pemilu BEM FMIPA 2026',
            'tahun'            => (int) date('Y'),
            'waktu_mulai'      => now()->subHour(),
            'waktu_selesai'    => now()->addHour(),
            'ruang_lingkup'    => 'fakultas',
            'id_program_studi' => null,
            'foto'             => 'foto.jpg',
        ]);

        $kegiatanHima = Kegiatan::create([
            'nama'             => 'Pemilu HIMA Informatika 2026',
            'tahun'            => (int) date('Y'),
            'waktu_mulai'      => now()->subHour(),
            'waktu_selesai'    => now()->addHour(),
            'ruang_lingkup'    => 'program studi',
            'id_program_studi' => 1,
            'foto'             => 'foto.jpg',
        ]);

        $kandidatBem = Kandidat::create([
            'id_kegiatan'  => $kegiatanBem->id,
            'no_urut'      => '01',
            'visi'         => 'Visi BEM',
            'misi'         => 'Misi BEM',
            'jumlah_suara' => 0,
        ]);

        $kandidatHima = Kandidat::create([
            'id_kegiatan'  => $kegiatanHima->id,
            'no_urut'      => '01',
            'visi'         => 'Visi HIMA',
            'misi'         => 'Misi HIMA',
            'jumlah_suara' => 0,
        ]);

        return [$kegiatanBem, $kegiatanHima, $kandidatBem, $kandidatHima];
    }

    public function test_vote_logs_do_not_contain_candidate_choices(): void
    {
        Log::spy();

        $user = $this->createVoter();
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'id_kandidat_hima' => $kandidatHima->id,
            'ttd'              => $validTtd,
        ]);

        $response->assertOk();

        // Verifikasi log info terpanggil dan tidak memuat kandidat_id
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context = []) {
            $json = json_encode($context);
            return ! str_contains($json, 'kandidat_id') && ! str_contains($json, 'id_kandidat');
        })->atLeast()->once();
    }

    public function test_double_vote_is_rejected_and_increments_candidate_only_once(): void
    {
        $user = $this->createVoter();
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Vote pertama
        $res1 = $this->actingAs($user)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'id_kandidat_hima' => $kandidatHima->id,
            'ttd'              => $validTtd,
        ]);
        $res1->assertOk();

        // Vote kedua (double vote)
        $res2 = $this->actingAs($user)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'id_kandidat_hima' => $kandidatHima->id,
            'ttd'              => $validTtd,
        ]);
        $res2->assertRedirect(route('dashboard'));

        // Pastikan hanya 1 suara tercatat
        expect($kandidatBem->fresh()->jumlah_suara)->toBe(1);
        expect($kandidatHima->fresh()->jumlah_suara)->toBe(1);
    }

    public function test_server_validation_rejects_inactive_voter(): void
    {
        $user = $this->createVoter(['status' => 'nonaktif']);
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'id_kandidat_hima' => $kandidatHima->id,
            'ttd'              => $validTtd,
        ]);

        $response->assertSessionHasErrors('user');
        expect($kandidatBem->fresh()->jumlah_suara)->toBe(0);
    }

    public function test_server_validation_rejects_admin_voter(): void
    {
        $user = $this->createVoter(['is_admin' => true]);
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'id_kandidat_hima' => $kandidatHima->id,
            'ttd'              => $validTtd,
        ]);

        $response->assertSessionHasErrors('user');
        expect($kandidatBem->fresh()->jumlah_suara)->toBe(0);
    }

    public function test_server_validation_rejects_vote_before_schedule(): void
    {
        $user = $this->createVoter();
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        $bem->update(['waktu_mulai' => now()->addHour(), 'waktu_selesai' => now()->addHours(2)]);

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'ttd'              => $validTtd,
        ]);

        $response->assertSessionHasErrors('id_kandidat_bem');
        expect($kandidatBem->fresh()->jumlah_suara)->toBe(0);
    }

    public function test_server_validation_rejects_vote_after_schedule(): void
    {
        $user = $this->createVoter();
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        $bem->update(['waktu_mulai' => now()->subHours(3), 'waktu_selesai' => now()->subHour()]);

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'ttd'              => $validTtd,
        ]);

        $response->assertSessionHasErrors('id_kandidat_bem');
        expect($kandidatBem->fresh()->jumlah_suara)->toBe(0);
    }

    public function test_server_validation_rejects_candidate_from_other_program_studi(): void
    {
        $user = $this->createVoter(['id_program_studi' => 1]);
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        // Ubah HIMA ke prodi 2 (user adalah prodi 1)
        $hima->update(['id_program_studi' => 2]);

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post('/vote', [
            'id_kandidat_hima' => $kandidatHima->id,
            'ttd'              => $validTtd,
        ]);

        $response->assertSessionHasErrors('id_kandidat_hima');
        expect($kandidatHima->fresh()->jumlah_suara)->toBe(0);
    }

    public function test_server_validation_rejects_identical_candidates_including_zero_padded_string(): void
    {
        $user = $this->createVoter();
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Kirim ID sama: "5" vs "05" (atau id kandidat BEM sama untuk kedua field)
        $response = $this->actingAs($user)->post('/vote', [
            'id_kandidat_bem'  => (string) $kandidatBem->id,
            'id_kandidat_hima' => '0' . $kandidatBem->id,
            'ttd'              => $validTtd,
        ]);

        $response->assertSessionHasErrors('id_kandidat_hima');
        expect($kandidatBem->fresh()->jumlah_suara)->toBe(0);
    }

    public function test_interim_results_cannot_be_viewed_by_regular_voter_during_ongoing_event(): void
    {
        $user = $this->createVoter(['is_admin' => false]);
        [$bem, $hima, $kandidatBem, $kandidatHima] = $this->createEventsAndCandidates();

        // Event masih aktif
        $bem->update(['waktu_selesai' => now()->addHour()]);
        $kandidatBem->update(['jumlah_suara' => 42]);

        $response = $this->actingAs($user)->get('/result-bem');

        // Harus dialihkan ke dashboard dengan pemberitahuan hasil belum tersedia
        $response->assertRedirect(route('dashboard'));

        // Pastikan jumlah_suara kandidat tersembunyi secara default dalam serialisasi JSON
        $kandidatJson = $kandidatBem->fresh()->toArray();
        expect(array_key_exists('jumlah_suara', $kandidatJson))->toBeFalse();
    }
}
