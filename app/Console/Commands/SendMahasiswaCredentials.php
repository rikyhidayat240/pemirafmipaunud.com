<?php

namespace App\Console\Commands;

use App\Mail\MahasiswaCredentials;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendMahasiswaCredentials extends Command
{
    protected $signature = 'mahasiswa:send-credentials
                            {--limit=100 : Maximum emails to send in this run}
                            {--prodi= : Filter by program studi ID}
                            {--angkatan= : Filter by angkatan year}
                            {--resend : Resend even if already sent before}
                            {--nim= : Send to a specific NIM only}';

    protected $description = 'Send login credentials to students who have not received them yet (batched to avoid email limits)';

    public function handle(): int
    {
        $limit   = (int) $this->option('limit');
        $prodi   = $this->option('prodi');
        $angkatan = $this->option('angkatan');
        $resend  = $this->option('resend');
        $nimOnly = $this->option('nim');

        $query = User::where('is_admin', false)
            ->whereNotNull('email')
            ->whereNotNull('password');

        // Filter: specific NIM
        if ($nimOnly) {
            $query->where('nim', $nimOnly);
        } else {
            // Filter: only unsent (unless --resend)
            if (!$resend) {
                $query->whereNull('credentials_sent_at');
            }

            if ($prodi) {
                $query->where('id_program_studi', $prodi);
            }

            if ($angkatan) {
                $query->where('angkatan', $angkatan);
            }
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Tidak ada mahasiswa yang perlu dikirimi credentials.');
            return self::SUCCESS;
        }

        $this->info("Total yang akan diproses: {$total}");
        $this->info("Limit pengiriman saat ini: {$limit} email");

        $mahasiswaList = $query->limit($limit)->get();
        $sent  = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($mahasiswaList->count());
        $bar->start();

        foreach ($mahasiswaList as $mahasiswa) {
            try {
                // Generate new password if not already set (for resend, keep existing)
                $plainPassword = null;

                if (!$mahasiswa->password || $resend) {
                    $plainPassword = $this->generatePassword();
                    $mahasiswa->update(['password' => Hash::make($plainPassword)]);
                } else {
                    // We can't recover hashed password — generate a fresh one on resend
                    $plainPassword = $this->generatePassword();
                    $mahasiswa->update(['password' => Hash::make($plainPassword)]);
                }

                Mail::to($mahasiswa->email)->send(new MahasiswaCredentials($mahasiswa, $plainPassword));

                $mahasiswa->update(['credentials_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Gagal kirim ke {$mahasiswa->nim} ({$mahasiswa->email}): " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $remaining = max(0, $total - $sent);
        $this->info("✅ Berhasil terkirim : {$sent}");
        if ($failed > 0) {
            $this->warn("❌ Gagal             : {$failed}");
        }
        $this->info("📬 Sisa belum terkirim: {$remaining}");

        if ($remaining > 0 && !$nimOnly) {
            $this->newLine();
            $this->warn("Jalankan perintah ini lagi besok untuk mengirim batch berikutnya.");
            $this->line("   php artisan mahasiswa:send-credentials --limit={$limit}");
        }

        return self::SUCCESS;
    }

    private function generatePassword(): string
    {
        // 8-char password: 2 uppercase + 4 lowercase + 2 digit
        $upper  = strtoupper(Str::random(2));
        $lower  = strtolower(Str::random(4));
        $digits = rand(10, 99);
        // Shuffle for unpredictability
        $chars = str_split($upper . $lower . $digits);
        shuffle($chars);
        return implode('', $chars);
    }
}
