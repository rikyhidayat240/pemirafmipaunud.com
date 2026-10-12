<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop foreign keys referencing nim
        Schema::table('mahasiswa_kandidat', function (Blueprint $table) {
            $table->dropForeign('mahasiswa_kandidat_nim_foreign');
        });
        Schema::table('surat_suara', function (Blueprint $table) {
            $table->dropForeign('surat_suara_nim_foreign');
        });

        // 2. Modify nim column length to VARCHAR(15)
        DB::statement('ALTER TABLE `mahasiswa` MODIFY `nim` VARCHAR(15) NOT NULL;');
        DB::statement('ALTER TABLE `mahasiswa_kandidat` MODIFY `nim` VARCHAR(15) NOT NULL;');
        DB::statement('ALTER TABLE `surat_suara` MODIFY `nim` VARCHAR(15) NOT NULL;');

        // 3. Re-add foreign keys
        Schema::table('mahasiswa_kandidat', function (Blueprint $table) {
            $table->foreign('nim')->references('nim')->on('mahasiswa')->onUpdate('cascade');
        });
        Schema::table('surat_suara', function (Blueprint $table) {
            $table->foreign('nim')->references('nim')->on('mahasiswa')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahasiswa_kandidat', function (Blueprint $table) {
            $table->dropForeign('mahasiswa_kandidat_nim_foreign');
        });
        Schema::table('surat_suara', function (Blueprint $table) {
            $table->dropForeign('surat_suara_nim_foreign');
        });

        DB::statement('ALTER TABLE `mahasiswa` MODIFY `nim` CHAR(10) NOT NULL;');
        DB::statement('ALTER TABLE `mahasiswa_kandidat` MODIFY `nim` CHAR(10) NOT NULL;');
        DB::statement('ALTER TABLE `surat_suara` MODIFY `nim` CHAR(10) NOT NULL;');

        Schema::table('mahasiswa_kandidat', function (Blueprint $table) {
            $table->foreign('nim')->references('nim')->on('mahasiswa')->onUpdate('cascade');
        });
        Schema::table('surat_suara', function (Blueprint $table) {
            $table->foreign('nim')->references('nim')->on('mahasiswa')->onDelete('cascade')->onUpdate('cascade');
        });
    }
};
