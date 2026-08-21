<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        $request->fulfill();

        // Generate surat suara
        $user = $request->user();
        $kegiatan = Kegiatan::where('tahun', now()->year)
            ->where('waktu_selesai', '>', now())
            ->where(function ($query) use ($user) {
                $query->where('id_program_studi', $user->id_program_studi)
                    ->orWhere('ruang_lingkup', 'fakultas');
            })
            ->with('mahasiswa')
            ->get();
        if ($kegiatan->isNotEmpty()) {
            foreach ($kegiatan as $k) {
                $k->mahasiswa()->syncWithoutDetaching([$user->nim => ['has_vote' => false]]);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }
}
