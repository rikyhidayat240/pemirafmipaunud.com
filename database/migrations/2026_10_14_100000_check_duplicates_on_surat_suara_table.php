<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check for existing duplicates on surat_suara
        $duplicates = DB::table('surat_suara')
            ->select('nim', 'id_kegiatan', DB::raw('COUNT(*) as total'))
            ->groupBy('nim', 'id_kegiatan')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $details = $duplicates->map(fn ($d) => "NIM: {$d->nim}, id_kegiatan: {$d->id_kegiatan} ({$d->total} baris)")->implode('; ');
            throw new \RuntimeException(
                "Migration gagal: Ditemukan duplikasi data pada tabel surat_suara ({$details}). " .
                "Harap bersihkan data duplikat secara manual dengan query database sebelum melanjutkan."
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
