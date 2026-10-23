<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PhpOffice\PhpSpreadsheet\IOFactory;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'mahasiswa';

    protected $primaryKey = 'nim';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nim',
        'id_program_studi',
        'nama',
        'angkatan',
        'email',
        'password',
        'avatar',
        'email_verified_at',
        'credentials_sent_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'credentials_sent_at'  => 'datetime',
            'password'             => 'hashed',
        ];
    }

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'id_program_studi', 'id');
    }

    public function kandidat()
    {
        return $this->belongsToMany(Kandidat::class, 'mahasiswa_kandidat', 'nim', 'id_kandidat')
            ->withPivot('jabatan')
            ->withTimestamps();
    }

    public function kegiatan()
    {
        return $this->belongsToMany(Kegiatan::class, 'surat_suara', 'nim', 'id_kegiatan')
            ->using(SuratSuara::class)
            ->withPivot('has_vote')
            ->withPivot('ttd')
            ->withTimestamps();
    }

    public static function getMahasiswaFromSheet(int $tahun)
    {
        // Get spreadsheet file
        if (!file_exists(database_path('data-mahasiswa'))) {
            mkdir(database_path('data-mahasiswa'), 0755, true);
        }
        $spreadsheetPath = database_path('data-mahasiswa/data-mahasiswa-' . $tahun . '.xlsx');
        if (!file_exists($spreadsheetPath)) {
            $spreadsheetPath = database_path('data-mahasiswa/data-mahasiswa-2025.xlsx'); // Ganti sesuai kebutuhan
        }
        $spreadsheet = IOFactory::load($spreadsheetPath);

        // Get list program studi and set up an array to hold mahasiswa data
        $programStudi = ProgramStudi::all();
        $mahasiswaData = [];

        // Loop through each worksheet
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $data = $worksheet->toArray();
            foreach ($data as $index => $row) {
                // Skip header or non-student rows (ensure NIM is a valid numeric string of at least 8 digits)
                $nim = trim((string) ($row[1] ?? ''));
                if (str_ends_with($nim, '.0')) {
                    $nim = substr($nim, 0, -2);
                }

                if (!preg_match('/^\d{8,15}$/', $nim)) {
                    continue;
                }

                $nama = trim((string) ($row[2] ?? ''));

                // Get program studi ID
                $prodiName = trim(str_replace('Sarjana ', '', (string) ($row[4] ?? '')));
                $prodiId = $programStudi->firstWhere('nama', $prodiName)?->id;

                // Detect column format (format baru memuat kolom Jenjang 'S1' pada index 5)
                $col5 = trim((string) ($row[5] ?? ''));
                if (in_array(strtoupper($col5), ['S1', 'D3', 'PROFESI'])) {
                    $angkatan = !empty($row[6]) ? (int) $row[6] : (int) ('20' . substr($nim, 0, 2));
                    $statusRaw = strtolower(trim((string) ($row[7] ?? 'aktif')));
                    $status = str_contains($statusRaw, 'aktif') && !str_contains($statusRaw, 'non') ? 'aktif' : 'nonaktif';
                    $rawEmail = trim((string) ($row[8] ?? ''));
                } else {
                    $statusRaw = strtolower($col5);
                    $status = str_contains($statusRaw, 'aktif') && !str_contains($statusRaw, 'non') ? 'aktif' : 'nonaktif';
                    $angkatan = !empty($row[6]) ? (int) $row[6] : (int) ('20' . substr($nim, 0, 2));
                    $rawEmail = trim((string) ($row[8] ?? ($row[7] ?? '')));
                }

                // Normalisasi & perbaikan anomali email
                $email = $rawEmail;
                if (str_contains(strtolower($email), '@gamil.com')) {
                    $email = str_ireplace('@gamil.com', '@gmail.com', $email);
                } elseif (str_ends_with(strtolower($email), '@gmail.co')) {
                    $email = substr($email, 0, -3) . '.com';
                }

                // Validasi format email, fallback ke NIM@student.unud.ac.id jika kosong / tidak valid
                if (empty($email) || in_array(strtolower($email), ['tidak ditemukan', '-', 'null', 'none']) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $email = $nim . '@student.unud.ac.id';
                }

                // Map row data to user attributes
                $mahasiswaData[] = [
                    'nim' => $nim,
                    'nama' => $nama,
                    'id_program_studi' => $prodiId,
                    'angkatan' => $angkatan,
                    'status' => $status,
                    'email' => $email,
                ];
            }
        }

        return $mahasiswaData;
    }

    public function isActive()
    {
        return $this->status === 'aktif';
    }
}
