<?php

namespace App\Http\Controllers;

use App\Mail\MahasiswaCredentials;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Http\Request;
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
        // SEC-07: Validate year range
        if ($year < 2020 || $year > (int) date('Y') + 1) {
            abort(422, 'Tahun tidak valid.');
        }

        // Get current users and users from spreadsheet
        $currentUsers = User::all();
        $users = User::getMahasiswaFromSheet($year);

        $newCount = 0;
        $updatedCount = 0;

        // Sync users
        try {
            foreach ($users as $user) {
                $currentUser = $currentUsers->firstWhere('nim', $user['nim']);
                if ($currentUser) {
                    // Update existing user (preserve email/password if already set)
                    $currentUser->update((array)$user);
                    $updatedCount++;
                } else {
                    // Create new user with auto-generated password
                    $plainPassword = $this->generatePassword();
                    $nim = $user['nim'];
                    $email = $nim . '@student.unud.ac.id';

                    $newUser = User::create(array_merge((array)$user, [
                        'email'             => $email,
                        'password'          => Hash::make($plainPassword),
                        'email_verified_at' => now(),
                        'credentials_sent_at' => null, // will be set after email is sent
                    ]));

                    // Try to send credential email (non-blocking)
                    try {
                        Mail::to($email)->send(new MahasiswaCredentials($newUser, $plainPassword));
                        $newUser->update(['credentials_sent_at' => now()]);
                    } catch (\Exception $mailEx) {
                        Log::warning('Credential email failed for nim=' . $nim . ': ' . $mailEx->getMessage());
                        // Don't abort — account is created, email can be re-sent via artisan command
                    }

                    $newCount++;
                }
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
            $mahasiswa->update([
                'password'            => Hash::make($plainPassword),
                'email_verified_at'   => now(),
            ]);

            Mail::to($mahasiswa->email)->send(new MahasiswaCredentials($mahasiswa, $plainPassword));
            $mahasiswa->update(['credentials_sent_at' => now()]);

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

        $sent = 0;
        $failed = 0;

        foreach ($mahasiswaList as $mahasiswa) {
            try {
                $plainPassword = $this->generatePassword();
                $mahasiswa->update([
                    'password'          => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                ]);

                Mail::to($mahasiswa->email)->send(new MahasiswaCredentials($mahasiswa, $plainPassword));
                $mahasiswa->update(['credentials_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::warning("Bulk credential email failed for nim={$mahasiswa->nim}: " . $e->getMessage());
            }
        }

        $msg = "Pengiriman bulk selesai: {$sent} email berhasil terkirim.";
        if ($failed > 0) {
            $msg .= " ({$failed} gagal).";
        }

        return back()->with($failed > 0 && $sent === 0 ? 'error' : 'success', $msg);
    }

    /**
     * Generate a secure random 8-character password.
     */
    private function generatePassword(): string
    {
        $upper  = strtoupper(Str::random(2));
        $lower  = strtolower(Str::random(4));
        $digits = rand(10, 99);
        $chars  = str_split($upper . $lower . $digits);
        shuffle($chars);
        return implode('', $chars);
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
                // Get the uploaded file
                $avatarFile = $request->file('avatar');
                $avatarName = time() . '_' . $avatarFile->getClientOriginalName();

                // Define the avatar destination path
                $avatarDestination = Storage::disk('public')->path('foto-mahasiswa');
                if (!file_exists($avatarDestination)) {
                    mkdir($avatarDestination, 0755, true);
                }

                // Store the avatar file
                Storage::disk('public')->putFileAs('foto-mahasiswa', $avatarFile, $avatarName);
                $avatarPath = 'foto-mahasiswa/' . $avatarName;
            }

            // Generate password acak dan email @student.unud.ac.id dari NIM
            $plainPassword = $this->generatePassword();
            $studentEmail  = isset($validatedData['email']) && !empty($validatedData['email'])
                ? $validatedData['email']
                : $validatedData['nim'] . '@student.unud.ac.id';

            $newUser = User::create([
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

            // Kirim email credentials
            try {
                Mail::to($studentEmail)->send(new MahasiswaCredentials($newUser, $plainPassword));
                $newUser->update(['credentials_sent_at' => now()]);
                return back()->with('success', 'Akun mahasiswa berhasil dibuat dan credentials telah dikirim ke ' . $studentEmail);
            } catch (\Exception $mailEx) {
                Log::warning('Credential email failed for nim=' . $validatedData['nim'] . ': ' . $mailEx->getMessage());
                return back()->with('success', 'Akun mahasiswa berhasil dibuat. Email credentials gagal terkirim — gunakan tombol "Kirim Ulang" di tabel untuk mengirim ulang.');
            }
        } catch (\Exception $e) {
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

            // Check if there is any data with the same NIM and email (excluding current mahasiswa)
            $existingNim = User::where('nim', $validatedData['nim'])
                ->whereNot('nim', $nim)
                ->first();
            if ($existingNim) {
                return redirect()->back()->with('error', 'NIM sudah terdaftar oleh mahasiswa lain.');
            }

            $existingEmail = User::where('email', $validatedData['email'])
                ->whereNot('nim', $nim)
                ->whereNotNull('email')
                ->first();
            if ($existingEmail) {
                return redirect()->back()->with('error', 'Email sudah terdaftar oleh mahasiswa lain.');
            }

            // Handle avatar upload
            $avatarPath = $mahasiswa->avatar;
            if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
                // Delete old avatar if exists
                if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }

                // Store new avatar
                $avatarFile = $request->file('avatar');
                $avatarName = time() . '_' . $avatarFile->getClientOriginalName();
                Storage::disk('public')->putFileAs('foto-mahasiswa', $avatarFile, $avatarName);
                $avatarPath = 'foto-mahasiswa/' . $avatarName;
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

            return redirect()->back()->with('success', 'Data mahasiswa berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Update mahasiswa error nim=' . $nim . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data mahasiswa. Silakan coba lagi.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $nim)
    {
        try {
            // Find mahasiswa
            $mahasiswa = User::where('nim', $nim)->firstOrFail();

            // Delete avatar if exists
            if ($mahasiswa->avatar && Storage::disk('public')->exists($mahasiswa->avatar)) {
                Storage::disk('public')->delete($mahasiswa->avatar);
            }

            // Delete mahasiswa
            $mahasiswa->delete();
            return redirect()->back()->with('success', 'Data mahasiswa berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Delete mahasiswa error nim=' . $nim . ': ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                return redirect()->back()->with('error', 'Data mahasiswa tidak dapat dihapus karena terkait dengan data voting.');
            }
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data mahasiswa. Silakan coba lagi.');
        }
    }
}
