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

        // Fetch kegiatan BEM (fakultas level) without cache to prevent stale data when events are newly created
        $kegiatanBem = Kegiatan::where('tahun', date('Y'))
            ->where('waktu_selesai', '>', now())
            ->where('ruang_lingkup', 'fakultas')
            ->with(['kandidat.mahasiswa', 'programStudi'])
            ->first();

        // Fetch kegiatan HIMA (program studi level) without cache
        $kegiatanHima = Kegiatan::where('tahun', date('Y'))
            ->where('waktu_selesai', '>', now())
            ->where('ruang_lingkup', 'program studi')
            ->where('id_program_studi', $user->id_program_studi)
            ->with(['kandidat.mahasiswa', 'programStudi'])
            ->first();

        // 1. Check if ANY kegiatan exists
        if (!$kegiatanBem && !$kegiatanHima) {
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Tidak Ada Kegiatan Pemilihan', 'message' => 'Saat ini tidak ada kegiatan pemilihan yang aktif.']);
        }

        // 2. Check if kegiatan has started
        $bemStarted = $kegiatanBem ? now()->gte($kegiatanBem->waktu_mulai) : false;
        $himaStarted = $kegiatanHima ? now()->gte($kegiatanHima->waktu_mulai) : false;

        // If neither has started, but at least one exists
        if (!$bemStarted && !$himaStarted) {
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Pemilihan Belum Dimulai', 'message' => 'Kegiatan pemilihan belum dimulai. Silakan coba lagi nanti.']);
        }

        // 3. Check if user has already voted using loaded relation in memory
        $hasVotedBem = $kegiatanBem ? $user->kegiatan->contains(function ($kegiatan) use ($kegiatanBem) {
            return $kegiatan->id === $kegiatanBem->id && $kegiatan->pivot->has_vote;
        }) : true; // default to true if it doesn't exist so we skip it

        $hasVotedHima = $kegiatanHima ? $user->kegiatan->contains(function ($kegiatan) use ($kegiatanHima) {
            return $kegiatan->id === $kegiatanHima->id && $kegiatan->pivot->has_vote;
        }) : true;

        if ($hasVotedBem && $hasVotedHima) {
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Pemilihan Sudah Dilakukan', 'message' => 'Anda sudah melakukan pemilihan untuk semua kegiatan yang tersedia. Terima kasih telah berpartisipasi.']);
        }

        // If they already voted for one, or if it hasn't started yet, we can nullify it so it doesn't show in the Vote page
        if ($hasVotedBem || !$bemStarted) $kegiatanBem = null;
        if ($hasVotedHima || !$himaStarted) $kegiatanHima = null;

        return Inertia::render('Vote', [
            'kegiatanBem' => $kegiatanBem,
            'kegiatanHima' => $kegiatanHima,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_kandidat_bem' => 'nullable|exists:kandidat,id',
            'id_kandidat_hima' => 'nullable|exists:kandidat,id',
            'ttd' => 'required|string', // base64 data URL
        ]);

        if (!$request->id_kandidat_bem && !$request->id_kandidat_hima) {
            return back()->with('alert', ['type' => 'error', 'title' => 'Error', 'message' => 'Tidak ada kandidat yang dipilih.']);
        }

        $kandidatBem = $request->id_kandidat_bem ? Kandidat::find($request->id_kandidat_bem) : null;
        $kandidatHima = $request->id_kandidat_hima ? Kandidat::find($request->id_kandidat_hima) : null;

        $user = User::where('nim', auth('web')->user()->nim)->first();

        // Wrap everything in a database transaction
        return DB::transaction(function () use ($request, $kandidatBem, $kandidatHima, $user) {
            
            // SECURITY FIX: Double Vote Check inside Transaction Lock
            // We check the database directly in this transaction to see if they already voted
            $alreadyVotedBem = $kandidatBem ? DB::table('surat_suara')
                ->where('nim', $user->nim)
                ->where('id_kegiatan', $kandidatBem->id_kegiatan)
                ->where('has_vote', true)
                ->exists() : false;

            $alreadyVotedHima = $kandidatHima ? DB::table('surat_suara')
                ->where('nim', $user->nim)
                ->where('id_kegiatan', $kandidatHima->id_kegiatan)
                ->where('has_vote', true)
                ->exists() : false;

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
            $syncData = [];
            if ($kandidatBem) {
                $syncData[$kandidatBem->id_kegiatan] = ['has_vote' => true, 'ttd' => $ttdPath];
            }
            if ($kandidatHima) {
                $syncData[$kandidatHima->id_kegiatan] = ['has_vote' => true, 'ttd' => $ttdPath];
            }
            
            if (!empty($syncData)) {
                $user->kegiatan()->syncWithoutDetaching($syncData);
            }

            // Increment candidate votes
            if ($kandidatBem) {
                $kandidatBem->increment('jumlah_suara');
            }
            if ($kandidatHima) {
                $kandidatHima->increment('jumlah_suara');
            }

            return Inertia::render('ThankYou');
        });
    }
}
