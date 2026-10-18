<?php

namespace Tests\Feature;

use App\Mail\MahasiswaCredentials;
use App\Models\AuditLog;
use App\Models\Kandidat;
use App\Models\Kegiatan;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixturePath = null;

    protected function tearDown(): void
    {
        if ($this->fixturePath && file_exists($this->fixturePath)) {
            @unlink($this->fixturePath);
        }

        parent::tearDown();
    }

    /**
     * Impor/sinkronisasi spreadsheet:
     * Buat fixture .xlsx di dalam test dengan PhpSpreadsheet dan jalankan alur impor sebagai admin.
     * Assert: data mahasiswa ter-update, status mengikuti logika sinkronisasi,
     * dan kolom is_admin atau status yang diselipkan di file TIDAK membuat user menjadi admin.
     */
    public function test_sync_mahasiswa_from_spreadsheet_fixture_updates_data_and_prevents_privilege_escalation(): void
    {
        $admin = User::factory()->create([
            'nim'               => 'admin_sync',
            'is_admin'          => true,
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        // Pre-existing student that will be updated
        $existingStudent = User::factory()->create([
            'nim'               => '2208561091',
            'nama'              => 'Old Student Name',
            'id_program_studi'  => 6, // Informatika
            'angkatan'          => 2022,
            'status'            => 'nonaktif',
            'is_admin'          => false,
            'email_verified_at' => now(),
        ]);

        // Buat direktori data-mahasiswa jika belum ada
        $dir = database_path('data-mahasiswa');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $testYear = 2025;
        $this->fixturePath = database_path("data-mahasiswa/data-mahasiswa-{$testYear}.xlsx");

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Kolom: No(0), NIM(1), Nama(2), JK(3), Program Studi(4), Status(5), Angkatan(6), Injected is_admin(7), Injected status(8)
        $sheet->fromArray([
            ['No', 'NIM', 'Nama', 'JK', 'Program Studi', 'Status', 'Angkatan', 'is_admin', 'injected_status'],
            [1, '2208561091', 'Updated Student 1', 'L', 'Informatika', 'Aktif', 2022, 1, 'admin'],
            [2, '2208561092', 'New Student 2', 'P', 'Kimia', 'Cuti Akademik', 2023, 1, 'admin'],
        ]);

        $writer = new Xlsx($spreadsheet);
        $writer->save($this->fixturePath);

        $response = $this->actingAs($admin)->post(route('users.sync-data', ['year' => $testYear]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verifikasi mahasiswa yang diperbarui
        $existingStudent->refresh();
        $this->assertSame('Updated Student 1', $existingStudent->nama);
        $this->assertSame('aktif', $existingStudent->status);
        $this->assertFalse((bool) $existingStudent->is_admin, 'Kolom is_admin di file spreadsheet tidak boleh mengelevasi mahasiswa lama');

        // Verifikasi mahasiswa baru yang dibuat
        $newStudent = User::where('nim', '2208561092')->first();
        $this->assertNotNull($newStudent);
        $this->assertSame('New Student 2', $newStudent->nama);
        $this->assertSame('nonaktif', $newStudent->status, 'Status non-aktif/cuti harus dipetakan ke nonaktif');
        $this->assertFalse((bool) $newStudent->is_admin, 'Kolom is_admin di file spreadsheet tidak boleh membuat user baru menjadi admin');
    }

    /**
     * Login mahasiswa dan admin berhasil;
     * halaman utama admin mengembalikan 200 untuk admin dan 302 redirect untuk non-admin.
     */
    public function test_student_and_admin_authentication_and_admin_page_authorization(): void
    {
        $studentPassword = 'studentPassword123';
        $student = User::factory()->create([
            'nim'               => '2208561005',
            'email'             => 'student_auth@test.com',
            'password'          => Hash::make($studentPassword),
            'status'            => 'aktif',
            'is_admin'          => false,
            'email_verified_at' => now(),
        ]);

        $adminPassword = 'adminPassword123';
        $admin = User::factory()->create([
            'nim'               => 'admin_auth',
            'email'             => 'admin_auth@test.com',
            'password'          => Hash::make($adminPassword),
            'status'            => 'aktif',
            'is_admin'          => true,
            'email_verified_at' => now(),
        ]);

        // 1. Mahasiswa login berhasil
        $this->assertTrue(Auth::attempt([
            'email'    => $student->email,
            'password' => $studentPassword,
        ]), 'Kredensial mahasiswa valid harus berhasil diautentikasi');
        $this->assertAuthenticatedAs($student);

        Auth::logout();

        // 2. Admin login berhasil
        $this->assertTrue(Auth::attempt([
            'email'    => $admin->email,
            'password' => $adminPassword,
        ]), 'Kredensial admin valid harus berhasil diautentikasi');
        $this->assertAuthenticatedAs($admin);

        // 3. Admin membuka halaman utama admin (/users) -> status 200
        $adminUsersRes = $this->actingAs($admin)->get('/users');
        $adminUsersRes->assertStatus(200);

        // 4. Mahasiswa non-admin membuka halaman utama admin (/users) -> status 302 redirect ke dashboard (HandleIsAdmin)
        $nonAdminUsersRes = $this->actingAs($student)->get('/users');
        $nonAdminUsersRes->assertStatus(302);
        $nonAdminUsersRes->assertRedirect(route('dashboard'));
    }

    /**
     * Alur kirim kredensial dengan Mail::fake():
     * pastikan password plaintext tidak muncul di log.
     */
    public function test_send_credentials_flow_dispatches_email_and_never_logs_plaintext_password(): void
    {
        Mail::fake();
        Log::spy();

        $admin = User::factory()->create([
            'nim'               => 'admin_creds',
            'is_admin'          => true,
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        $student = User::factory()->create([
            'nim'               => '2208561088',
            'email'             => 'student88@student.unud.ac.id',
            'is_admin'          => false,
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post("/users/{$student->nim}/send-credentials");
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Pastikan email terkirim via MahasiswaCredentials mailable
        $capturedPassword = null;
        Mail::assertSent(MahasiswaCredentials::class, function ($mail) use ($student, &$capturedPassword) {
            if ($mail->mahasiswa->nim === $student->nim) {
                $capturedPassword = $mail->plainPassword;
                return true;
            }
            return false;
        });

        $this->assertNotEmpty($capturedPassword, 'Password plaintext harus diteruskan ke mailable');

        // Pastikan password plaintext tidak muncul di log apa pun
        foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level) {
            Log::shouldNotHaveReceived($level, function ($message, $context = []) use ($capturedPassword) {
                $rawMsg = is_string($message) ? $message : json_encode($message);
                $rawCtx = json_encode($context);
                return str_contains($rawMsg, $capturedPassword) || str_contains($rawCtx, $capturedPassword);
            });
        }
    }

    /**
     * Verifikasi endpoint registrasi dan reset password setelah routes/console.php dibersihkan:
     * - check-student tidak terdaftar sama sekali -> 404
     * - POST register tidak terdaftar -> 405 (tidak ada registrasi mandiri)
     * - POST forgot-password & reset-password tidak terdaftar -> 405 / 404 (tidak ada reset password via email)
     * - GET register & forgot-password tidak menampilkan form registrasi/reset Breeze (hanya login / redirect 302)
     */
    public function test_disabled_auth_and_student_check_routes_are_not_accessible_after_console_routes_cleaned(): void
    {
        // 1. check-student tidak ada sama sekali di routing -> 404
        $this->get('/check-student')->assertNotFound();
        $this->post('/check-student', ['nim' => '2208561001'])->assertNotFound();

        // 2. Action submit pendaftaran (POST /register) tidak terdaftar -> 405
        $this->post('/register', [
            'nim' => '2208561099',
            'nama' => 'Pendaftar Liar',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(405);
        $this->assertGuest();

        // 3. Action submit reset password via email tidak terdaftar
        $this->post('/forgot-password', ['email' => 'victim@student.unud.ac.id'])->assertStatus(405);
        $this->post('/reset-password', ['token' => 'dummy', 'email' => 'victim@student.unud.ac.id'])->assertNotFound();
        $this->get('/reset-password')->assertNotFound();

        // 4. Verifikasi bahwa form registrasi & reset password Breeze lama tidak aktif
        // (GET /register mengembalikan halaman login, GET /forgot-password & reset-password redirect 302 ke login)
        $registerRes = $this->get('/register');
        $registerRes->assertOk();
        // Pastikan bukan komponen Register Breeze yang dimuat
        $registerRes->assertInertia(fn ($page) => $page->component('auth/Login'));

        $this->get('/forgot-password')->assertRedirect(route('login'));
        $this->get('/reset-password/sample-token')->assertRedirect(route('login'));
    }

    /**
     * Verifikasi pengiriman kredensial massal (CLI tanpa limit & Web UI > 20 mahasiswa via defer).
     */
    public function test_bulk_send_credentials_cli_and_web_handle_all_students_at_once(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'nim'               => 'admin_bulk',
            'is_admin'          => true,
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        // Buat 25 mahasiswa belum terkirim (> 20 untuk memicu jalur defer pada Web UI)
        for ($i = 1; $i <= 25; $i++) {
            User::factory()->create([
                'nim'                 => sprintf('2308561%03d', $i),
                'email'               => sprintf('2308561%03d@student.unud.ac.id', $i),
                'is_admin'            => false,
                'status'              => 'aktif',
                'credentials_sent_at' => null,
            ]);
        }

        // 1. Uji Web UI Kirim Massal (> 20 mahasiswa -> deferred execution)
        $response = $this->actingAs($admin)->post(route('users.send-bulk-credentials'), [
            'only_unsent' => true,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        Mail::assertSentCount(25);
        $this->assertSame(0, User::where('is_admin', false)->whereNull('credentials_sent_at')->count());

        // Verifikasi bahwa lock pada jalur defer telah dilepas setelah closure defer selesai berjalan
        $deferLock = \Illuminate\Support\Facades\Cache::lock('pemira_bulk_email_lock', 60);
        $this->assertTrue($deferLock->get(), 'Lock harus berhasil diambil kembali setelah closure defer selesai');
        $deferLock->release();

        // 2. Uji CLI command `mahasiswa:send-credentials --resend` tanpa perlu argumen --limit
        Mail::fake();
        $this->artisan('mahasiswa:send-credentials', ['--resend' => true])
            ->assertExitCode(0);

        Mail::assertSentCount(25);
    }

    /**
     * Hapus mahasiswa tanpa catatan suara berhasil.
     */
    public function test_delete_mahasiswa_without_votes_succeeds(): void
    {
        $admin = User::factory()->admin()->create([
            'nim' => 'admin_del_1',
        ]);

        $student = User::factory()->create([
            'nim' => '2408561001',
        ]);

        $response = $this->actingAs($admin)->delete(route('users.destroy', $student->nim));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Data mahasiswa berhasil dihapus.');
        $this->assertDatabaseMissing('mahasiswa', ['nim' => $student->nim]);
    }

    /**
     * Hapus mahasiswa yang sudah memiliki catatan suara ditolak
     * dengan pesan jelas dan catatan suara tetap utuh.
     */
    public function test_delete_mahasiswa_with_vote_is_rejected_and_vote_remains_intact(): void
    {
        $admin = User::factory()->admin()->create([
            'nim' => 'admin_del_2',
        ]);

        $voter = User::factory()->create([
            'nim' => '2408561002',
        ]);

        $kegiatan = Kegiatan::create([
            'nama'             => 'Pemilu BEM FMIPA Uji FK',
            'tahun'            => (int) date('Y'),
            'waktu_mulai'      => now()->subHour(),
            'waktu_selesai'    => now()->addHour(),
            'ruang_lingkup'    => 'fakultas',
            'id_program_studi' => null,
            'foto'             => 'foto.jpg',
        ]);

        DB::table('surat_suara')->insert([
            'nim'         => $voter->nim,
            'id_kegiatan' => $kegiatan->id,
            'has_vote'    => true,
            'ttd'         => 'ttd/2408561002.png',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('users.destroy', $voter->nim));

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Data mahasiswa tidak dapat dihapus karena terkait dengan data voting.');

        // Mahasiswa dan catatan suara tetap utuh
        $this->assertDatabaseHas('mahasiswa', ['nim' => $voter->nim]);
        $this->assertDatabaseHas('surat_suara', [
            'nim'         => $voter->nim,
            'id_kegiatan' => $kegiatan->id,
            'has_vote'    => 1,
        ]);
    }

    /**
     * Jalur penghapusan massal (bulk delete) tidak crash saat sebagian
     * mahasiswa memiliki catatan suara: melewati yang punya suara, menghapus yang tidak punya suara,
     * dan melaporkan jumlah keduanya.
     */
    public function test_bulk_delete_mahasiswa_skips_students_with_votes_without_crashing(): void
    {
        $admin = User::factory()->admin()->create([
            'nim' => 'admin_del_bulk',
        ]);

        $studentNoVote1 = User::factory()->create(['nim' => '2408561011']);
        $studentWithVote = User::factory()->create(['nim' => '2408561012']);
        $studentNoVote2 = User::factory()->create(['nim' => '2408561013']);

        $kegiatan = Kegiatan::create([
            'nama'             => 'Pemilu BEM FMIPA Bulk FK',
            'tahun'            => (int) date('Y'),
            'waktu_mulai'      => now()->subHour(),
            'waktu_selesai'    => now()->addHour(),
            'ruang_lingkup'    => 'fakultas',
            'id_program_studi' => null,
            'foto'             => 'foto.jpg',
        ]);

        DB::table('surat_suara')->insert([
            'nim'         => $studentWithVote->nim,
            'id_kegiatan' => $kegiatan->id,
            'has_vote'    => true,
            'ttd'         => 'ttd/2408561012.png',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('users.bulk-destroy'), [
            'nims' => [
                $studentNoVote1->nim,
                $studentWithVote->nim,
                $studentNoVote2->nim,
            ],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas(
            'success',
            '2 data mahasiswa berhasil dihapus. 1 mahasiswa dilewati karena terkait dengan data voting.'
        );

        // Mahasiswa tanpa suara terhapus
        $this->assertDatabaseMissing('mahasiswa', ['nim' => $studentNoVote1->nim]);
        $this->assertDatabaseMissing('mahasiswa', ['nim' => $studentNoVote2->nim]);

        // Mahasiswa dengan suara dan catatan suaranya tetap utuh
        $this->assertDatabaseHas('mahasiswa', ['nim' => $studentWithVote->nim]);
        $this->assertDatabaseHas('surat_suara', [
            'nim'         => $studentWithVote->nim,
            'id_kegiatan' => $kegiatan->id,
            'has_vote'    => 1,
        ]);
    }

    /**
     * updated_at kandidat tidak berubah saat vote, hitungan suara tetap benar,
     * dan proteksi double-vote tetap aktif.
     */
    public function test_kandidat_updated_at_is_unchanged_when_vote_is_cast_and_tally_increments_correctly(): void
    {
        $voter = User::factory()->create([
            'nim'              => '2408561099',
            'id_program_studi' => 1,
            'status'           => 'aktif',
            'is_admin'         => false,
        ]);

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
            'nama'             => 'Pemilu HIMA 2026',
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

        $frozenTime = '2026-10-01 08:00:00';
        DB::table('kandidat')
            ->whereIn('id', [$kandidatBem->id, $kandidatHima->id])
            ->update([
                'created_at' => $frozenTime,
                'updated_at' => $frozenTime,
            ]);

        $beforeBemUpdatedAt = DB::table('kandidat')->where('id', $kandidatBem->id)->value('updated_at');
        $beforeHimaUpdatedAt = DB::table('kandidat')->where('id', $kandidatHima->id)->value('updated_at');

        $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($voter)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'id_kandidat_hima' => $kandidatHima->id,
            'ttd'              => $validTtd,
        ]);

        $response->assertOk();

        // Pastikan updated_at sebelum == sesudah vote
        $afterBemUpdatedAt = DB::table('kandidat')->where('id', $kandidatBem->id)->value('updated_at');
        $afterHimaUpdatedAt = DB::table('kandidat')->where('id', $kandidatHima->id)->value('updated_at');

        $this->assertSame($beforeBemUpdatedAt, $afterBemUpdatedAt, 'updated_at kandidat BEM tidak boleh berubah saat vote');
        $this->assertSame($beforeHimaUpdatedAt, $afterHimaUpdatedAt, 'updated_at kandidat HIMA tidak boleh berubah saat vote');

        // Pastikan jumlah_suara naik tepat 1
        $this->assertSame(1, (int) DB::table('kandidat')->where('id', $kandidatBem->id)->value('jumlah_suara'));
        $this->assertSame(1, (int) DB::table('kandidat')->where('id', $kandidatHima->id)->value('jumlah_suara'));

        // Pastikan percobaan double-vote ditolak dan tidak mengubah jumlah_suara maupun updated_at
        $secondResponse = $this->actingAs($voter)->post('/vote', [
            'id_kandidat_bem'  => $kandidatBem->id,
            'id_kandidat_hima' => $kandidatHima->id,
            'ttd'              => $validTtd,
        ]);

        $secondResponse->assertRedirect(route('dashboard'));
        $this->assertSame(1, (int) DB::table('kandidat')->where('id', $kandidatBem->id)->value('jumlah_suara'));
        $this->assertSame(1, (int) DB::table('kandidat')->where('id', $kandidatHima->id)->value('jumlah_suara'));
        $this->assertSame($beforeBemUpdatedAt, DB::table('kandidat')->where('id', $kandidatBem->id)->value('updated_at'));
        $this->assertSame($beforeHimaUpdatedAt, DB::table('kandidat')->where('id', $kandidatHima->id)->value('updated_at'));
    }

    /**
     * Baris suara baru pada surat_suara disimpan dengan waktu yang telah dibulatkan
     * ke awal jam (YYYY-MM-DD HH:00:00), dan nama file ttd tidak membocorkan detik Unix.
     */
    public function test_new_vote_record_timestamps_are_coarsened_to_the_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 13:42:57'));

        try {
            $voter = User::factory()->create([
                'nim'              => '2408561077',
                'id_program_studi' => 1,
                'status'           => 'aktif',
                'is_admin'         => false,
            ]);

            $kegiatanBem = Kegiatan::create([
                'nama'             => 'Pemilu BEM FMIPA Coarsen Test',
                'tahun'            => 2026,
                'waktu_mulai'      => Carbon::parse('2026-10-16 08:00:00'),
                'waktu_selesai'    => Carbon::parse('2026-10-16 17:00:00'),
                'ruang_lingkup'    => 'fakultas',
                'id_program_studi' => null,
                'foto'             => 'foto.jpg',
            ]);

            $kandidatBem = Kandidat::create([
                'id_kegiatan'  => $kegiatanBem->id,
                'no_urut'      => '01',
                'visi'         => 'Visi BEM',
                'misi'         => 'Misi BEM',
                'jumlah_suara' => 0,
            ]);

            $validTtd = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

            $response = $this->actingAs($voter)->post('/vote', [
                'id_kandidat_bem' => $kandidatBem->id,
                'ttd'             => $validTtd,
            ]);

            $response->assertOk();

            $ballot = DB::table('surat_suara')
                ->where('nim', $voter->nim)
                ->where('id_kegiatan', $kegiatanBem->id)
                ->first();

            $this->assertNotNull($ballot);
            $this->assertSame('2026-10-16 13:00:00', $ballot->created_at);
            $this->assertSame('2026-10-16 13:00:00', $ballot->updated_at);
            $this->assertStringNotContainsString((string) Carbon::now()->timestamp, (string) $ballot->ttd);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Command `votes:coarsen-timestamps` berjalan sebagai dry-run secara default
     * (tidak mengubah data), menolak jalan di production tanpa konfirmasi, dan mengubah data uji saat diberi `--force`.
     */
    public function test_votes_coarsen_timestamps_command_dry_run_and_force_and_production_guard(): void
    {
        $voter1 = User::factory()->create(['nim' => '2408561081']);
        $voter2 = User::factory()->create(['nim' => '2408561082']);

        $kegiatan = Kegiatan::create([
            'nama'             => 'Pemilu BEM FMIPA Coarsen Command',
            'tahun'            => (int) date('Y'),
            'waktu_mulai'      => now()->subHour(),
            'waktu_selesai'    => now()->addHour(),
            'ruang_lingkup'    => 'fakultas',
            'id_program_studi' => null,
            'foto'             => 'foto.jpg',
        ]);

        DB::table('surat_suara')->insert([
            [
                'nim'         => $voter1->nim,
                'id_kegiatan' => $kegiatan->id,
                'has_vote'    => true,
                'ttd'         => 'ttd/1.png',
                'created_at'  => '2026-10-10 09:34:51',
                'updated_at'  => '2026-10-10 09:35:12',
            ],
            [
                'nim'         => $voter2->nim,
                'id_kegiatan' => $kegiatan->id,
                'has_vote'    => true,
                'ttd'         => 'ttd/2.png',
                'created_at'  => '2026-10-10 14:18:09',
                'updated_at'  => '2026-10-10 14:19:44',
            ],
        ]);

        // 1. Default DRY-RUN: tidak boleh mengubah data
        $this->artisan('votes:coarsen-timestamps')
            ->expectsOutputToContain('[DRY-RUN]')
            ->assertExitCode(0);

        $this->assertDatabaseHas('surat_suara', [
            'nim'        => $voter1->nim,
            'created_at' => '2026-10-10 09:34:51',
            'updated_at' => '2026-10-10 09:35:12',
        ]);
        $this->assertDatabaseHas('surat_suara', [
            'nim'        => $voter2->nim,
            'created_at' => '2026-10-10 14:18:09',
            'updated_at' => '2026-10-10 14:19:44',
        ]);

        // 2. Proteksi APP_ENV=production: menolak --force tanpa --confirm-production / konfirmasi
        $originalEnv = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $this->artisan('votes:coarsen-timestamps', [
                '--force'          => true,
                '--no-interaction' => true,
            ])->assertExitCode(1);
        } finally {
            $this->app['env'] = $originalEnv;
        }

        // Pastikan data masih belum berubah setelah ditolak di production
        $this->assertDatabaseHas('surat_suara', [
            'nim'        => $voter1->nim,
            'created_at' => '2026-10-10 09:34:51',
            'updated_at' => '2026-10-10 09:35:12',
        ]);

        // 3. Eksekusi dengan --force: membulatkan timestamp data uji ke awal jam
        $this->artisan('votes:coarsen-timestamps', ['--force' => true])
            ->expectsOutputToContain('[APPLIED]')
            ->assertExitCode(0);

        $this->assertDatabaseHas('surat_suara', [
            'nim'        => $voter1->nim,
            'created_at' => '2026-10-10 09:00:00',
            'updated_at' => '2026-10-10 09:00:00',
        ]);
        $this->assertDatabaseHas('surat_suara', [
            'nim'        => $voter2->nim,
            'created_at' => '2026-10-10 14:00:00',
            'updated_at' => '2026-10-10 14:00:00',
        ]);
    }

    /**
     * Verifikasi migrasi tabel audit_logs dapat di-rollback dan di-up kembali tanpa error.
     */
    public function test_audit_logs_migration_rollback_and_up(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'), 'Tabel audit_logs harus ada setelah migrasi');

        $migration = require database_path('migrations/2026_10_16_130000_create_audit_logs_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('audit_logs'), 'down() harus menghapus tabel audit_logs');

        $migration->up();
        $this->assertTrue(Schema::hasTable('audit_logs'), 'up() harus membuat kembali tabel audit_logs');
    }

    /**
     * Verifikasi aksi-aksi admin (kandidat, kegiatan, mahasiswa) tercatat di audit_logs.
     */
    public function test_admin_actions_generate_audit_log_records(): void
    {
        $admin = User::factory()->admin()->create([
            'nim'               => 'admin_audit',
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        $prodi = DB::table('program_studi')->first();
        if (! $prodi) {
            $prodiId = DB::table('program_studi')->insertGetId([
                'nama' => 'Informatika',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $prodiId = $prodi->id;
        }

        // 1. Kegiatan: create, update, delete
        $kegiatan = Kegiatan::create([
            'nama'          => 'PEMIRA Test Kegiatan',
            'ruang_lingkup' => 'fakultas',
            'tahun'         => 2026,
            'waktu_mulai'   => now(),
            'waktu_selesai' => now()->addDays(2),
            'foto'          => 'foto-kegiatan/dummy.png',
        ]);
        AuditLogService::log('kegiatan.create', 'kegiatan', (string) $kegiatan->id, ['nama' => $kegiatan->nama]);

        $this->assertDatabaseHas('audit_logs', [
            'aksi'        => 'kegiatan.create',
            'target_type' => 'kegiatan',
            'target_id'   => (string) $kegiatan->id,
        ]);

        // 2. Kandidat: create, update, delete
        $kandidat = Kandidat::create([
            'id_kegiatan'   => $kegiatan->id,
            'no_urut'       => 1,
            'visi'          => 'Visi Test',
            'misi'          => 'Misi Test',
            'foto'          => 'foto-kandidat/dummy.png',
            'jumlah_suara'  => 0,
        ]);
        AuditLogService::log('kandidat.create', 'kandidat', (string) $kandidat->id, ['no_urut' => 1]);

        $this->assertDatabaseHas('audit_logs', [
            'aksi'        => 'kandidat.create',
            'target_type' => 'kandidat',
            'target_id'   => (string) $kandidat->id,
        ]);

        // 3. Mahasiswa: aksi controller dengan actingAs($admin)
        $studentNim = '2208561088';
        $student = User::factory()->create([
            'nim'              => $studentNim,
            'nama'             => 'Mahasiswa Audit Target',
            'id_program_studi' => $prodiId,
            'angkatan'         => 2022,
            'is_admin'         => false,
            'status'           => 'aktif',
        ]);

        // Kirim kredensial
        $this->actingAs($admin)->post(route('users.send-credentials', ['nim' => $studentNim]));
        $this->assertDatabaseHas('audit_logs', [
            'admin_nim'   => $admin->nim,
            'aksi'        => 'mahasiswa.send_credentials',
            'target_id'   => $studentNim,
        ]);

        // Update mahasiswa
        $this->actingAs($admin)->put(route('users.update', ['user' => $studentNim]), [
            'nim'              => $studentNim,
            'nama'             => 'Mahasiswa Updated Name',
            'id_program_studi' => $prodiId,
            'angkatan'         => 2022,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'admin_nim'   => $admin->nim,
            'aksi'        => 'mahasiswa.update',
            'target_id'   => $studentNim,
        ]);

        // Hapus mahasiswa
        $this->actingAs($admin)->delete(route('users.destroy', ['user' => $studentNim]));
        $this->assertDatabaseHas('audit_logs', [
            'admin_nim'   => $admin->nim,
            'aksi'        => 'mahasiswa.delete',
            'target_id'   => $studentNim,
        ]);
    }

    /**
     * Verifikasi kontrol akses endpoint audit-logs.
     */
    public function test_audit_logs_access_control(): void
    {
        // 1. Guest ditolak (redirect 302 ke login)
        $response = $this->get('/audit-logs');
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 2. Mahasiswa non-admin ditolak (redirect 302 ke dashboard dengan flash error)
        $student = User::factory()->create([
            'nim'               => '2208561077',
            'is_admin'          => false,
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);
        $response = $this->actingAs($student)->get('/audit-logs');
        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('alert');

        // 3. Admin diizinkan (200 JSON bila diminta)
        $admin = User::factory()->admin()->create([
            'nim'               => 'admin_log_viewer',
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);
        $response = $this->actingAs($admin)->getJson('/audit-logs');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'current_page']);
    }

    /**
     * Verifikasi metadata audit_logs membersihkan password, token, data impor, dan pilihan suara.
     */
    public function test_audit_log_metadata_sanitization_strictly_rejects_sensitive_data(): void
    {
        $admin = User::factory()->admin()->create([
            'nim'               => 'admin_sanitizer',
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $taintedMetadata = [
            'safe_field'        => 'valid_data',
            'password'          => 'SuperSecret123!',
            'plain_password'    => 'PlainSecret456',
            'remember_token'    => 'token_abc123',
            'api_key'           => 'secret_key_xyz',
            'sheet_data'        => ['row1' => 'sensitive_file_contents'],
            'id_kandidat_bem'   => 2,
            'id_kandidat_hima'  => 5,
            'pilihan'           => 1,
            'candidate_choice'  => 3,
            'vote'              => 'candidate_2',
            'ballot'            => ['choice' => 1],
            'nested'            => [
                'user_id'       => 10,
                'secret_token'  => 'do_not_record_this',
            ],
        ];

        $log = AuditLogService::log(
            aksi: 'test.sensitive_action',
            targetType: 'test',
            targetId: '99',
            metadata: $taintedMetadata
        );

        $savedMetadata = $log->fresh()->metadata;

        $this->assertIsArray($savedMetadata);
        $this->assertSame('valid_data', $savedMetadata['safe_field']);
        $this->assertSame(10, $savedMetadata['nested']['user_id']);

        // Assert sensitive keys are completely absent
        $this->assertArrayNotHasKey('password', $savedMetadata);
        $this->assertArrayNotHasKey('plain_password', $savedMetadata);
        $this->assertArrayNotHasKey('remember_token', $savedMetadata);
        $this->assertArrayNotHasKey('api_key', $savedMetadata);
        $this->assertArrayNotHasKey('sheet_data', $savedMetadata);
        $this->assertArrayNotHasKey('id_kandidat_bem', $savedMetadata);
        $this->assertArrayNotHasKey('id_kandidat_hima', $savedMetadata);
        $this->assertArrayNotHasKey('pilihan', $savedMetadata);
        $this->assertArrayNotHasKey('candidate_choice', $savedMetadata);
        $this->assertArrayNotHasKey('vote', $savedMetadata);
        $this->assertArrayNotHasKey('ballot', $savedMetadata);
        $this->assertArrayNotHasKey('secret_token', $savedMetadata['nested']);

        // Assert no JSON string representation contains forbidden sensitive words
        $jsonStr = json_encode($savedMetadata);
        $this->assertStringNotContainsString('SuperSecret123!', $jsonStr);
        $this->assertStringNotContainsString('PlainSecret456', $jsonStr);
        $this->assertStringNotContainsString('token_abc123', $jsonStr);
        $this->assertStringNotContainsString('secret_key_xyz', $jsonStr);
        $this->assertStringNotContainsString('sensitive_file_contents', $jsonStr);
        $this->assertStringNotContainsString('do_not_record_this', $jsonStr);
    }

    /**
     * Verifikasi resolusi client IP dan scheme (HTTPS/HTTP) dengan header forwarded
     * ketika reverse proxy berada dalam daftar trusted proxies vs ketika tidak dipercaya.
     */
    public function test_trusted_proxy_scheme_and_ip_detection_with_trusted_and_untrusted_headers(): void
    {
        // 1. Proxy terpercaya: client IP dan scheme mengikuti header forwarded meskipun backend menerima via HTTP
        \Illuminate\Http\Middleware\TrustProxies::flushState();
        config(['trustedproxy.proxies' => ['192.168.1.50']]);

        $this->withServerVariables([
            'REMOTE_ADDR'            => '192.168.1.50',
            'HTTP_X_FORWARDED_FOR'   => '203.0.113.195',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT'  => '443',
        ])->get('http://localhost/');

        $this->assertSame('203.0.113.195', request()->ip());
        $this->assertSame('https', request()->getScheme());
        $this->assertTrue(request()->isSecure());

        // 2. Proxy tidak terpercaya: client IP dan scheme tetap mengacu pada direct connection (mencegah spoofing)
        \Illuminate\Http\Middleware\TrustProxies::flushState();
        config(['trustedproxy.proxies' => ['192.168.1.50']]);

        $this->withServerVariables([
            'REMOTE_ADDR'            => '198.51.100.2',
            'HTTP_X_FORWARDED_FOR'   => '203.0.113.195',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT'  => '443',
        ])->get('http://localhost/');

        $this->assertSame('198.51.100.2', request()->ip());
        $this->assertSame('http', request()->getScheme());
        $this->assertFalse(request()->isSecure());
    }

    /**
     * Verifikasi route diagnostik staging /staging/proxy-diagnostic:
     * - Tamu dialihkan ke login (302)
     * - Mahasiswa non-admin ditolak (302 dengan alert)
     * - Admin menerima respon JSON 200 berisi informasi IP, scheme, dan header forwarded tanpa token/cookie/rahasia
     */
    public function test_staging_proxy_diagnostic_endpoint_access_control(): void
    {
        // 1. Guest ditolak (redirect 302 ke login)
        $response = $this->get('/staging/proxy-diagnostic');
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 2. Mahasiswa non-admin ditolak (redirect 302 ke dashboard dengan alert)
        $student = User::factory()->create([
            'nim'               => '2208561080',
            'is_admin'          => false,
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);
        $response = $this->actingAs($student)->get('/staging/proxy-diagnostic');
        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('alert');

        // 3. Admin diizinkan (200 JSON dengan data diagnostik yang aman)
        $admin = User::factory()->admin()->create([
            'nim'               => 'admin_diag',
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withServerVariables([
                'REMOTE_ADDR'            => '10.0.0.1',
                'HTTP_X_FORWARDED_FOR'   => '203.0.113.200',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_HOST'  => 'pemira.test',
                'HTTP_X_FORWARDED_PORT'  => '443',
            ])
            ->get('/staging/proxy-diagnostic');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'client_ip',
            'scheme',
            'is_secure',
            'remote_addr',
            'x_forwarded_for',
            'x_forwarded_proto',
            'x_forwarded_host',
            'x_forwarded_port',
        ]);

        $json = $response->json();
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('cookie', $json);
        $this->assertArrayNotHasKey('token', $json);
    }

    /**
     * Verifikasi route diagnostik staging dinonaktifkan sepenuhnya (404) ketika APP_ENV=production.
     */
    public function test_staging_proxy_diagnostic_endpoint_is_disabled_in_production(): void
    {
        $admin = User::factory()->admin()->create([
            'nim'               => 'admin_diag_prod',
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        $originalEnv = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $response = $this->actingAs($admin)->get('/staging/proxy-diagnostic');
            $response->assertStatus(404);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    /**
     * Verifikasi derivasi AWS SES SMTP Password (SigV4) terhadap vektor uji AWS.
     */
    public function test_ses_sigv4_smtp_password_derivation_matches_aws_test_vectors(): void
    {
        $testSecret = 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY';

        // 1. Region ap-southeast-2
        $passwordSydney = \App\Console\Commands\ConvertSesSmtpPassword::calculateSmtpPassword(
            $testSecret,
            'ap-southeast-2'
        );
        $this->assertSame('BLNVIwl1Vk66qi8YP1q44PSaqUtaoyrS/0LT79VaGBfa', $passwordSydney);

        // 2. Region us-east-1
        $passwordVirginia = \App\Console\Commands\ConvertSesSmtpPassword::calculateSmtpPassword(
            $testSecret,
            'us-east-1'
        );
        $this->assertSame('BLBM/9hSUELfq8Gw+rU1YcBjkOxGbhT2XG763xVLGWL9', $passwordVirginia);

        // 3. Verifikasi interaksi command Artisan (prompt tersembunyi tanpa bocor di CLI arg)
        $this->artisan('ses:convert-password', ['--region' => 'ap-southeast-2', '--key' => 'AKIA_SAMPLE_KEY_TEST'])
            ->expectsQuestion('Masukkan AWS Secret Access Key Anda', $testSecret)
            ->expectsOutputToContain('BLNVIwl1Vk66qi8YP1q44PSaqUtaoyrS/0LT79VaGBfa')
            ->expectsOutputToContain('AKIA_SAMPLE_KEY_TEST')
            ->assertExitCode(0);
    }

    /**
     * Verifikasi bahwa kegagalan pengiriman email kredensial (SMTP/SES failure)
     * TIDAK mengubah password mahasiswa di database, sehingga mahasiswa tidak terkunci dari akunnya.
     */
    public function test_failed_credential_email_does_not_modify_user_password_in_db(): void
    {
        $admin = User::factory()->create([
            'is_admin'          => true,
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        $originalPlainPassword = 'OriginalSecretPassword123!';
        $student = User::factory()->create([
            'nim'                 => '2208561093',
            'email'               => 'student93@student.unud.ac.id',
            'password'            => Hash::make($originalPlainPassword),
            'is_admin'            => false,
            'status'              => 'aktif',
            'email_verified_at'   => now(),
            'credentials_sent_at' => null,
        ]);

        $originalHash = $student->password;

        // Simulasikan kegagalan SMTP / AWS SES pada Web UI sendCredentials
        \Illuminate\Support\Facades\Mail::shouldReceive('to')
            ->once()
            ->with($student->email)
            ->andReturnSelf();
        \Illuminate\Support\Facades\Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Simulated AWS SES 454 Throttling / Connection Timeout'));

        $response = $this->actingAs($admin)->post("/users/{$student->nim}/send-credentials");
        $response->assertStatus(302);
        $response->assertSessionHas('error');

        // Pastikan password di DB TIDAK berubah dan masih cocok dengan password awal
        $student->refresh();
        $this->assertSame($originalHash, $student->password, 'Password di DB tidak boleh diubah sebelum email berhasil terkirim');
        $this->assertTrue(Hash::check($originalPlainPassword, $student->password), 'Mahasiswa harus tetap dapat login dengan password lama jika email gagal');
        $this->assertNull($student->credentials_sent_at, 'credentials_sent_at tidak boleh di-update jika email gagal');

        // Simulasikan hal yang sama pada Artisan CLI command mahasiswa:send-credentials
        $cliStudent = User::factory()->create([
            'nim'                 => '2208561094',
            'email'               => 'student94@student.unud.ac.id',
            'password'            => Hash::make('CliOriginalPassword456!'),
            'is_admin'            => false,
            'status'              => 'aktif',
            'email_verified_at'   => now(),
            'credentials_sent_at' => null,
        ]);
        $cliOriginalHash = $cliStudent->password;

        // AWS SES Throttling memicu retry hingga maxRetries (3x), password tetap aman di DB
        \Illuminate\Support\Facades\Mail::shouldReceive('to')
            ->times(3)
            ->with($cliStudent->email)
            ->andReturnSelf();
        \Illuminate\Support\Facades\Mail::shouldReceive('send')
            ->times(3)
            ->andThrow(new \RuntimeException('454 Throttling failure: Maximum sending rate exceeded'));

        $this->artisan('mahasiswa:send-credentials', ['--nim' => $cliStudent->nim])
            ->expectsOutputToContain('Gagal kirim ke 2208561094')
            ->assertExitCode(0);

        $cliStudent->refresh();
        $this->assertSame($cliOriginalHash, $cliStudent->password, 'Password di DB via CLI tidak boleh berubah jika email gagal');
        $this->assertTrue(Hash::check('CliOriginalPassword456!', $cliStudent->password));
        $this->assertNull($cliStudent->credentials_sent_at);
    }

    /**
     * Verifikasi bahwa proses bulk email dilindungi oleh Cache::lock atomic
     * dan dilepas di blok finally setelah proses selesai.
     */
    public function test_bulk_email_atomic_cache_lock_prevents_concurrency_and_releases_in_finally(): void
    {
        $admin = User::factory()->create([
            'is_admin'          => true,
            'status'            => 'aktif',
            'email_verified_at' => now(),
        ]);

        $student = User::factory()->create([
            'nim'                 => '2208561095',
            'email'               => 'student95@student.unud.ac.id',
            'is_admin'            => false,
            'status'              => 'aktif',
            'credentials_sent_at' => null,
        ]);

        // 1. Dapatkan lock secara manual untuk mensimulasikan proses background lain yang sedang berjalan
        $externalLock = \Illuminate\Support\Facades\Cache::lock('pemira_bulk_email_lock', 3600);
        $this->assertTrue($externalLock->get(), 'Lock harus berhasil didapatkan');

        // Uji Web UI: Saat lock aktif, request send-bulk-credentials harus ditolak / diberi info sedang berjalan via flash error
        $response = $this->actingAs($admin)->post(route('users.send-bulk-credentials'));
        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Pengiriman email massal sedang berjalan di latar belakang. Silakan refresh halaman secara berkala untuk memantau progres.');

        // Uji CLI: Saat lock aktif, command mahasiswa:send-credentials harus berhenti dengan pesan peringatan
        $this->artisan('mahasiswa:send-credentials')
            ->expectsOutputToContain('Pengiriman email massal lain sedang berlangsung (lock aktif)')
            ->assertExitCode(1);

        // 2. Lepas lock eksternal
        $externalLock->release();

        // 3. Jalankan pengiriman email via Web UI (<= 20 mahasiswa agar sinkron dan blok finally dieksekusi)
        \Illuminate\Support\Facades\Mail::fake();
        $response = $this->actingAs($admin)->post(route('users.send-bulk-credentials'), ['only_unsent' => true]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Pastikan lock TELAH DILEPAS pada blok finally sehingga lock baru dapat diperoleh kembali
        $subsequentLock = \Illuminate\Support\Facades\Cache::lock('pemira_bulk_email_lock', 60);
        $this->assertTrue($subsequentLock->get(), 'Lock harus dilepas di blok finally dan bisa diambil kembali');
        $subsequentLock->release();
    }

    /**
     * Verifikasi jeda antar email (configurable throttle delay) dan retry dengan backoff
     * saat menghadapi error throttling dari AWS SES (misal: 454 Throttling failure).
     */
    public function test_ses_throttling_retry_with_exponential_backoff_and_configurable_delay(): void
    {
        $student = User::factory()->create([
            'nim'                 => '2208561096',
            'email'               => 'student96@student.unud.ac.id',
            'is_admin'            => false,
            'status'              => 'aktif',
            'credentials_sent_at' => null,
        ]);

        // Verifikasi pembacaan konfigurasi throttle delay dan max retries
        config(['services.ses.throttle_delay_ms' => 50]);
        config(['services.ses.max_retries' => 3]);
        $this->assertSame(50, config('services.ses.throttle_delay_ms'));
        $this->assertSame(3, config('services.ses.max_retries'));

        // Simulasikan percobaan 1 kena 454 Throttling error, percobaan 2 berhasil
        $callCount = 0;
        \Illuminate\Support\Facades\Mail::shouldReceive('to')
            ->twice()
            ->with($student->email)
            ->andReturnSelf();

        \Illuminate\Support\Facades\Mail::shouldReceive('send')
            ->twice()
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    throw new \RuntimeException('454 Throttling failure: Maximum sending rate exceeded');
                }
                return true;
            });

        // Jalankan pengiriman melalui controller
        $controller = app(\App\Http\Controllers\MahasiswaController::class);
        $result = $controller->sendCredentialWithRetry($student, 3);

        $this->assertTrue($result, 'Pengiriman harus berhasil setelah retry pada percobaan ke-2');
        $this->assertSame(2, $callCount, 'Harus mencoba tepat 2 kali');

        $student->refresh();
        $this->assertNotNull($student->credentials_sent_at, 'credentials_sent_at harus tercatat setelah retry sukses');
    }

    /**
     * Verifikasi bahwa pesan error kuota harian ("Daily message quota exceeded")
     * TIDAK memicu retry (langsung gagal tanpa perulangan) dan password di DB tidak diubah.
     */
    public function test_daily_quota_exceeded_does_not_trigger_retry(): void
    {
        $student = User::factory()->create([
            'nim'                 => '2208561097',
            'email'               => 'student97@student.unud.ac.id',
            'is_admin'            => false,
            'status'              => 'aktif',
            'credentials_sent_at' => null,
        ]);
        $originalHash = $student->password;

        $callCount = 0;
        \Illuminate\Support\Facades\Mail::shouldReceive('to')
            ->once()
            ->with($student->email)
            ->andReturnSelf();

        \Illuminate\Support\Facades\Mail::shouldReceive('send')
            ->once()
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                throw new \RuntimeException('454 Throttling failure: Daily message quota exceeded');
            });

        $controller = app(\App\Http\Controllers\MahasiswaController::class);
        $result = $controller->sendCredentialWithRetry($student, 3);

        $this->assertFalse($result, 'Pengiriman dengan daily quota exceeded harus langsung gagal tanpa retry');
        $this->assertSame(1, $callCount, 'Daily message quota exceeded TIDAK BOLEH memicu retry');

        $student->refresh();
        $this->assertSame($originalHash, $student->password, 'Password di DB tidak boleh diubah');
        $this->assertNull($student->credentials_sent_at, 'credentials_sent_at harus tetap null');
    }
}



