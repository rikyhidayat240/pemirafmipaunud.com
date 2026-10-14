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
        Schema::table('surat_suara', function (Blueprint $table) {
            $table->dropForeign('surat_suara_nim_foreign');
            $table->foreign('nim')->references('nim')->on('mahasiswa')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_suara', function (Blueprint $table) {
            $table->dropForeign('surat_suara_nim_foreign');
            $table->foreign('nim')->references('nim')->on('mahasiswa')->onDelete('cascade')->onUpdate('cascade');
        });
    }
};
