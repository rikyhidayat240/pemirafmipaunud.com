<?php

namespace App\Console\Commands;

use App\Mail\MahasiswaCredentials;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendMahasiswaCredentials extends Command
{
    protected $signature = 'mahasiswa:send-credentials
                            {--limit=0 : Maximum emails to send in this run (0 = semua sekaligus tanpa batas)}
                            {--prodi= : Filter by program studi ID}
                            {--angkatan= : Filter by angkatan year}
                            {--resend : Resend even if already sent before}
                            {--nim= : Send to a specific NIM only}';

    protected $description = 'Send login credentials to students who have not received them yet';

    public function handle(): int
    {
        set_time_limit(0);

        $limit    = (int) $this->option('limit');
        $prodi    = $this->option('prodi');
        $angkatan = $this->option('angkatan');
        $resend   = $this->option('resend');
        $nimOnly  = $this->option('nim');

        $query = User::where('is_admin', false)
            ->whereNotNull('email')
            ->where('email', '!=', '');

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

        $lock = Cache::lock('pemira_bulk_email_lock', 3600);
        if (! $lock->get()) {
            $this->warn('Pengiriman email massal lain sedang berlangsung (lock aktif). Silakan tunggu hingga selesai.');
            return self::FAILURE;
        }

        try {
            $throttleDelayMs = (int) config('services.ses.throttle_delay_ms', 100);
            $maxRetries      = (int) config('services.ses.max_retries', 3);

            $this->info("Total yang akan diproses: {$total}");
            if ($limit > 0) {
                $this->info("Limit pengiriman saat ini: {$limit} email");
                $query->limit($limit);
            } else {
                $this->info('Mode pengiriman: Semua sekaligus (tanpa limit)');
            }

            $mahasiswaList = $query->get();
            $sent   = 0;
            $failed = 0;

            $bar = $this->output->createProgressBar($mahasiswaList->count());
            $bar->start();

            foreach ($mahasiswaList as $index => $mahasiswa) {
                if ($index > 0 && $throttleDelayMs > 0) {
                    usleep($throttleDelayMs * 1000);
                }

                if ($this->sendCredentialWithRetry($mahasiswa, $maxRetries)) {
                    $sent++;
                } else {
                    $failed++;
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
                $this->warn("Jalankan perintah ini lagi untuk mengirim sisa mahasiswa yang belum terkirim:");
                $this->line('   php artisan mahasiswa:send-credentials');
            }

            return self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Send credential email to a student with exponential backoff retry for throttling errors.
     */
    public function sendCredentialWithRetry(User $mahasiswa, int $maxRetries = 3): bool
    {
        $attempt = 0;
        $backoffBaseMs = (int) config('services.ses.backoff_base_ms', app()->environment('testing') ? 1 : 1000);

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                $plainPassword = $this->generatePassword();

                Mail::to($mahasiswa->email)->send(new MahasiswaCredentials($mahasiswa, $plainPassword));

                $mahasiswa->update([
                    'password'            => Hash::make($plainPassword),
                    'email_verified_at'   => $mahasiswa->email_verified_at ?? now(),
                    'credentials_sent_at' => now(),
                ]);

                return true;
            } catch (\Throwable $e) {
                if ($this->isThrottlingException($e) && $attempt < $maxRetries) {
                    $sleepMs = $backoffBaseMs * (int) pow(2, $attempt - 1);
                    $this->newLine();
                    $this->warn("Throttling terdeteksi untuk {$mahasiswa->nim} (percobaan {$attempt}/{$maxRetries}). Retrying in {$sleepMs}ms: " . $e->getMessage());
                    usleep($sleepMs * 1000);
                    continue;
                }

                $this->newLine();
                $this->error("Gagal kirim ke {$mahasiswa->nim} ({$mahasiswa->email}): " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Determine if an exception represents an email rate limit / throttling error.
     * Matches only temporary rate-based errors (e.g. 454 Maximum sending rate exceeded, 429 Too Many Requests).
     * Excludes non-transient quota limits such as Daily message quota exceeded.
     */
    public function isThrottlingException(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        // Do not retry on daily quota exhaustion
        if (str_contains($message, 'daily') || str_contains($message, 'quota')) {
            return false;
        }

        return str_contains($message, 'maximum sending rate exceeded')
            || str_contains($message, 'rate exceeded')
            || str_contains($message, 'too many requests')
            || str_contains($message, '429')
            || (str_contains($message, '454') && (str_contains($message, 'throttl') || str_contains($message, 'rate')))
            || str_contains($message, 'throttling');
    }

    private function generatePassword(): string
    {
        return Str::password(12, letters: true, numbers: true, symbols: false, spaces: false);
    }
}
