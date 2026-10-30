<?php

namespace App\Http\Controllers;

use App\Models\Kandidat;
use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $user = auth('web')->user();
        // Eager-load kegiatan for vote status checking
        $user->load('kegiatan');

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
            ->orderByDesc('waktu_mulai')
            ->orderByDesc('id')
            ->first();

        // Fetch kegiatan HIMA (program studi level) without cache
        $kegiatanHima = Kegiatan::where('tahun', date('Y'))
            ->where('waktu_selesai', '>', now())
            ->where('ruang_lingkup', 'program studi')
            ->where('id_program_studi', $user->id_program_studi)
            ->with(['kandidat.mahasiswa', 'programStudi'])
            ->orderByDesc('waktu_mulai')
            ->orderByDesc('id')
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

    public function store(\App\Http\Requests\StoreVoteRequest $request)
    {
        $kandidatBem = $request->id_kandidat_bem ? Kandidat::find($request->id_kandidat_bem) : null;
        $kandidatHima = $request->id_kandidat_hima ? Kandidat::find($request->id_kandidat_hima) : null;

        $user = User::where('nim', auth('web')->user()->nim)->first();

        try {
            // Wrap everything in a database transaction
            return DB::transaction(function () use ($request, $kandidatBem, $kandidatHima, $user) {
                // Kunci baris user untuk isolasi transaksi voting
                User::where('nim', $user->nim)->lockForUpdate()->first();

                // Double Vote Check inside Transaction Lock
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
                    Log::warning('[SECURITY] Double-vote attempt detected in Pemira', [
                        'nim'               => $user->nim,
                        'already_voted_bem' => $alreadyVotedBem,
                        'already_voted_hima'=> $alreadyVotedHima,
                        'ip'                => $request->ip(),
                        'user_agent'        => $request->userAgent(),
                    ]);
                    return redirect()->route('dashboard')
                        ->with('alert', ['type' => 'error', 'title' => 'Pemilihan Sudah Dilakukan', 'message' => 'Anda sudah memberikan suara! Suara ganda ditolak.']);
                }

                $ttdPath = null;
                if ($request->ttd) {
                    $image = $request->ttd;
                    $allowedTypes = ['png', 'jpeg', 'jpg'];
                    if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                        $image = substr($image, strpos($image, ',') + 1);
                        $detectedType = strtolower($type[1]);

                        if (!in_array($detectedType, $allowedTypes, true)) {
                            Log::warning('[SECURITY] Rejected invalid TTD image type', [
                                'nim'  => $user->nim,
                                'type' => $detectedType,
                                'ip'   => $request->ip(),
                            ]);
                            return back()->with('alert', ['type' => 'error', 'title' => 'Error', 'message' => 'Format tanda tangan tidak valid.']);
                        }
                        
                        $image = base64_decode($image);
                        if ($image === false) {
                            return back()->with('alert', ['type' => 'error', 'title' => 'Error', 'message' => 'Gagal memproses tanda tangan.']);
                        }
                        
                        $filename = $user->nim . '_' . Str::random(24) . '.' . $detectedType;
                        Storage::disk('public')->put('ttd-mahasiswa/' . $filename, $image);
                        $ttdPath = 'ttd-mahasiswa/' . $filename;
                    }
                }

                // Mark user as voted in pivot table with coarsened timestamp (rounded down to hour)
                $coarsenedTimestamp = now()->startOfHour();
                $syncData = [];
                if ($kandidatBem) {
                    $syncData[$kandidatBem->id_kegiatan] = [
                        'has_vote'   => true,
                        'ttd'        => $ttdPath,
                        'created_at' => $coarsenedTimestamp,
                        'updated_at' => $coarsenedTimestamp,
                    ];
                }
                if ($kandidatHima) {
                    $syncData[$kandidatHima->id_kegiatan] = [
                        'has_vote'   => true,
                        'ttd'        => $ttdPath,
                        'created_at' => $coarsenedTimestamp,
                        'updated_at' => $coarsenedTimestamp,
                    ];
                }
                
                if (!empty($syncData)) {
                    $user->kegiatan()->syncWithoutDetaching($syncData);
                }

                // Increment candidate votes atomically via query builder without touching updated_at
                if ($kandidatBem) {
                    DB::table('kandidat')->where('id', $kandidatBem->id)->increment('jumlah_suara');
                }
                if ($kandidatHima) {
                    DB::table('kandidat')->where('id', $kandidatHima->id)->increment('jumlah_suara');
                }

                Log::info('[VOTE] Vote cast successfully in Pemira', [
                    'nim' => $user->nim,
                    'ip'  => $request->ip(),
                ]);

                return Inertia::render('ThankYou');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            Log::warning('[SECURITY] Duplicate vote detected via unique constraint in Pemira', [
                'nim' => $user->nim,
                'ip'  => $request->ip(),
            ]);
            return redirect()->route('dashboard')
                ->with('alert', ['type' => 'error', 'title' => 'Pemilihan Sudah Dilakukan', 'message' => 'Anda sudah memberikan suara! Suara ganda ditolak.']);
        }
    }
}
