<?php

namespace App\Http\Controllers;

use App\Models\Kandidat;
use App\Models\Kegiatan;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BerandaController extends Controller
{
    private function getKegiatan()
    {
        $user = auth('web')->user();
        if ($user->is_admin) return Kegiatan::all();
        
        $kegiatan = Kegiatan::where('waktu_selesai', '>', now())
            ->where(function ($query) use ($user) {
                $query->where('ruang_lingkup', 'fakultas')
                      ->orWhere(function ($q) use ($user) {
                          $q->where('ruang_lingkup', 'program studi')
                            ->where('id_program_studi', $user->id_program_studi);
                      });
            })
            ->get();
            
        if ($kegiatan->isEmpty()) {
            $kegiatan = Kegiatan::where(function ($query) use ($user) {
                $query->where('ruang_lingkup', 'fakultas')
                      ->orWhere(function ($q) use ($user) {
                          $q->where('ruang_lingkup', 'program studi')
                            ->where('id_program_studi', $user->id_program_studi);
                      });
            })
            ->latest('waktu_selesai')
            ->limit(2)
            ->get();
        }
        
        return $kegiatan;
    }

    public function getTime()
    {
        // First, try to get active kegiatan (waktu_selesai > now)
        $activeKegiatan = Kegiatan::where('tahun', now()->year)
            ->where('waktu_selesai', '>', now())
            ->orderBy('waktu_mulai')
            ->first();

        if ($activeKegiatan) {
            return $activeKegiatan->waktu_mulai;
        }

        // If no active kegiatan, get the latest ended kegiatan
        $latestKegiatan = Kegiatan::latest('waktu_selesai')->first();

        return $latestKegiatan ? $latestKegiatan->waktu_mulai : now();
    }

    public function guest()
    {
        return Inertia::render('Dashboard', [
            'waktu' => $this->getTime(),
        ]);
    }

    public function index()
    {
        return Inertia::render('Dashboard', [
            'kegiatan' => $this->getKegiatan(),
            'waktu' => $this->getTime(),
        ]);
    }

    public function terms()
    {
        return Inertia::render('Terms', [
            'kegiatan' => $this->getKegiatan(),
            'waktu' => $this->getTime(),
        ]);
    }

    public function candidates(string $slug)
    {
        $kegiatan = Kegiatan::where('nama', str_replace('-', ' ', $slug))
            ->with('kandidat.mahasiswa')
            ->firstOrFail();
        $kandidat = Kandidat::where('id_kegiatan', $kegiatan->id)
            ->with('mahasiswa.programStudi')
            ->get();

        $idProdi = auth('web')->user()->id_program_studi ?? null;
        if ($idProdi !== $kegiatan->id_program_studi && $kegiatan->ruang_lingkup === 'program studi') {
            return redirect()->back();
        }

        return Inertia::render('Candidates', [
            'kegiatan' => $kegiatan,
            'kandidat' => $kandidat,
        ]);
    }

    public function resultBem()
    {
        $user = auth('web')->user();

        // Admin can view live results anytime
        if ($user && $user->is_admin) {
            $kegiatan = Kegiatan::where('tahun', now()->year)
                ->where('ruang_lingkup', 'fakultas')
                ->with(['kandidat', 'kandidat.mahasiswa.programStudi'])
                ->latest('waktu_selesai')
                ->first();

            if ($kegiatan) {
                $kegiatan->kandidat->each->makeVisible('jumlah_suara');
            }

            return Inertia::render('ResultBem', [
                'kegiatan' => $kegiatan,
            ]);
        }

        // Regular users can ONLY view ended kegiatan (waktu_selesai <= now)
        $kegiatan = Kegiatan::where('tahun', now()->year)
            ->where('ruang_lingkup', 'fakultas')
            ->where('waktu_selesai', '<=', now())
            ->with(['kandidat', 'kandidat.mahasiswa.programStudi'])
            ->latest('waktu_selesai')
            ->first();

        if (! $kegiatan) {
            return redirect()->route('dashboard')->with('alert', [
                'type' => 'info',
                'title' => 'Hasil Belum Tersedia',
                'message' => 'Hasil pemilihan BEM belum dapat dilihat karena pemilihan masih berlangsung atau belum ada kegiatan yang selesai.',
            ]);
        }

        // Reveal jumlah_suara only for ended event
        $kegiatan->kandidat->each->makeVisible('jumlah_suara');

        return Inertia::render('ResultBem', [
            'kegiatan' => $kegiatan,
        ]);
    }

    public function resultHima()
    {
        $user = auth('web')->user();

        if ($user && $user->is_admin) {
            $kegiatans = Kegiatan::where('tahun', now()->year)
                ->where('ruang_lingkup', 'program studi')
                ->with(['kandidat', 'kandidat.mahasiswa.programStudi'])
                ->get();

            $kegiatans->each(function ($k) {
                $k->kandidat->each->makeVisible('jumlah_suara');
            });

            return Inertia::render('ResultHima', [
                'kegiatans' => $kegiatans,
            ]);
        }

        // Regular users can ONLY view ended kegiatan
        $kegiatans = Kegiatan::where('tahun', now()->year)
            ->where('ruang_lingkup', 'program studi')
            ->where('waktu_selesai', '<=', now())
            ->with(['kandidat', 'kandidat.mahasiswa.programStudi'])
            ->get();

        if ($kegiatans->isEmpty()) {
            return redirect()->route('dashboard')->with('alert', [
                'type' => 'info',
                'title' => 'Hasil Belum Tersedia',
                'message' => 'Hasil pemilihan HIMA belum dapat dilihat karena pemilihan masih berlangsung atau belum ada kegiatan yang selesai.',
            ]);
        }

        $kegiatans->each(function ($k) {
            $k->kandidat->each->makeVisible('jumlah_suara');
        });

        return Inertia::render('ResultHima', [
            'kegiatans' => $kegiatans,
        ]);
    }
}
