<?php

namespace App\Http\Controllers;

use App\Models\Kandidat;
use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KandidatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch kandidat data with related kegiatan and mahasiswa kandidat
        $kandidat = Kandidat::with('kegiatan', 'mahasiswa')
            ->whereHas('kegiatan', function ($query) {
                $query->where('tahun', now()->year);
            })
            ->orderBy('id_kegiatan', 'asc')
            ->get();
        $kegiatan = Kegiatan::where('tahun', now()->year)
            ->withCount(['mahasiswa as total_mahasiswa'])
            ->get();
        $mahasiswa = User::where('is_admin', 0)
            ->orWhere('nama', 'LIKE', '%Kotak Kosong%')
            ->get();
        return Inertia::render('kandidat/Index', [
            'kandidat' => $kandidat,
            'mahasiswa' => $mahasiswa,
            'kegiatan' => $kegiatan,
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
            'id_kegiatan' => 'required|exists:kegiatan,id',
            'no_urut' => 'required|string|digits:2',
            'visi' => 'required|string',
            'misi' => 'required|string',
            'foto' => 'required|image|max:4096|mimes:jpeg,png,jpg,webp',
            'mahasiswa' => 'required|array|min:1',
            'mahasiswa.*.nim' => 'required|exists:mahasiswa,nim',
            'mahasiswa.*.jabatan' => 'required|in:ketua,wakil',
        ], [
            'id_kegiatan.required' => 'Kegiatan wajib dipilih.',
            'id_kegiatan.exists' => 'Kegiatan tidak valid.',
            'no_urut.required' => 'Nomor urut wajib diisi.',
            'no_urut.digits' => 'Nomor urut harus terdiri dari 2 digit.',
            'visi.required' => 'Visi wajib diisi.',
            'misi.required' => 'Misi wajib diisi.',
            'foto.required' => 'Foto kandidat wajib diisi.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 4MB.',
            'foto.mimes' => 'Foto harus berupa file dengan ekstensi jpeg, png, jpg, atau webp.',
            'mahasiswa.required' => 'Mahasiswa kandidat wajib dipilih.',
            'mahasiswa.*.nim.exists' => 'NIM mahasiswa tidak valid.',
            'mahasiswa.*.jabatan.in' => 'Jabatan harus ketua atau wakil.',
        ]);

        try {
            // Check if no_urut already exists for this kegiatan
            $existingKandidat = Kandidat::where('id_kegiatan', $validatedData['id_kegiatan'])
                ->where('no_urut', $validatedData['no_urut'])
                ->first();
            
            if ($existingKandidat) {
                return redirect()->back()->with('error', 'Nomor urut sudah digunakan untuk kegiatan ini.');
            }

            // Store foto if exists
            $fotoPath = null;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                // Get the uploaded file
                $fotoFile = $request->file('foto');
                $fotoName = time() . '_' . $fotoFile->getClientOriginalName();

                // Define the foto destination path
                $fotoDestination = Storage::disk('public')->path('foto-kandidat');
                if (!file_exists($fotoDestination)) {
                    mkdir($fotoDestination, 0755, true);
                }

                // Store the foto file
                Storage::disk('public')->putFileAs('foto-kandidat', $fotoFile, $fotoName);
                $fotoPath = 'foto-kandidat/' . $fotoName;
            }

            // Create new kandidat record
            $kandidat = Kandidat::create([
                'id_kegiatan' => $validatedData['id_kegiatan'],
                'no_urut' => $validatedData['no_urut'],
                'visi' => $validatedData['visi'],
                'misi' => $validatedData['misi'],
                'foto' => $fotoPath,
                'jumlah_suara' => 0,
            ]);

            // Attach mahasiswa to kandidat with jabatan
            foreach ($validatedData['mahasiswa'] as $mahasiswa) {
                $kandidat->mahasiswa()->attach($mahasiswa['nim'], [
                    'jabatan' => $mahasiswa['jabatan'],
                    'created_at' => now()->toDateTime(),
                    'updated_at' => now()->toDateTime(),
                ]);
            }

            // Redirect with success message
            return redirect()->back()->with('success', 'Data kandidat berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat data kandidat: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate input data
        $validatedData = $request->validate([
            'id_kegiatan' => 'required|exists:kegiatan,id',
            'no_urut' => 'required|string|digits:2',
            'visi' => 'required|string',
            'misi' => 'required|string',
            'foto' => 'nullable|image|max:4096|mimes:jpeg,png,jpg,webp',
            'mahasiswa' => 'required|array|min:1',
            'mahasiswa.*.nim' => 'required|exists:mahasiswa,nim',
            'mahasiswa.*.jabatan' => 'required|in:ketua,wakil',
        ], [
            'id_kegiatan.required' => 'Kegiatan wajib dipilih.',
            'id_kegiatan.exists' => 'Kegiatan tidak valid.',
            'no_urut.required' => 'Nomor urut wajib diisi.',
            'no_urut.digits' => 'Nomor urut harus terdiri dari 2 digit.',
            'visi.required' => 'Visi wajib diisi.',
            'misi.required' => 'Misi wajib diisi.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 4MB.',
            'foto.mimes' => 'Foto harus berupa file dengan ekstensi jpeg, png, jpg, atau webp.',
            'mahasiswa.required' => 'Mahasiswa kandidat wajib dipilih.',
            'mahasiswa.*.nim.exists' => 'NIM mahasiswa tidak valid.',
            'mahasiswa.*.jabatan.in' => 'Jabatan harus ketua atau wakil.',
        ]);

        try {
            // Find the kandidat to update
            $kandidat = Kandidat::findOrFail($id);

            // Check if no_urut already exists for this kegiatan (excluding current kandidat)
            $existingKandidat = Kandidat::where('id_kegiatan', $validatedData['id_kegiatan'])
                ->where('no_urut', $validatedData['no_urut'])
                ->where('id', '!=', $id)
                ->first();
            
            if ($existingKandidat) {
                return redirect()->back()->with('error', 'Nomor urut sudah digunakan untuk kegiatan ini.');
            }

            // Handle foto upload
            $fotoPath = $kandidat->foto;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                // Delete old foto if exists
                if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                    Storage::disk('public')->delete($fotoPath);
                }

                // Store new foto
                $fotoFile = $request->file('foto');
                $fotoName = time() . '_' . $fotoFile->getClientOriginalName();
                Storage::disk('public')->putFileAs('foto-kandidat', $fotoFile, $fotoName);
                $fotoPath = 'foto-kandidat/' . $fotoName;
            }

            // Update kandidat
            $kandidat->update([
                'id_kegiatan' => $validatedData['id_kegiatan'],
                'no_urut' => $validatedData['no_urut'],
                'visi' => $validatedData['visi'],
                'misi' => $validatedData['misi'],
                'foto' => $fotoPath,
            ]);

            // Sync mahasiswa to kandidat with jabatan
            $mahasiswaSync = [];
            foreach ($validatedData['mahasiswa'] as $mahasiswa) {
                $mahasiswaSync[$mahasiswa['nim']] = [
                    'jabatan' => $mahasiswa['jabatan'],
                    'created_at' => now()->toDateTime(),
                    'updated_at' => now()->toDateTime(),
                ];
            }
            $kandidat->mahasiswa()->sync($mahasiswaSync);

            // Redirect with success message
            return redirect()->back()->with('success', 'Data kandidat berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data kandidat: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Find kandidat
            $kandidat = Kandidat::findOrFail($id);

            // Detach all mahasiswa relationships first
            $kandidat->mahasiswa()->detach();

            // Delete foto if exists
            if ($kandidat->foto && Storage::disk('public')->exists($kandidat->foto)) {
                Storage::disk('public')->delete($kandidat->foto);
            }

            // Delete kandidat
            $kandidat->delete();
            
            return redirect()->back()->with('success', 'Data kandidat berhasil dihapus.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                return redirect()->back()->with('error', 'Data kandidat tidak dapat dihapus karena terkait dengan data lain.');
            }
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data kandidat: ' . $e->getMessage());
        }
    }
}
