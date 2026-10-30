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
        $selectCols = [
            'id',
            'nama',
            'tahun',
            'ruang_lingkup',
            'id_program_studi',
            'waktu_mulai',
            'waktu_selesai',
            'foto',
        ];

        if ($user->is_admin) {
            return Kegiatan::query()
                ->select($selectCols)
                ->orderBy('tahun', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        $baseQuery = Kegiatan::query()
            ->select($selectCols)
            ->where(function ($query) use ($user) {
                $query->where('ruang_lingkup', 'fakultas')
                      ->orWhere(function ($q) use ($user) {
                          $q->where('ruang_lingkup', 'program studi')
                            ->where('id_program_studi', $user->id_program_studi);
                      });
            });

        $deduplicateByScope = fn ($item) => $item->ruang_lingkup === 'fakultas'
            ? 'fakultas'
            : 'prodi_' . $item->id_program_studi;

        $kegiatan = (clone $baseQuery)
            ->where('waktu_selesai', '>', now())
            ->orderByDesc('waktu_mulai')
            ->orderByDesc('id')
            ->get()
            ->unique($deduplicateByScope)
            ->values();
            
        if ($kegiatan->isEmpty()) {
            $kegiatan = (clone $baseQuery)
                ->orderByDesc('waktu_selesai')
                ->orderByDesc('id')
                ->get()
                ->unique($deduplicateByScope)
                ->values();
        }
        
        return $kegiatan;
    }

    public function getTime()
    {
        // First, try to get active kegiatan (waktu_selesai > now)
        $activeKegiatan = Kegiatan::query()
            ->select(['id', 'waktu_mulai', 'waktu_selesai', 'tahun'])
            ->where('tahun', now()->year)
            ->where('waktu_selesai', '>', now())
            ->orderBy('waktu_mulai', 'asc')
            ->first();

        if ($activeKegiatan) {
            return $activeKegiatan->waktu_mulai;
        }

        // If no active kegiatan, get the latest ended kegiatan
        $latestKegiatan = Kegiatan::query()
            ->select(['id', 'waktu_mulai', 'waktu_selesai', 'tahun'])
            ->latest('waktu_selesai')
            ->first();

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
            ->orderByDesc('tahun')
            ->orderByDesc('waktu_selesai')
            ->orderByDesc('id')
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

        if (! $user || ! $user->is_admin) {
            return redirect()->route('dashboard')->with('alert', [
                'type' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Anda tidak memiliki akses ke halaman ini.',
            ]);
        }

        $kegiatan = Kegiatan::where('tahun', now()->year)
            ->where('ruang_lingkup', 'fakultas')
            ->with(['kandidat', 'kandidat.mahasiswa.programStudi'])
            ->orderByDesc('waktu_selesai')
            ->orderByDesc('id')
            ->first();

        if ($kegiatan) {
            $kegiatan->kandidat->each->makeVisible('jumlah_suara');
        }

        return Inertia::render('ResultBem', [
            'kegiatan' => $kegiatan,
        ]);
    }

    public function resultHima()
    {
        $user = auth('web')->user();

        if (! $user || ! $user->is_admin) {
            return redirect()->route('dashboard')->with('alert', [
                'type' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Anda tidak memiliki akses ke halaman ini.',
            ]);
        }

        $kegiatans = Kegiatan::where('tahun', now()->year)
            ->where('ruang_lingkup', 'program studi')
            ->with(['kandidat', 'kandidat.mahasiswa.programStudi'])
            ->orderByDesc('waktu_selesai')
            ->orderByDesc('id')
            ->get()
            ->unique('id_program_studi')
            ->sortBy('id_program_studi')
            ->values();

        $kegiatans->each(function ($k) {
            $k->kandidat->each->makeVisible('jumlah_suara');
        });

        return Inertia::render('ResultHima', [
            'kegiatans' => $kegiatans,
        ]);
    }
}
