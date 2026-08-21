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
        Schema::create('mahasiswa_kandidat', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 10);
            $table->foreign('nim')->references('nim')->on('mahasiswa')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_kandidat')->constrained('kandidat')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('jabatan', ['ketua', 'wakil']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_kandidat');
    }
};
