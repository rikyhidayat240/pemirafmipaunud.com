<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('mahasiswa', 'credentials_sent_at')) {
            Schema::table('mahasiswa', function (Blueprint $table) {
                $table->timestamp('credentials_sent_at')->nullable()->after('email_verified_at')
                    ->comment('Timestamp when login credentials were emailed to the student');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mahasiswa', 'credentials_sent_at')) {
            Schema::table('mahasiswa', function (Blueprint $table) {
                $table->dropColumn('credentials_sent_at');
            });
        }
    }
};
