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
        'status',
        'is_admin',
        'email_verified_at',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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
                // Skip header row
                if ($index === 0) {
                    continue;
                }

                // Get program studi ID
                $row[4] = str_replace('Sarjana ', '', $row[4]);
                $prodiId = $programStudi->firstWhere('nama', $row[4])?->id;

                // Get status aktif/nonaktif
                $status = explode(' ', strtolower($row[5]))[0] === 'aktif' ? 'aktif' : 'nonaktif';

                // Map row data to user attributes
                $mahasiswaData[] = [
                    'nim' => $row[1],
                    'nama' => $row[2],
                    'id_program_studi' => $prodiId,
                    'angkatan' => (int) $row[6],
                    'status' => $status,
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
