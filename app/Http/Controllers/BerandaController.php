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
        $user = User::where('nim', auth('web')->user()->nim)->first();
        if ($user->is_admin) return Kegiatan::all();
        $kegiatan = $user->kegiatan()
            ->where('waktu_selesai', '>', now())
            ->get();
        if (!$kegiatan) {
            $kegiatan = $user->kegiatan()
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
        return Inertia::render('ResultBem');
    }

    public function resultHima()
    {
        return Inertia::render('ResultHima');
    }
}
