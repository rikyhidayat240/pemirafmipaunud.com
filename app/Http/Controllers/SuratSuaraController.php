<?php

namespace App\Http\Controllers;

use App\Models\Kandidat;
use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuratSuaraController extends Controller
{
    /**
     * Show vote page
     */
    public function show()
    {
        // 1. Eager load kegiatan to avoid N+1 queries when checking pivot later
        $user = User::with('kegiatan')->where('nim', auth('web')->user()->nim)->first();

        // Check user requirements
        if ($user->is_admin) {
            return redirect()->back()->with('alert', ['type' => 'error', 'title' => 'Anda adalah Admin', 'message' => 'Admin tidak dapat mengakses halaman pemilihan.']);
        }
        if (!$user->isActive()) {
            return redirect()->back()->with('alert', ['type' => 'error', 'title' => 'Akun Tidak Aktif', 'message' => 'Anda tidak terdaftar sebagai mahasiswa aktif sehingga tidak dapat melakukan pemilihan.']);
        }

        // Cache kegiatan BEM (fakultas level) for 24 hours
        $kegiatanBem = \Illuminate\Support\Facades\Cache::remember('kegiatan_bem_' . date('Y'), 60 * 60 * 24, function () {
            return Kegiatan::where('tahun', date('Y'))
                ->where('waktu_selesai', '>', now())
                ->where('ruang_lingkup', 'fakultas')
                ->with(['kandidat.mahasiswa', 'programStudi'])
                ->first();
        });

        // Cache kegiatan HIMA (program studi level) for 24 hours
        $kegiatanHima = \Illuminate\Support\Facades\Cache::remember('kegiatan_hima_' . date('Y') . '_prodi_' . $user->id_program_studi, 60 * 60 * 24, function () use ($user) {
            return Kegiatan::where('tahun', date('Y'))
                ->where('waktu_selesai', '>', now())
                ->where('ruang_lingkup', 'program studi')
                ->where('id_program_studi', $user->id_program_studi)
                ->with(['kandidat.mahasiswa', 'programStudi'])
                ->first();
        });

        // 1. Check if kegiatan exists
        if (!$kegiatanBem || !$kegiatanHima) {
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Tidak Ada Kegiatan Pemilihan', 'message' => 'Saat ini tidak ada kegiatan pemilihan yang aktif.']);
        }

        // 2. Check if user has valid ballot (surat suara) using loaded relation in memory
        $hasBallotBem = $user->kegiatan->contains('id', $kegiatanBem->id);
        $hasBallotHima = $user->kegiatan->contains('id', $kegiatanHima->id);

        if (!$hasBallotBem || !$hasBallotHima) {
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Tidak Ada Surat Suara', 'message' => 'Anda tidak memiliki surat suara untuk pemilihan ini.']);
        }

        // 3. Check if kegiatan has started
        if (now()->lt($kegiatanBem->waktu_mulai) || now()->lt($kegiatanHima->waktu_mulai)) {
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Pemilihan Belum Dimulai', 'message' => 'Kegiatan pemilihan belum dimulai. Silakan coba lagi nanti.']);
        }

        // 4. Check if user has already voted using loaded relation in memory
        $hasVotedBem = $user->kegiatan->contains(function ($kegiatan) use ($kegiatanBem) {
            return $kegiatan->id === $kegiatanBem->id && $kegiatan->pivot->has_vote;
        });

        $hasVotedHima = $user->kegiatan->contains(function ($kegiatan) use ($kegiatanHima) {
            return $kegiatan->id === $kegiatanHima->id && $kegiatan->pivot->has_vote;
        });

        if ($hasVotedBem && $hasVotedHima) {
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Pemilihan Sudah Dilakukan', 'message' => 'Anda hanya dapat melakukan pemilihan sekali. Terima kasih telah berpartisipasi.']);
        }

        return Inertia::render('Vote', [
            'kegiatanBem' => $kegiatanBem,
            'kegiatanHima' => $kegiatanHima,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_kandidat_bem' => 'required|exists:kandidat,id',
            'id_kandidat_hima' => 'required|exists:kandidat,id',
            'ttd' => 'required|string', // base64 data URL
        ]);

        $kandidatBem = Kandidat::find($request->id_kandidat_bem);
        $kandidatHima = Kandidat::find($request->id_kandidat_hima);

        $user = User::where('nim', auth('web')->user()->nim)->first();

        // Wrap everything in a database transaction
        return DB::transaction(function () use ($request, $kandidatBem, $kandidatHima, $user) {
            
            // SECURITY FIX: Double Vote Check inside Transaction Lock
            // We check the database directly in this transaction to see if they already voted
            $alreadyVotedBem = DB::table('mahasiswa_kegiatan')
                ->where('nim', $user->nim)
                ->where('id_kegiatan', $kandidatBem->id_kegiatan)
                ->where('has_vote', true)
                ->exists();

            $alreadyVotedHima = DB::table('mahasiswa_kegiatan')
                ->where('nim', $user->nim)
                ->where('id_kegiatan', $kandidatHima->id_kegiatan)
                ->where('has_vote', true)
                ->exists();

            if ($alreadyVotedBem || $alreadyVotedHima) {
                // If they already voted, abort the transaction and do NOT increment votes
                return redirect()->route('dashboard')
                    ->with('alert', ['type' => 'error', 'title' => 'Pemilihan Sudah Dilakukan', 'message' => 'Anda sudah memberikan suara! Suara ganda ditolak.']);
            }

            // Process and save signature image
            $ttdPath = null;
            if ($request->ttd) {
                $image = $request->ttd;
                if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                    $image = substr($image, strpos($image, ',') + 1);
                    $type = strtolower($type[1]);
                    
                    $image = base64_decode($image);
                    if ($image === false) {
                        return back()->with('alert', ['type' => 'error', 'title' => 'Error', 'message' => 'Gagal memproses tanda tangan.']);
                    }
                    
                    $filename = $user->nim . '_' . time() . '_' . Str::random(10) . '.' . $type;
                    Storage::disk('public')->put('ttd-mahasiswa/' . $filename, $image);
                    $ttdPath = 'ttd-mahasiswa/' . $filename;
                }
            }

            // Mark user as voted in pivot table
            $user->kegiatan()->syncWithoutDetaching([
                $kandidatBem->id_kegiatan => ['has_vote' => true, 'ttd' => $ttdPath],
                $kandidatHima->id_kegiatan => ['has_vote' => true, 'ttd' => $ttdPath]
            ]);

            // Increment candidate votes
            $kandidatBem->increment('jumlah_suara');
            $kandidatHima->increment('jumlah_suara');

            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'success', 'title' => 'Pemilihan Berhasil', 'message' => 'Terima kasih telah berpartisipasi dalam pemilihan.']);
        });
    }
}
