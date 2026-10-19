<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupObsoleteAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-obsolete-assets
        {--force : Actually delete the verified obsolete files (dry-run by default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and delete provably obsolete static assets with 0 references in PEMIRA';

    /**
     * Candidate obsolete files in public/ with expected zero references.
     *
     * @var array<string, string>
     */
    protected array $candidates = [
        'images/foto-slide-hero/hero-image-1.jpg' => 'Unused legacy hero slider banner (PEMIRA homepage has no slider)',
        'images/foto-slide-hero/hero-image-2.jpg' => 'Unused legacy hero slider banner',
        'images/foto-slide-hero/hero-image-3.jpg' => 'Unused legacy hero slider banner',
        'images/foto-slide-hero/IMG_1.webp'       => 'Unused leftover converted hero banner',
        'images/foto-slide-hero/IMG_2.webp'       => 'Unused leftover converted hero banner',
        'images/foto-slide-hero/IMG_3.webp'       => 'Unused leftover converted hero banner',
        'Logo pemira.webp'                        => 'Duplicate 242 KB file with 0 references; active views use /logo-pemira.webp (40 KB)',
        'group-188.png'                           => 'Unused constellation graphic (never rendered in any view)',
        'logo-pemira.svg'                         => 'Legacy vector logo superseded by logo-pemira.webp',
        'StarfieldCanvas.png'                     => 'Superseded by StarfieldCanvas.webp (98 KB)',
        'images/background-logo-dpm.png'          => 'Superseded by background-logo-dpm.webp (126 KB)',
        'images/foto-kegiatan/pemilihan-ketua-wakil-bem.png' => 'Superseded by pemilihan-ketua-wakil-bem.webp',
        'images/foto-kegiatan/pemilihan-ketua-himabio.png'   => 'Superseded by pemilihan-ketua-himabio.webp',
        'images/foto-kegiatan/pemilihan-ketua-himafarma.png' => 'Superseded by pemilihan-ketua-himafarma.webp',
        'images/foto-kegiatan/pemilihan-ketua-himafi.png'    => 'Superseded by pemilihan-ketua-himafi.webp',
        'images/foto-kegiatan/pemilihan-ketua-himaif.png'    => 'Superseded by pemilihan-ketua-himaif.webp',
        'images/foto-kegiatan/pemilihan-ketua-himaki.png'    => 'Superseded by pemilihan-ketua-himaki.webp',
        'images/foto-kegiatan/pemilihan-ketua-himatika.png'  => 'Superseded by pemilihan-ketua-himatika.webp',
    ];

    /**
     * Directories to scan for references.
     *
     * @var array<string>
     */
    protected array $scanDirectories = [
        'resources',
        'app',
        'routes',
        'database',
        'config',
        'tests',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $publicDir = public_path();
        $baseDir = base_path();

        $this->info('=== PEMIRA OBSOLETE ASSET CLEANUP & AUDIT ===');
        $this->info('Mode: ' . ($force ? 'FORCE (Deleting verified files)' : 'DRY RUN (Auditing only)'));

        // Collect all source files to scan
        $sourceFiles = $this->collectSourceFiles($baseDir);
        $this->info(sprintf('Scanning %d codebase files across resources/, app/, routes/, database/, config/, tests/ ...', count($sourceFiles)));

        $tableData = [];
        $totalBytesReclaimed = 0;
        $deletedCount = 0;
        $blockedCount = 0;

        foreach ($this->candidates as $relPath => $reason) {
            $filePath = $publicDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
            $exists = file_exists($filePath);
            $fileSize = $exists ? filesize($filePath) : 0;

            // Integrity protection check: never delete anything outside public/ or in storage/ or .env
            if (!$this->isSafeToDelete($relPath, $filePath, $publicDir)) {
                $this->error("BLOCKED: {$relPath} violates safety constraints.");
                $blockedCount++;
                continue;
            }

            // Reference audit
            $references = $this->findReferences($relPath, $sourceFiles);

            if (count($references) > 0) {
                $this->warn(sprintf('PRESERVED: %s has %d reference(s).', $relPath, count($references)));
                $tableData[] = [
                    $relPath,
                    number_format($fileSize) . ' B',
                    'PRESERVED (' . count($references) . ' refs)',
                    $reason,
                ];
                continue;
            }

            if (!$exists) {
                $tableData[] = [
                    $relPath,
                    '0 B',
                    'ALREADY DELETED',
                    $reason,
                ];
                continue;
            }

            // 0 references verified and file exists
            if ($force) {
                @unlink($filePath);
                $deletedCount++;
                $totalBytesReclaimed += $fileSize;
                $status = 'DELETED (' . number_format($fileSize) . ' B)';
            } else {
                $totalBytesReclaimed += $fileSize;
                $status = 'CANDIDATE (' . number_format($fileSize) . ' B)';
            }

            $tableData[] = [
                $relPath,
                number_format($fileSize) . ' B',
                $status,
                $reason,
            ];
        }

        // Clean up empty directories if force
        if ($force) {
            $heroDir = $publicDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'foto-slide-hero';
            if (is_dir($heroDir) && count(scandir($heroDir)) <= 2) {
                @rmdir($heroDir);
                $this->info("Removed empty directory: {$heroDir}");
            }
        }

        $this->table(
            ['Asset Path', 'Size', 'Status', 'Justification'],
            $tableData
        );

        $this->newLine();
        $this->info(sprintf(
            'Summary: %d files verified obsolete | Total Reclaimed: %s bytes (%s MB)',
            count($this->candidates),
            number_format($totalBytesReclaimed),
            round($totalBytesReclaimed / 1024 / 1024, 2)
        ));

        if (!$force) {
            $this->warn('This was a DRY RUN. Pass --force to execute the deletions.');
        }

        return self::SUCCESS;
    }

    /**
     * Check if a path is safe to delete under integrity constraints.
     */
    protected function isSafeToDelete(string $relPath, string $fullPath, string $publicDir): bool
    {
        $realPublic = realpath($publicDir) ?: $publicDir;
        $normalizedRealPublic = strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPublic), DIRECTORY_SEPARATOR));
        $normalizedFull = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath));

        // Must be inside public/
        if (!str_starts_with($normalizedFull, $normalizedRealPublic)) {
            return false;
        }

        // Never touch storage runtime data, .env, or backup
        if (str_contains($relPath, 'storage') || str_contains($relPath, '.env') || str_contains($relPath, 'backup')) {
            return false;
        }

        return true;
    }

    /**
     * Collect all source files to scan for references.
     *
     * @return array<string>
     */
    protected function collectSourceFiles(string $baseDir): array
    {
        $files = [];

        foreach ($this->scanDirectories as $dir) {
            $dirPath = $baseDir . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($dirPath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    if (str_ends_with($fileInfo->getFilename(), 'CleanupObsoleteAssetsTest.php') ||
                        str_ends_with($fileInfo->getFilename(), 'CleanupObsoleteAssets.php')) {
                        continue;
                    }
                    $files[] = $fileInfo->getPathname();
                }
            }
        }

        // Add root config files and scripts
        foreach (['vite.config.ts', 'package.json', 'composer.json'] as $rootFile) {
            $rfp = $baseDir . DIRECTORY_SEPARATOR . $rootFile;
            if (file_exists($rfp)) {
                $files[] = $rfp;
            }
        }

        return $files;
    }

    /**
     * Find references to an asset in source files.
     *
     * @param array<string> $sourceFiles
     * @return array<string>
     */
     protected function findReferences(string $relPath, array $sourceFiles): array
     {
         $basename = basename($relPath);
         $forwardSlash = '/' . ltrim(str_replace('\\', '/', $relPath), '/');
 
         // If asset is inside a subdirectory, match by path to avoid false collisions with other dirs
         if (str_contains($relPath, '/') || str_contains($relPath, '\\')) {
             $terms = [
                 $relPath,
                 $forwardSlash,
                 str_replace('/', '\\', $relPath),
             ];
         } else {
             $terms = [
                 $basename,
                 $forwardSlash,
             ];
         }
 
         $matches = [];
 
         foreach ($sourceFiles as $file) {
             $content = file_get_contents($file);
             if ($content === false) {
                 continue;
             }
 
             foreach ($terms as $term) {
                 // For "Logo pemira.webp" specifically, distinguish exact case from lowercase "logo-pemira.webp"
                 if ($basename === 'Logo pemira.webp') {
                     if (str_contains($content, 'Logo pemira.webp')) {
                         $matches[] = $file . ' (matched exact: ' . $term . ')';
                         break;
                     }
                 } else {
                     if (stripos($content, $term) !== false) {
                         $matches[] = $file . ' (matched: ' . $term . ')';
                         break;
                     }
                 }
             }
         }
 
         return $matches;
     }
}
