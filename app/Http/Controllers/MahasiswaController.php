<?php

namespace App\Http\Controllers;

use App\Mail\MahasiswaCredentials;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MahasiswaController extends Controller
{
    /**
     * Get mahasiswa data from spreadsheet.
     */
    public function syncMahasiswa(int $year)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if ($year < 2020 || $year > (int) date('Y') + 1) {
            abort(422, 'Tahun tidak valid.');
        }

        // Get current users and users from spreadsheet
        $currentUsers = User::all()->keyBy('nim');
        $users = User::getMahasiswaFromSheet($year);

        $newCount = 0;
        $updatedCount = 0;
        $newUsers = [];

        // Pre-compute initial placeholder hash once so creating hundreds of accounts finishes in <2s
        $initialPasswordHash = Hash::make($this->generatePassword());

        // Sync users
        try {
            foreach ($users as $user) {
                $userArray = (array) $user;
                $fillableData = Arr::only($userArray, ['nim', 'nama', 'id_program_studi', 'angkatan']);
                $status = $userArray['status'] ?? 'aktif';

                $currentUser = $currentUsers->get($userArray['nim']);
                if ($currentUser) {
                    // Update existing user (preserve email/password if already set)
                    $currentUser->fill($fillableData);
                    $currentUser->status = $status;
                    $currentUser->save();
                    $updatedCount++;
                } else {
                    $nim = $userArray['nim'];
                    $email = $nim . '@student.unud.ac.id';

                    $newUser = new User();
                    $newUser->fill(array_merge($fillableData, [
                        'email'               => $email,
                        'password'            => $initialPasswordHash,
                        'email_verified_at'   => now(),
                        'credentials_sent_at' => null,
                    ]));
                    $newUser->status = $status;
                    $newUser->is_admin = false;
                    $newUser->save();

                    $newUsers[] = $newUser;
                    $newCount++;
                }
            }

            AuditLogService::log('mahasiswa.sync', 'user', (string) $year, [
                'year'          => $year,
                'new_count'     => $newCount,
                'updated_count' => $updatedCount,
            ]);

            if ($newCount > 0 && $newCount <= 20) {
                foreach ($newUsers as $newUser) {
                    try {
                        $plainPassword = $this->generatePassword();
                        Mail::to($newUser->email)->send(new MahasiswaCredentials($newUser, $plainPassword));
                        $newUser->update([
                            'password'            => Hash::make($plainPassword),
                            'credentials_sent_at' => now(),
                        ]);
                    } catch (\Exception $mailEx) {
                        Log::warning('Credential email failed for nim=' . $newUser->nim . ': ' . $mailEx->getMessage());
                    }
                }
            } elseif ($newCount > 20) {
                defer(function () use ($newUsers) {
                    set_time_limit(0);
                    ignore_user_abort(true);

                    foreach ($newUsers as $newUser) {
                        try {
                            $plainPassword = $this->generatePassword();
                            Mail::to($newUser->email)->send(new MahasiswaCredentials($newUser, $plainPassword));
                            $newUser->update([
                                'password'            => Hash::make($plainPassword),
                                'credentials_sent_at' => now(),
                            ]);
                        } catch (\Exception $mailEx) {
                            Log::warning('Credential email failed for nim=' . $newUser->nim . ': ' . $mailEx->getMessage());
                        }
                    }
                });

                return redirect()->back()->with('success',
                    "Sinkronisasi selesai. {$newCount} akun baru dibuat, {$updatedCount} data diperbarui. Pengiriman email kredensial sedang berjalan otomatis di latar belakang.");
            }

            return redirect()->back()->with('success',
                'Sinkronisasi selesai. ' . $newCount . ' akun baru dibuat, ' . $updatedCount . ' data diperbarui.');
        } catch (\Exception $e) {
            Log::error('Sync mahasiswa error year=' . $year . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyinkronkan data mahasiswa. Silakan coba lagi.');
        }
    }

    /**
     * Resend credential email to a specific student.
     */
    public function sendCredentials(string $nim)
    {
        $mahasiswa = User::where('nim', $nim)->where('is_admin', false)->firstOrFail();

        if (empty($mahasiswa->email)) {
            return back()->with('error', 'Mahasiswa ini belum memiliki email yang terdaftar.');
        }

        try {
            // Generate new password
            $plainPassword = $this->generatePassword();

            Mail::to($mahasiswa->email)->send(new MahasiswaCredentials($mahasiswa, $plainPassword));

            $mahasiswa->update([
                'password'            => Hash::make($plainPassword),
                'email_verified_at'   => $mahasiswa->email_verified_at ?? now(),
                'credentials_sent_at' => now(),
            ]);

            AuditLogService::log('mahasiswa.send_credentials', 'user', $mahasiswa->nim, [
                'nim' => $mahasiswa->nim,
            ]);

            return back()->with('success', 'Credentials berhasil dikirim ke ' . $mahasiswa->email);
        } catch (\Exception $e) {
            Log::error('Send credentials error nim=' . $nim . ': ' . $e->getMessage());
            return back()->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

    /**
     * Send credential email to all students (Bulk).
     */
    public function sendBulkCredentials(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $lock = Cache::lock('pemira_bulk_email_lock', 3600);
        if (! $lock->get()) {
            return back()->with('error', 'Pengiriman email massal sedang berjalan di latar belakang. Silakan refresh halaman secara berkala untuk memantau progres.');
        }

        $isDeferred = false;

        try {
            $onlyUnsent = $request->boolean('only_unsent', false);

            $query = User::where('is_admin', false)
                ->whereNotNull('email')
                ->where('email', '!=', '');

            if ($onlyUnsent) {
                $query->whereNull('credentials_sent_at');
            }

            $mahasiswaList = $query->get();

            if ($mahasiswaList->isEmpty()) {
                return back()->with('error', 'Tidak ada mahasiswa yang memenuhi syarat pengiriman email.');
            }

            $total = $mahasiswaList->count();

            AuditLogService::log('mahasiswa.send_bulk_credentials', 'user', null, [
                'total'       => $total,
                'only_unsent' => $onlyUnsent,
            ]);

            $throttleDelayMs = (int) config('services.ses.throttle_delay_ms', 100);
            $maxRetries      = (int) config('services.ses.max_retries', 3);

            // For large batches (> 20), execute in background after sending HTTP response to avoid 504 Gateway Timeout
            if ($total > 20) {
                $isDeferred = true;

                defer(function () use ($mahasiswaList, $lock, $throttleDelayMs, $maxRetries) {
                    set_time_limit(0);
                    ignore_user_abort(true);

                    try {
                        foreach ($mahasiswaList as $index => $mahasiswa) {
                            if ($index > 0 && $throttleDelayMs > 0) {
                                usleep($throttleDelayMs * 1000);
                            }

                            $this->sendCredentialWithRetry($mahasiswa, $maxRetries);
                        }
                    } finally {
                        optional($lock)->release();
                    }
                });

                return back()->with('success', "Pengiriman massal ke {$total} mahasiswa sedang berjalan di latar belakang! Silakan refresh halaman secara berkala untuk memantau status Terkirim.");
            }

            $sent = 0;
            $failed = 0;

            foreach ($mahasiswaList as $index => $mahasiswa) {
                if ($index > 0 && $throttleDelayMs > 0) {
                    usleep($throttleDelayMs * 1000);
                }

                if ($this->sendCredentialWithRetry($mahasiswa, $maxRetries)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }

            $msg = "Pengiriman bulk selesai: {$sent} email berhasil terkirim.";
            if ($failed > 0) {
                $msg .= " ({$failed} gagal).";
            }

            return back()->with($failed > 0 && $sent === 0 ? 'error' : 'success', $msg);
        } finally {
            if (! $isDeferred) {
                optional($lock)->release();
            }
        }
    }

    /**
     * Send credential email to a student with exponential backoff retry for throttling errors.
     */
    public function sendCredentialWithRetry(User $mahasiswa, int $maxRetries = 3): bool
    {
        $attempt = 0;
        $backoffBaseMs = (int) config('services.ses.backoff_base_ms', app()->environment('testing') ? 1 : 1000);

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                $plainPassword = $this->generatePassword();

                Mail::to($mahasiswa->email)->send(new MahasiswaCredentials($mahasiswa, $plainPassword));

                $mahasiswa->update([
                    'password'            => Hash::make($plainPassword),
                    'email_verified_at'   => $mahasiswa->email_verified_at ?? now(),
                    'credentials_sent_at' => now(),
                ]);

                return true;
            } catch (\Throwable $e) {
                if ($this->isThrottlingException($e) && $attempt < $maxRetries) {
                    $sleepMs = $backoffBaseMs * (int) pow(2, $attempt - 1);
                    Log::warning("Email throttling encountered for nim={$mahasiswa->nim} (attempt {$attempt}/{$maxRetries}). Retrying in {$sleepMs}ms: {$e->getMessage()}");
                    usleep($sleepMs * 1000);
                    continue;
                }

                Log::warning("Credential email failed for nim={$mahasiswa->nim}: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Determine if an exception represents an email rate limit / throttling error.
     * Matches only temporary rate-based errors (e.g. 454 Maximum sending rate exceeded, 429 Too Many Requests).
     * Excludes non-transient quota limits such as Daily message quota exceeded.
     */
    public function isThrottlingException(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        // Do not retry on daily quota exhaustion
        if (str_contains($message, 'daily') || str_contains($message, 'quota')) {
            return false;
        }

        return str_contains($message, 'maximum sending rate exceeded')
            || str_contains($message, 'rate exceeded')
            || str_contains($message, 'too many requests')
            || str_contains($message, '429')
            || (str_contains($message, '454') && (str_contains($message, 'throttl') || str_contains($message, 'rate')))
            || str_contains($message, 'throttling');
    }

    /**
     * Generate a secure random password using CSPRNG.
     */
    public function generatePassword(): string
    {
        return Str::password(12, letters: true, numbers: true, symbols: false, spaces: false);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch mahasiswa data with related program studi
        $mahasiswa = User::with('programStudi')
            ->where('is_admin', false)
            ->get();
        $programStudi = ProgramStudi::all();
        return Inertia::render('mahasiswa/Index', [
            'mahasiswa' => $mahasiswa,
            'programStudi' => $programStudi,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate input data
        $validatedData = $request->validate([
            'nim' => 'required|string|max:15|unique:mahasiswa,nim',
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:mahasiswa,email',
            'id_program_studi' => 'required|exists:program_studi,id',
            'angkatan' => 'required|integer|min:2000|max:2100',
            'avatar' => 'nullable|image|max:4096|mimes:jpeg,png,jpg,webp',
        ], [
            'nim.max' => 'NIM maksimal 15 digit.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah terdaftar.',
            'id_program_studi.exists' => 'Program studi tidak valid.',
            'angkatan.min' => 'Angkatan minimal tahun 2000.',
            'angkatan.max' => 'Angkatan maksimal tahun 2100.',
            'avatar.image' => 'Foto profil harus berupa gambar.',
            'avatar.max' => 'Ukuran foto profil maksimal 4MB.',
            'avatar.mimes' => 'Foto profil harus berupa file dengan ekstensi jpeg, png, jpg, atau webp.',
        ]);

        try {
            // Store avatar if exists
            $avatarPath = null;
            if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
                $avatarPath = ImageUploadService::storeStudent($request->file('avatar'));
            }

            // Generate password acak dan email @student.unud.ac.id dari NIM
            $plainPassword = $this->generatePassword();
            $studentEmail  = isset($validatedData['email']) && !empty($validatedData['email'])
                ? $validatedData['email']
                : $validatedData['nim'] . '@student.unud.ac.id';

            $newUser = new User();
            $newUser->fill([
                'nim'               => $validatedData['nim'],
                'nama'              => $validatedData['nama'],
                'email'             => $studentEmail,
                'id_program_studi'  => $validatedData['id_program_studi'],
                'angkatan'          => $validatedData['angkatan'],
                'avatar'            => $avatarPath,
                'password'          => Hash::make($plainPassword),
                'email_verified_at' => now(),
                'credentials_sent_at' => null,
            ]);
            $newUser->status = 'aktif';
            $newUser->is_admin = false;
            $newUser->save();

            AuditLogService::log('mahasiswa.create', 'user', $newUser->nim, [
                'nim'              => $newUser->nim,
                'nama'             => $newUser->nama,
                'id_program_studi' => $newUser->id_program_studi,
                'angkatan'         => $newUser->angkatan,
            ]);

            // Kirim email credentials
            try {
                Mail::to($studentEmail)->send(new MahasiswaCredentials($newUser, $plainPassword));
                $newUser->update(['credentials_sent_at' => now()]);
                return back()->with('success', 'Akun mahasiswa berhasil dibuat dan credentials telah dikirim ke ' . $studentEmail);
            } catch (\Throwable $mailEx) {
                Log::warning('Credential email failed for nim=' . $validatedData['nim'] . ': ' . $mailEx->getMessage());
                return back()->with('success', 'Akun mahasiswa berhasil dibuat. Email credentials gagal terkirim — gunakan tombol "Kirim Ulang" di tabel untuk mengirim ulang.');
            }
        } catch (\Throwable $e) {
            Log::error('Create mahasiswa error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat membuat data mahasiswa. Silakan coba lagi.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $nim)
    {
        // Validate input data
        $validatedData = $request->validate([
            'nim' => 'required|string|max:15',
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'id_program_studi' => 'required|exists:program_studi,id',
            'angkatan' => 'required|integer|min:2000|max:2100',
            'avatar' => 'nullable|image|max:4096|mimes:jpeg,png,jpg,webp',
        ], [
            'nim.max' => 'NIM maksimal 15 digit.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'email.max' => 'Email maksimal 255 karakter.',
            'id_program_studi.exists' => 'Program studi tidak valid.',
            'angkatan.min' => 'Angkatan minimal tahun 2000.',
            'angkatan.max' => 'Angkatan maksimal tahun 2100.',
            'avatar.image' => 'Foto profil harus berupa gambar.',
            'avatar.max' => 'Ukuran foto profil maksimal 4MB.',
            'avatar.mimes' => 'Foto profil harus berupa file dengan ekstensi jpeg, png, jpg, atau webp.',
        ]);

        try {
            // Find the mahasiswa to update
            $mahasiswa = User::where('nim', $nim)->firstOrFail();

            // Prevent changing NIM if student already has vote or candidate records
            if ($validatedData['nim'] !== $nim && $this->hasProtectedRecords($nim)) {
                return redirect()->back()->with('error', 'NIM mahasiswa tidak dapat diubah karena sudah terkait dengan data voting atau kandidat.');
            }

            // Check if there is any data with the same NIM and email (excluding current mahasiswa)
            $existingNim = User::where('nim', $validatedData['nim'])
                ->whereNot('nim', $nim)
                ->first();
            if ($existingNim) {
                return redirect()->back()->with('error', 'NIM sudah terdaftar oleh mahasiswa lain.');
            }

            if (!empty($validatedData['email'])) {
                $existingEmail = User::where('email', $validatedData['email'])
                    ->whereNot('nim', $nim)
                    ->whereNotNull('email')
                    ->first();
                if ($existingEmail) {
                    return redirect()->back()->with('error', 'Email sudah terdaftar oleh mahasiswa lain.');
                }
            }

            // Handle avatar upload
            $avatarPath = $mahasiswa->avatar;
            if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
                // Delete old avatar if exists
                if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }

                $avatarPath = ImageUploadService::storeStudent($request->file('avatar'));
            }

            // Update mahasiswa — preserve existing password, only update profile fields
            $updateData = [
                'nim' => $validatedData['nim'],
                'nama' => $validatedData['nama'],
                'id_program_studi' => $validatedData['id_program_studi'],
                'angkatan' => $validatedData['angkatan'],
                'avatar' => $avatarPath,
            ];

            // Only update email if provided and different
            if (!empty($validatedData['email'])) {
                $updateData['email'] = $validatedData['email'];
            }

            $mahasiswa->update($updateData);

            AuditLogService::log('mahasiswa.update', 'user', $mahasiswa->nim, [
                'nim'              => $mahasiswa->nim,
                'nama'             => $mahasiswa->nama,
                'id_program_studi' => $mahasiswa->id_program_studi,
                'angkatan'         => $mahasiswa->angkatan,
            ]);

            return redirect()->back()->with('success', 'Data mahasiswa berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Update mahasiswa error nim=' . $nim . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data mahasiswa. Silakan coba lagi.');
        }
    }

    /**
     * Check whether the student has protected vote or candidate records.
     */
    protected function hasProtectedRecords(string $nim): bool
    {
        return DB::table('surat_suara')->where('nim', $nim)->exists()
            || DB::table('mahasiswa_kandidat')->where('nim', $nim)->exists();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $nim)
    {
        try {
            // Find mahasiswa
            $mahasiswa = User::where('nim', $nim)->firstOrFail();

            if ($this->hasProtectedRecords($mahasiswa->nim)) {
                return redirect()->back()->with('error', 'Data mahasiswa tidak dapat dihapus karena terkait dengan data voting.');
            }

            $avatarPath = $mahasiswa->avatar;

            // Delete mahasiswa first so avatar is not lost if DB rejects deletion
            $mahasiswa->delete();

            // Delete avatar if exists
            if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }

            AuditLogService::log('mahasiswa.delete', 'user', $nim, [
                'nim' => $nim,
            ]);

            return redirect()->back()->with('success', 'Data mahasiswa berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Delete mahasiswa error nim=' . $nim . ': ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                return redirect()->back()->with('error', 'Data mahasiswa tidak dapat dihapus karena terkait dengan data voting.');
            }
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data mahasiswa. Silakan coba lagi.');
        }
    }

    /**
     * Bulk remove students from storage while skipping those with vote/candidate records.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'nims'   => 'required|array|min:1',
            'nims.*' => 'required|string|max:15',
        ]);

        $targetUsers = User::whereIn('nim', array_unique($validated['nims']))
            ->where('is_admin', false)
            ->get();

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($targetUsers as $mahasiswa) {
            if ($this->hasProtectedRecords($mahasiswa->nim)) {
                $skippedCount++;
                continue;
            }

            try {
                $avatarPath = $mahasiswa->avatar;
                $mahasiswa->delete();

                if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }

                $deletedCount++;
            } catch (\Exception $e) {
                Log::warning('Bulk delete skipped nim=' . $mahasiswa->nim . ': ' . $e->getMessage());
                $skippedCount++;
            }
        }

        AuditLogService::log('mahasiswa.bulk_delete', 'user', null, [
            'deleted_count' => $deletedCount,
            'skipped_count' => $skippedCount,
        ]);

        if ($deletedCount === 0 && $skippedCount > 0) {
            return redirect()->back()->with(
                'error',
                "Tidak ada mahasiswa yang dihapus. {$skippedCount} mahasiswa dilewati karena terkait dengan data voting."
            );
        }

        $message = "{$deletedCount} data mahasiswa berhasil dihapus.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} mahasiswa dilewati karena terkait dengan data voting.";
        }

        return redirect()->back()->with('success', $message);
    }
}
