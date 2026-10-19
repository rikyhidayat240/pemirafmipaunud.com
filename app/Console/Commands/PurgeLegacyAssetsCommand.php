<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PurgeLegacyAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:purge-legacy-assets
        {--force : Actually delete verified obsolete legacy files from server disk (dry-run by default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge obsolete legacy files (>300 KB) flagged on Hostinger server disk';

    /**
     * Obsolete legacy files on disk that should be completely removed from public/.
     *
     * @var array<string, string>
     */
    protected array $legacyFiles = [
        'images/foto-slide-hero/hero-image-3.jpg'            => 'Legacy uncompressed hero slide (6.14 MB)',
        'images/foto-slide-hero/hero-image-2.jpg'            => 'Legacy uncompressed hero slide (3.61 MB)',
        'images/foto-slide-hero/hero-image-1.jpg'            => 'Legacy uncompressed hero slide (2.50 MB)',
        'images/foto-slide-hero/IMG_1.webp'                  => 'Legacy converted hero slide',
        'images/foto-slide-hero/IMG_2.webp'                  => 'Legacy converted hero slide',
        'images/foto-slide-hero/IMG_3.webp'                  => 'Legacy converted hero slide',
        'StarfieldCanvas.png'                                => 'Legacy uncompressed body background (2.90 MB, superseded by StarfieldCanvas.webp)',
        'images/background-logo-dpm.png'                     => 'Legacy uncompressed background logo (591 KB, superseded by background-logo-dpm.webp)',
        'images/foto-kegiatan/pemilihan-ketua-wakil-bem.png' => 'Legacy uncompressed event banner (781 KB, superseded by webp)',
        'images/foto-kegiatan/pemilihan-ketua-himafi.png'    => 'Legacy uncompressed event banner (631 KB, superseded by webp)',
        'images/foto-kegiatan/pemilihan-ketua-himafarma.png' => 'Legacy uncompressed event banner (521 KB, superseded by webp)',
        'images/foto-kegiatan/pemilihan-ketua-himabio.png'   => 'Legacy uncompressed event banner (496 KB, superseded by webp)',
        'images/foto-kegiatan/pemilihan-ketua-himaif.png'    => 'Legacy uncompressed event banner (493 KB, superseded by webp)',
        'images/foto-kegiatan/pemilihan-ketua-himatika.png'  => 'Legacy uncompressed event banner (474 KB, superseded by webp)',
        'images/foto-kegiatan/pemilihan-ketua-himaki.png'    => 'Legacy uncompressed event banner (superseded by webp)',
        'Logo pemira.webp'                                   => 'Duplicate 242 KB file with 0 references (active: /logo-pemira.webp 40 KB)',
        'group-188.png'                                      => 'Legacy unused graphic',
        'logo-pemira.svg'                                    => 'Legacy vector logo superseded by webp',
    ];

    /**
     * Active protected assets that must NEVER be deleted.
     *
     * @var array<string>
     */
    protected array $protectedAssets = [
        'StarfieldCanvas.webp',
        'logo-pemira.webp',
        'Logo pemira.png',
        'images/background-logo-dpm.webp',
        'images/foto-kegiatan/pemilihan-ketua-wakil-bem.webp',
        'images/foto-kegiatan/pemilihan-ketua-himabio.webp',
        'images/foto-kegiatan/pemilihan-ketua-himafarma.webp',
        'images/foto-kegiatan/pemilihan-ketua-himafi.webp',
        'images/foto-kegiatan/pemilihan-ketua-himaif.webp',
        'images/foto-kegiatan/pemilihan-ketua-himaki.webp',
        'images/foto-kegiatan/pemilihan-ketua-himatika.webp',
        'images/corner-image.png',
        'images/frame-bem.webp',
        'images/frame-hima.webp',
        'images/kotak-kosong.webp',
        'favicon.png',
        'favicon-32x32.png',
        'apple-touch-icon.png',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $publicDir = public_path();

        $this->info('====================================================');
        $this->info('  PEMIRA FMIPA — HOSTINGER SERVER ASSET PURGE');
        $this->info('====================================================');
        $this->info('Mode: ' . ($force ? 'FORCE PURGE (Unlinking legacy files)' : 'DRY RUN (Previewing files on disk)'));
        $this->line('');

        $totalBytes = 0;
        $foundCount = 0;
        $purgedCount = 0;

        $rows = [];

        foreach ($this->legacyFiles as $relPath => $desc) {
            $fullPath = $publicDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

            // Safety guard: Must reside within public_path() and never in storage/ or .env
            $realPublic = realpath($publicDir) ?: $publicDir;
            if (str_contains($relPath, '..') || str_starts_with($relPath, 'storage') || str_starts_with($relPath, '.env')) {
                $this->error("BLOCKED: {$relPath} violates safety policy.");
                continue;
            }

            if (file_exists($fullPath)) {
                $foundCount++;
                $size = filesize($fullPath);
                $totalBytes += $size;

                if ($force) {
                    if (@unlink($fullPath)) {
                        $purgedCount++;
                        $rows[] = [$relPath, $this->formatBytes($size), 'PURGED', $desc];
                    } else {
                        $rows[] = [$relPath, $this->formatBytes($size), 'FAILED TO DELETE', $desc];
                    }
                } else {
                    $rows[] = [$relPath, $this->formatBytes($size), 'FOUND (Dry Run)', $desc];
                }
            } else {
                $rows[] = [$relPath, '-', 'ALREADY GONE', $desc];
            }
        }

        $this->table(['File Path (public/)', 'Size', 'Status', 'Description'], $rows);

        // Remove empty legacy directories if force
        if ($force) {
            $heroDir = $publicDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'foto-slide-hero';
            if (is_dir($heroDir) && count(scandir($heroDir) ?: []) <= 2) {
                @rmdir($heroDir);
                $this->info("Removed empty directory: {$heroDir}");
            }
        }

        $this->line('');
        $this->info(sprintf(
            'Summary: %d legacy files detected on disk (%s). Purged: %d.',
            $foundCount,
            $this->formatBytes($totalBytes),
            $purgedCount
        ));

        // Integrity check: verify protected assets exist
        $missingProtected = 0;
        foreach ($this->protectedAssets as $protected) {
            $protPath = $publicDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $protected);
            if (!file_exists($protPath)) {
                $this->warn("WARNING: Protected asset missing: {$protected}");
                $missingProtected++;
            }
        }

        if ($missingProtected === 0) {
            $this->info('Integrity Check: All active assets are verified safe and present.');
        }

        if (!$force && $foundCount > 0) {
            $this->comment('To permanently purge these files, run:');
            $this->line('  php artisan server:purge-legacy-assets --force');
        }

        return 0;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
