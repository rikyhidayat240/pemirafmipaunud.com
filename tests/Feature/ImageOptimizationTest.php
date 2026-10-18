<?php

namespace Tests\Feature;

use App\Models\Kandidat;
use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (File::exists(storage_path('app/image-backups'))) {
            File::deleteDirectory(storage_path('app/image-backups'));
        }

        if (file_exists(base_path('php.ini'))) {
            @unlink(base_path('php.ini'));
        }

        parent::tearDown();
    }

    /**
     * Create a real test image file with given dimensions.
     */
    protected function createTestImage(string $name, int $width, int $height): UploadedFile
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg');
        $tempFile = tempnam(sys_get_temp_dir(), 'test_img_') . '.' . $ext;

        $pyCode = "from PIL import Image; img = Image.new('RGB', ({$width}, {$height}), color='blue'); img.save(r'{$tempFile}')";
        @shell_exec('python -c "' . $pyCode . '"');

        $mime = match ($ext) {
            'png'   => 'image/png',
            'webp'  => 'image/webp',
            default => 'image/jpeg',
        };

        return new UploadedFile($tempFile, $name, $mime, null, true);
    }

    public function test_oversized_candidate_photo_upload_is_resized_and_stored_as_webp(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin'          => true,
            'email_verified_at' => now(),
        ]);

        $student = User::factory()->create([
            'is_admin' => false,
        ]);

        $kegiatan = Kegiatan::create([
            'nama'          => 'Kegiatan Pemilihan Uji',
            'ruang_lingkup' => 'fakultas',
            'tahun'         => (int) date('Y'),
            'waktu_mulai'   => now()->subDay()->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDay()->format('Y-m-d H:i:s'),
            'foto'          => 'foto-kegiatan/dummy.webp',
        ]);

        $file = $this->createTestImage('oversized_candidate.jpg', 2000, 2000);

        $response = $this->actingAs($admin)->post('/candidates', [
            'id_kegiatan' => $kegiatan->id,
            'no_urut'     => '01',
            'visi'        => 'Visi uji optimasi kandidat',
            'misi'        => 'Misi uji optimasi kandidat',
            'foto'        => $file,
            'mahasiswa'   => [
                ['nim' => $student->nim, 'jabatan' => 'ketua'],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $kandidat = Kandidat::where('id_kegiatan', $kegiatan->id)->where('no_urut', '01')->first();
        $this->assertNotNull($kandidat);
        $this->assertNotNull($kandidat->foto);

        // Verify .webp extension and random hash filename policy
        $this->assertStringEndsWith('.webp', $kandidat->foto);
        $this->assertStringNotContainsString('oversized_candidate', $kandidat->foto);
        Storage::disk('public')->assertExists($kandidat->foto);

        // Verify dimensions downscaled to candidate preset max 800x800
        $storedFullPath = Storage::disk('public')->path($kandidat->foto);
        $dimensions = getimagesize($storedFullPath);
        $this->assertNotFalse($dimensions);
        $this->assertLessThanOrEqual(800, $dimensions[0]);
        $this->assertLessThanOrEqual(800, $dimensions[1]);
    }

    public function test_oversized_event_photo_upload_is_resized_and_stored_as_webp(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin'          => true,
            'email_verified_at' => now(),
        ]);

        $file = $this->createTestImage('oversized_event.jpg', 2400, 1600);

        $response = $this->actingAs($admin)->post('/events', [
            'nama'          => 'Kegiatan Pemilihan Event Oversized',
            'ruang_lingkup' => 'fakultas',
            'tahun'         => (int) date('Y'),
            'waktu_mulai'   => now()->subDay()->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDay()->format('Y-m-d H:i:s'),
            'foto'          => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $kegiatan = Kegiatan::where('nama', 'Kegiatan Pemilihan Event Oversized')->first();
        $this->assertNotNull($kegiatan);
        $this->assertNotNull($kegiatan->foto);

        // Verify .webp extension and random hash filename
        $this->assertStringEndsWith('.webp', $kegiatan->foto);
        $this->assertStringNotContainsString('oversized_event', $kegiatan->foto);
        Storage::disk('public')->assertExists($kegiatan->foto);

        // Verify dimensions downscaled to event preset max 1200x800
        $storedFullPath = Storage::disk('public')->path($kegiatan->foto);
        $dimensions = getimagesize($storedFullPath);
        $this->assertNotFalse($dimensions);
        $this->assertLessThanOrEqual(1200, $dimensions[0]);
        $this->assertLessThanOrEqual(800, $dimensions[1]);
    }

    public function test_oversized_student_avatar_upload_is_resized_and_stored_as_webp(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin'          => true,
            'email_verified_at' => now(),
        ]);

        $file = $this->createTestImage('oversized_avatar.jpg', 1600, 1600);

        $response = $this->actingAs($admin)->post('/users', [
            'nim'              => '2208561077',
            'nama'             => 'Mahasiswa Uji Avatar WebP',
            'id_program_studi' => 1,
            'angkatan'         => 2022,
            'avatar'           => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $student = User::where('nim', '2208561077')->first();
        $this->assertNotNull($student);
        $this->assertNotNull($student->avatar);

        // Verify .webp extension and random hash filename
        $this->assertStringEndsWith('.webp', $student->avatar);
        $this->assertStringNotContainsString('oversized_avatar', $student->avatar);
        Storage::disk('public')->assertExists($student->avatar);

        // Verify dimensions downscaled to student preset max 400x400
        $storedFullPath = Storage::disk('public')->path($student->avatar);
        $dimensions = getimagesize($storedFullPath);
        $this->assertNotFalse($dimensions);
        $this->assertLessThanOrEqual(400, $dimensions[0]);
        $this->assertLessThanOrEqual(400, $dimensions[1]);
    }

    public function test_dummy_stream_fallback_preserves_random_hash_filename(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin'          => true,
            'email_verified_at' => now(),
        ]);

        // Zero-byte dummy stream created by fake()->create
        $file = UploadedFile::fake()->create('raw_dummy.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($admin)->post('/users', [
            'nim'              => '2208561078',
            'nama'             => 'Mahasiswa Dummy Fallback',
            'id_program_studi' => 1,
            'angkatan'         => 2022,
            'avatar'           => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $student = User::where('nim', '2208561078')->first();
        $this->assertNotNull($student);
        $this->assertNotNull($student->avatar);
        $this->assertStringNotContainsString('raw_dummy', $student->avatar);
        Storage::disk('public')->assertExists($student->avatar);
    }

    public function test_batch_optimization_command_dry_run_does_not_modify_files_or_database(): void
    {
        Storage::fake('public');

        $origImage = $this->createTestImage('unoptimized_event.jpg', 1600, 1200);
        $origPath = 'foto-kegiatan/unoptimized_event.jpg';
        Storage::disk('public')->put($origPath, file_get_contents($origImage->getRealPath()));

        $kegiatan = Kegiatan::create([
            'nama'          => 'Event Dry Run Test',
            'ruang_lingkup' => 'fakultas',
            'tahun'         => (int) date('Y'),
            'waktu_mulai'   => now()->subDay()->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDay()->format('Y-m-d H:i:s'),
            'foto'          => $origPath,
        ]);

        $backupsBefore = glob(storage_path('app/image-backups/*'));

        $this->artisan('images:optimize-uploads')
            ->expectsOutputToContain('[DRY-RUN]')
            ->assertExitCode(0);

        // Database record must NOT be changed
        $this->assertEquals($origPath, Kegiatan::find($kegiatan->id)->foto);

        // Original file on disk must NOT be deleted or altered
        Storage::disk('public')->assertExists($origPath);

        // No backup directory created in dry run
        $backupsAfter = glob(storage_path('app/image-backups/*'));
        $this->assertEquals(count($backupsBefore ?: []), count($backupsAfter ?: []));
    }

    public function test_batch_optimization_command_with_force_converts_files_updates_db_and_creates_backup(): void
    {
        Storage::fake('public');

        $origImage = $this->createTestImage('unoptimized_candidate.jpg', 1600, 1600);
        $origPath = 'foto-kandidat/unoptimized_candidate.jpg';
        Storage::disk('public')->put($origPath, file_get_contents($origImage->getRealPath()));

        $kegiatan = Kegiatan::create([
            'nama'          => 'Event For Force Optimization',
            'ruang_lingkup' => 'fakultas',
            'tahun'         => (int) date('Y'),
            'waktu_mulai'   => now()->subDay()->format('Y-m-d H:i:s'),
            'waktu_selesai' => now()->addDay()->format('Y-m-d H:i:s'),
            'foto'          => 'foto-kegiatan/dummy.webp',
        ]);

        $kandidat = Kandidat::create([
            'id_kegiatan' => $kegiatan->id,
            'no_urut'     => '02',
            'visi'        => 'Visi force',
            'misi'        => 'Misi force',
            'foto'        => $origPath,
        ]);

        $this->artisan('images:optimize-uploads', ['--force' => true])
            ->expectsOutputToContain('[APPLIED]')
            ->assertExitCode(0);

        // Database path must be updated to .webp
        $updatedKandidat = Kandidat::find($kandidat->id);
        $this->assertStringEndsWith('.webp', $updatedKandidat->foto);
        $this->assertEquals('foto-kandidat/unoptimized_candidate.webp', $updatedKandidat->foto);

        // Converted file exists on public disk
        Storage::disk('public')->assertExists($updatedKandidat->foto);

        // Stored dimensions must be downscaled to max 800x800
        $storedFullPath = Storage::disk('public')->path($updatedKandidat->foto);
        $dimensions = getimagesize($storedFullPath);
        $this->assertNotFalse($dimensions);
        $this->assertLessThanOrEqual(800, $dimensions[0]);
        $this->assertLessThanOrEqual(800, $dimensions[1]);

        // Backup file must be created
        $backups = glob(storage_path('app/image-backups/*/' . $origPath));
        $this->assertNotEmpty($backups, 'Original file should be backed up');

        // Running second time with --force must be idempotent and skip already-converted files
        $this->artisan('images:optimize-uploads', ['--force' => true])
            ->expectsOutputToContain('Already WebP')
            ->assertExitCode(0);
    }
}
