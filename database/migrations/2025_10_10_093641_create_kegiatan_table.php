<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_program_studi')->nullable()->constrained('program_studi')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nama');
            $table->year('tahun');
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->enum('ruang_lingkup', ['fakultas', 'program studi']);
            $table->string('foto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
