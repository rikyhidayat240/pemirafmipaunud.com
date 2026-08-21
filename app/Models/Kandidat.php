<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kandidat extends Model
{
    /** @use HasFactory<\Database\Factories\KandidatFactory> */
    use HasFactory;

    protected $table = 'kandidat';

    protected $fillable = [
        'id_kegiatan',
        'no_urut',
        'foto',
        'visi',
        'misi',
        'jumlah_suara',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan');
    }

    public function mahasiswa()
    {
        return $this->belongsToMany(User::class, 'mahasiswa_kandidat', 'id_kandidat', 'nim', 'id', 'nim')
            ->withPivot('jabatan')
            ->withTimestamps();
    }
}
