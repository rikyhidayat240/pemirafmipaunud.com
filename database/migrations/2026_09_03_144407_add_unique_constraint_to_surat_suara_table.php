<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SEC-03: Prevent double-vote at database level via unique constraint.
     * PERF-03: Add indexes for frequently queried columns.
     */
    public function up(): void
    {
        Schema::table('surat_suara', function (Blueprint $table) {
            // Prevent duplicate entries at DB level (race condition protection)
            $table->unique(['nim', 'id_kegiatan'], 'surat_suara_nim_kegiatan_unique');

            // Performance indexes for common WHERE clauses
            $table->index(['id_kegiatan', 'has_vote'], 'surat_suara_kegiatan_has_vote_idx');
        });

        Schema::table('kegiatan', function (Blueprint $table) {
            // Index tahun for fast filtering
            $table->index('tahun', 'kegiatan_tahun_idx');
            $table->index(['ruang_lingkup', 'tahun'], 'kegiatan_ruang_lingkup_tahun_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_suara', function (Blueprint $table) {
            $table->dropUnique('surat_suara_nim_kegiatan_unique');
            $table->dropIndex('surat_suara_kegiatan_has_vote_idx');
        });

        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropIndex('kegiatan_tahun_idx');
            $table->dropIndex('kegiatan_ruang_lingkup_tahun_idx');
        });
    }
};

