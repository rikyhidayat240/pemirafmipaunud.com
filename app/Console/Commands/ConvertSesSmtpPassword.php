<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ConvertSesSmtpPassword extends Command
{
    protected $signature = 'ses:convert-password
                            {--key= : The AWS Access Key ID (or fallback to config services.ses.key)}
                            {--region=ap-southeast-2 : The AWS SES region (default: ap-southeast-2)}
                            {--test= : Send a test email to the specified address using current .env config}';

    protected $description = 'Convert AWS Secret Access Key to SES SMTP Password (SigV4) or send a test email';

    public function handle(): int
    {
        $testEmail = $this->option('test');
        if ($testEmail) {
            $this->info("Mengirim email uji coba ke {$testEmail} ...");
            try {
                Mail::raw(
                    'Halo! Ini adalah email uji coba resmi dari sistem E-Voting PEMIRA FMIPA UNUD melalui AWS SES.',
                    function ($message) use ($testEmail) {
                        $message->to($testEmail)
                            ->subject('Uji Coba AWS SES PEMIRA FMIPA UNUD - Berhasil');
                    }
                );
                $this->info("✅ Email uji coba BERHASIL dikirim ke {$testEmail}! Silakan cek Inbox/Spam Anda.");
                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->error('❌ Gagal mengirim email: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        $region = trim((string) $this->option('region')) ?: 'ap-southeast-2';
        $accessKey = $this->option('key') ?: config('services.ses.key');

        $secret = $this->secret('Masukkan AWS Secret Access Key Anda');

        if (empty($secret)) {
            $this->error('Secret Access Key tidak boleh kosong.');
            return self::FAILURE;
        }

        $secret = trim($secret);
        $smtpPassword = self::calculateSmtpPassword($secret, $region);

        $this->newLine();
        $this->info('====================================================');
        $this->info("✅ Berhasil Dikonversi ke AWS SES SMTP Password (SigV4 - {$region})!");
        $this->info('====================================================');
        if (!empty($accessKey)) {
            $this->line("SMTP Username : <comment>{$accessKey}</comment>");
        } else {
            $this->line("SMTP Username : <comment>(Gunakan AWS Access Key ID Anda / MAIL_USERNAME di .env)</comment>");
        }
        $this->line("SMTP Password : <comment>{$smtpPassword}</comment>");
        $this->info('====================================================');
        $this->newLine();
        $this->line('Salin SMTP Password baru di atas dan tempelkan ke MAIL_PASSWORD di file .env Anda.');

        return self::SUCCESS;
    }

    /**
     * Derive AWS SES SMTP password using Signature Version 4 (SigV4).
     */
    public static function calculateSmtpPassword(string $secret, string $region = 'ap-southeast-2'): string
    {
        $date = '11111111';
        $service = 'ses';
        $terminal = 'aws4_request';
        $message = 'SendRawEmail';
        $version = chr(0x04);

        $signature = hash_hmac('sha256', $date, 'AWS4' . $secret, true);
        $signature = hash_hmac('sha256', $region, $signature, true);
        $signature = hash_hmac('sha256', $service, $signature, true);
        $signature = hash_hmac('sha256', $terminal, $signature, true);
        $signature = hash_hmac('sha256', $message, $signature, true);

        return base64_encode($version . $signature);
    }
}

