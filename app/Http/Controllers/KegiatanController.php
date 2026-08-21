<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class KegiatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch kegiatan data with related program studi and count mahasiswa yang sudah vote
        $kegiatan = Kegiatan::with('programStudi')
            ->withCount([
                'mahasiswa as total_mahasiswa',
                'mahasiswa as jumlah_pemilih' => function ($query) {
                    $query->where('has_vote', true);
                }
            ])
            ->where('tahun', now()->year)
            ->get();
        $programStudi = ProgramStudi::all();
        return Inertia::render('kegiatan/Index', [
            'kegiatan' => $kegiatan,
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
            'nama' => 'required|string|max:255',
            'ruang_lingkup' => 'required|string|in:fakultas,program studi',
            'tahun' => 'required|integer|min:2000|max:2100',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after_or_equal:waktu_mulai',
            'id_program_studi' => 'nullable|exists:program_studi,id',
            'foto' => 'required|image|max:4096|mimes:jpeg,png,jpg,webp',
        ], [
            'nama.required' => 'Nama kegiatan wajib diisi.',
            'ruang_lingkup.in' => 'Ruang lingkup harus berupa fakultas atau program studi.',
            'waktu_selesai.after_or_equal' => 'Waktu selesai harus sama dengan atau setelah waktu mulai.',
            'id_program_studi.exists' => 'Program studi tidak valid.',
            'foto.required' => 'Foto kegiatan wajib diisi.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 4MB.',
            'foto.mimes' => 'Foto harus berupa file dengan ekstensi jpeg, png, jpg, atau webp.',
        ]);

        try {
            // Check ruang lingkup and set program studi accordingly
            if ($validatedData['ruang_lingkup'] === 'fakultas') {
                $validatedData['id_program_studi'] = null;
            } else if ($validatedData['ruang_lingkup'] === 'program studi' && empty($validatedData['id_program_studi'])) {
                return redirect()->back()->with('error', 'Program studi wajib diisi untuk ruang lingkup program studi.');
            }

            // Store foto if exists
            $fotoPath = null;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                // Get the uploaded file
                $fotoFile = $request->file('foto');
                $fotoName = time() . '_' . $fotoFile->getClientOriginalName();

                // Define the avatar destination path
                $fotoDestination = Storage::disk('public')->path('foto-kegiatan');
                if (!file_exists($fotoDestination)) {
                    mkdir($fotoDestination, 0755, true);
                }

                // Store the foto file
                Storage::disk('public')->putFileAs('foto-kegiatan', $fotoFile, $fotoName);
                $fotoPath = 'foto-kegiatan/' . $fotoName;
            }

            // Create new kegiatan record
            Kegiatan::create([
                'nama' => $validatedData['nama'],
                'ruang_lingkup' => $validatedData['ruang_lingkup'],
                'tahun' => $validatedData['tahun'],
                'waktu_mulai' => $validatedData['waktu_mulai'],
                'waktu_selesai' => $validatedData['waktu_selesai'],
                'id_program_studi' => $validatedData['id_program_studi'],
                'foto' => $fotoPath,
            ]);

            // Redirect with success message
            return redirect()->back()->with('success', 'Data kegiatan berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat data kegiatan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find the kegiatan by ID
        $kegiatan = Kegiatan::with(['kandidat.mahasiswa', 'programStudi'])
            ->withCount(['mahasiswa as total_mahasiswa'])
            ->findOrFail($id);

        if ($kegiatan->ruang_lingkup === 'fakultas') {
            // Get votes by program studi for fakultas level
            $votesByProdi = DB::table('program_studi')
                ->leftJoin('mahasiswa', 'program_studi.id', '=', 'mahasiswa.id_program_studi')
                ->leftJoin('surat_suara', function ($join) use ($kegiatan) {
                    $join->on('mahasiswa.nim', '=', 'surat_suara.nim')
                        ->where('surat_suara.id_kegiatan', '=', $kegiatan->id);
                })
                ->where('mahasiswa.is_admin', '!=', 1)
                ->select(
                    'program_studi.id as id_program_studi',
                    'program_studi.nama as nama_prodi',
                    DB::raw('COUNT(DISTINCT mahasiswa.nim) as total_mahasiswa'),
                    DB::raw('COUNT(DISTINCT CASE WHEN surat_suara.has_vote = 1 THEN surat_suara.nim END) as jumlah_pemilih'),
                    DB::raw('ROUND((COUNT(DISTINCT CASE WHEN surat_suara.has_vote = 1 THEN surat_suara.nim END) / COUNT(DISTINCT mahasiswa.nim)) * 100, 2) as persentase')
                )
                ->groupBy('program_studi.id', 'program_studi.nama')
                ->get();

            return Inertia::render('kegiatan/Result', [
                'kegiatan' => $kegiatan,
                'votesByProdi' => $votesByProdi,
            ]);
        } else {
            // Get votes by angkatan for program studi level
            $votesByAngkatan = DB::table('mahasiswa')
                ->leftJoin('surat_suara', function ($join) use ($kegiatan) {
                    $join->on('mahasiswa.nim', '=', 'surat_suara.nim')
                        ->where('surat_suara.id_kegiatan', '=', $kegiatan->id);
                })
                ->where('mahasiswa.id_program_studi', '=', $kegiatan->id_program_studi)
                ->where('mahasiswa.is_admin', '!=', 1)
                ->select(
                    'mahasiswa.angkatan',
                    DB::raw('COUNT(DISTINCT mahasiswa.nim) as total_mahasiswa'),
                    DB::raw('COUNT(DISTINCT CASE WHEN surat_suara.has_vote = 1 THEN surat_suara.nim END) as jumlah_pemilih'),
                    DB::raw('ROUND((COUNT(DISTINCT CASE WHEN surat_suara.has_vote = 1 THEN surat_suara.nim END) / COUNT(DISTINCT mahasiswa.nim)) * 100, 2) as persentase')
                )
                ->groupBy('mahasiswa.angkatan')
                ->orderBy('mahasiswa.angkatan', 'desc')
                ->get();

            return Inertia::render('kegiatan/Result', [
                'kegiatan' => $kegiatan,
                'votesByAngkatan' => $votesByAngkatan,
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate input data
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'ruang_lingkup' => 'required|string|in:fakultas,program studi',
            'tahun' => 'required|integer|min:2000|max:2100',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after_or_equal:waktu_mulai',
            'id_program_studi' => 'nullable|exists:program_studi,id',
            'foto' => 'nullable|image|max:4096|mimes:jpeg,png,jpg,webp',
        ], [
            'nama.required' => 'Nama kegiatan wajib diisi.',
            'ruang_lingkup.in' => 'Ruang lingkup harus berupa fakultas atau program studi.',
            'waktu_selesai.after_or_equal' => 'Waktu selesai harus sama dengan atau setelah waktu mulai.',
            'id_program_studi.exists' => 'Program studi tidak valid.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 4MB.',
            'foto.mimes' => 'Foto harus berupa file dengan ekstensi jpeg, png, jpg, atau webp.',
        ]);

        try {
            // Check ruang lingkup and set program studi accordingly
            if ($validatedData['ruang_lingkup'] === 'fakultas') {
                $validatedData['id_program_studi'] = null;
            } else if ($validatedData['ruang_lingkup'] === 'program studi' && empty($validatedData['id_program_studi'])) {
                return redirect()->back()->with('error', 'Program studi wajib diisi untuk ruang lingkup program studi.');
            }

            // Find the kegiatan to update
            $kegiatan = Kegiatan::findOrFail($id);

            // Handle foto upload
            $fotoPath = $kegiatan->foto;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                // Delete old foto if exists
                if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                    Storage::disk('public')->delete($fotoPath);
                }

                // Store new foto
                $fotoFile = $request->file('foto');
                $fotoName = time() . '_' . $fotoFile->getClientOriginalName();
                Storage::disk('public')->putFileAs('foto-kegiatan', $fotoFile, $fotoName);
                $fotoPath = 'foto-kegiatan/' . $fotoName;
            }

            // Update kegiatan
            $kegiatan->update([
                'nama' => $validatedData['nama'],
                'ruang_lingkup' => $validatedData['ruang_lingkup'],
                'tahun' => $validatedData['tahun'],
                'waktu_mulai' => $validatedData['waktu_mulai'],
                'waktu_selesai' => $validatedData['waktu_selesai'],
                'id_program_studi' => $validatedData['id_program_studi'],
                'foto' => $fotoPath,
            ]);

            // Redirect with success message
            return redirect()->back()->with('success', 'Data kegiatan berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data kegiatan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Find kegiatan
            $kegiatan = Kegiatan::findOrFail($id);

            // Delete foto if exists
            if ($kegiatan->foto && Storage::disk('public')->exists($kegiatan->foto)) {
                Storage::disk('public')->delete($kegiatan->foto);
            }

            // Delete kegiatan
            $kegiatan->delete();
            return redirect()->back()->with('success', 'Data kegiatan berhasil dihapus.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                return redirect()->back()->with('error', 'Data kegiatan tidak dapat dihapus karena terkait dengan data lain.');
            }
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data kegiatan: ' . $e->getMessage());
        }
    }

    /**
     * Print the attendance list for the specified resource.
     */
    public function print(string $id)
    {
        try {
            // Find kegiatan
            $kegiatan = Kegiatan::findOrFail($id);

            // Get mahasiswa list
            $mahasiswaList = $kegiatan->mahasiswa()
                ->orderBy('id_program_studi')
                ->orderBy('nim')
                ->where('has_vote', 1)
                ->get();

            // Convert images to base64
            $headerImagePath = public_path('images/header.png');
            $footerImagePath = public_path('images/footer.png');
            
            // Check if files exist
            if (!file_exists($headerImagePath)) {
                return redirect()->back()->with('error', 'File header tidak ditemukan.');
            }
            if (!file_exists($footerImagePath)) {
                return redirect()->back()->with('error', 'File footer tidak ditemukan.');
            }
            
            $headerImageData = base64_encode(file_get_contents($headerImagePath));
            $footerImageData = base64_encode(file_get_contents($footerImagePath));
            
            $headerImageSrc = 'data:image/png;base64,' . $headerImageData;
            $footerImageSrc = 'data:image/png;base64,' . $footerImageData;

            // Generate PDF using the presensi view
            $pdf = Pdf::loadView('layouts.presensi', [
                'kegiatan' => $kegiatan,
                'mahasiswaList' => $mahasiswaList,
                'headerImagePath' => $headerImageSrc,
                'footerImagePath' => $footerImageSrc,
            ])
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'Times New Roman',
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            // Return PDF directly for download
            $fileName = 'Presensi_' . str_replace(' ', '_', $kegiatan->nama) . '_' . date('Y-m-d') . '.pdf';
            return $pdf->download($fileName);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mencetak presensi mahasiswa: ' . $e->getMessage());
        }
    }
}
