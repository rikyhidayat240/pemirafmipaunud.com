<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PatchEnvSec extends Command
{
    protected $signature = 'app:patch-env-sec';
    protected $description = 'Patch .env with production security settings';

    public function handle()
    {
        $path = base_path('.env');

        if (!file_exists($path)) {
            $this->error('.env not found!');
            return 1;
        }

        $content = file_get_contents($path);

        $replacements = [
            'APP_ENV=local'               => 'APP_ENV=production',
            'APP_DEBUG=true'              => 'APP_DEBUG=false',
            'LOG_LEVEL=debug'             => 'LOG_LEVEL=warning',
            'SESSION_ENCRYPT=false'       => 'SESSION_ENCRYPT=true',
            'SESSION_SECURE_COOKIE=false' => 'SESSION_SECURE_COOKIE=true',
            'SESSION_SAME_SITE=lax'       => 'SESSION_SAME_SITE=strict',
        ];

        foreach ($replacements as $from => $to) {
            // Handle both LF and CRLF line endings
            $content = str_replace($from . "\r\n", $to . "\r\n", $content);
            $content = str_replace($from . "\n",   $to . "\n",   $content);
            // Also handle values at end of file (no trailing newline)
            $content = str_replace($from, $to, $content);
        }

        // Add SESSION_SECURE_COOKIE if missing
        if (strpos($content, 'SESSION_SECURE_COOKIE') === false) {
            $content = str_replace(
                'SESSION_DOMAIN=null',
                "SESSION_DOMAIN=null\nSESSION_SECURE_COOKIE=true\nSESSION_SAME_SITE=strict\nSESSION_HTTP_ONLY=true",
                $content
            );
        }

        file_put_contents($path, $content);
        $this->info('Production .env security settings patched successfully!');

        // Show result
        $this->line('SESSION_ENCRYPT  : ' . env('SESSION_ENCRYPT', 'not set'));
        $this->line('APP_DEBUG        : ' . (env('APP_DEBUG') ? 'true' : 'false'));

        return 0;
    }
}
