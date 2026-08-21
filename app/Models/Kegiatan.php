<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Kegiatan extends Model
{
    /** @use HasFactory<\Database\Factories\KegiatanFactory> */
    use HasFactory;

    protected $table = 'kegiatan';

    protected $fillable = [
        'nama',
        'tahun',
        'waktu_mulai',
        'waktu_selesai',
        'ruang_lingkup',
        'id_program_studi',
        'foto',
    ];

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'id_program_studi');
    }

    public function kandidat()
    {
        return $this->hasMany(Kandidat::class, 'id_kegiatan');
    }

    public function mahasiswa()
    {
        return $this->belongsToMany(User::class, 'surat_suara', 'id_kegiatan', 'nim')
            ->withPivot('has_vote')
            ->withPivot('ttd')
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }
}
