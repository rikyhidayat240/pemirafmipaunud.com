<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $request->validated();
            $user = $request->user();
            $currentEmail = $user->email;

            // Store avatar if exists
            $avatarPath = null;
            if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
                $avatarFile = $request->file('avatar');
                $avatarName = time() . '_' . $avatarFile->getClientOriginalName();

                $avatarDestination = Storage::disk('public')->path('foto-mahasiswa');
                if (!file_exists($avatarDestination)) {
                    mkdir($avatarDestination, 0755, true);
                }

                Storage::disk('public')->putFileAs('foto-mahasiswa', $avatarFile, $avatarName);
                $avatarPath = 'foto-mahasiswa/' . $avatarName;
            }

            // Check existing email
            $existingEmail = User::where('email', $request->email)
                ->whereNot('nim', $user->nim)
                ->whereNotNull('email')
                ->exists();

            if ($existingEmail) {
                return back()->withErrors(['email' => 'Email sudah terdaftar oleh mahasiswa lain.']);
            }

            // Check domain email @student.unud.ac.id
            $emailDomain = substr(strrchr($request->email, "@"), 1);
            if ($emailDomain !== 'student.unud.ac.id') {
                return back()->withErrors(['email' => 'Email harus menggunakan domain @student.unud.ac.id.']);
            }

            // Check if email changed BEFORE updating
            $emailChanged = $currentEmail !== $request->email;

            // Update user
            $user->update([
                'nama' => $request->nama,
                'email' => $request->email,
                'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
                'avatar' => $avatarPath ?? $user->avatar,
            ]);

            // If email changed, redirect to verification
            if ($emailChanged) {
                return to_route('verification.notice')
                    ->with('status', 'Verifikasi email anda terlebih dahulu.');
            }

            return redirect()->back()->with('success', 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui profil: ' . $e->getMessage());
        }
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
