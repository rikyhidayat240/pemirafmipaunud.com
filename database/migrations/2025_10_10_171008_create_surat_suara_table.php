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
        Schema::create('surat_suara', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kegiatan')->constrained('kegiatan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nim', 10);
            $table->foreign('nim')->references('nim')->on('mahasiswa')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('has_vote')->default(false);
            $table->string('ttd')->nullable(); // signature column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_suara');
    }
};
