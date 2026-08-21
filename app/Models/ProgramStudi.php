<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramStudiFactory> */
    use HasFactory;

    protected $table = 'program_studi';

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function mahasiswa()
    {
        return $this->hasMany(User::class, 'id_program_studi');
    }

    public function kegiatan()
    {
        return $this->hasMany(Kegiatan::class, 'id_program_studi');
    }
}
