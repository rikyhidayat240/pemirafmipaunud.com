<?php

namespace App\Console\Commands;

use App\Services\ImageUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OptimizeUploadedImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize-uploads
                            {--force : Apply conversions, create backups, and update database records}
                            {--backup : Preserve original files in a backup folder (active by default with --force)}
                            {--disk=public : Storage disk containing the images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan uploaded images in database (kegiatan, kandidat, mahasiswa), resize to presets, and convert to WebP';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force    = (bool) $this->option('force');
        $diskName = (string) ($this->option('disk') ?: 'public');
        $disk     = Storage::disk($diskName);

        $this->line($force ? '<info>[MODE: FORCE EXECUTION]</info>' : '<comment>[MODE: DRY-RUN]</comment>');

        // Targets: Model label, DB query, preset name, ID column, Path column, updater callback
        $items = $this->collectTargetItems();

        if (empty($items)) {
            $this->info('No image records found to process.');
            if (!$force) {
                $this->line('[DRY-RUN] No files were modified and no database rows were altered. Run with --force to execute.');
            }
            return self::SUCCESS;
        }

        $timestamp = now()->format('Ymd_His');
        $backupDir = storage_path('app/image-backups/' . $timestamp);

        if ($force) {
            if (!file_exists($backupDir)) {
                @mkdir($backupDir, 0755, true);
            }
        }

        $tableRows        = [];
        $scannedCount     = 0;
        $toConvertCount   = 0;
        $convertedCount   = 0;
        $alreadyWebpCount = 0;
        $skippedCount     = 0;
        $totalOrigBytes   = 0;
        $totalNewBytes    = 0;
        $convertedFiles   = []; // map: oldPath => newPath

        foreach ($items as $item) {
            $scannedCount++;
            $modelName = $item['model'];
            $id        = $item['id'];
            $current   = $item['path'];
            $preset    = $item['preset'];
            $updater   = $item['updater'];

            if (empty($current)) {
                $skippedCount++;
                continue;
            }

            // 1. Skip if already WebP
            if (str_ends_with(strtolower($current), '.webp')) {
                $alreadyWebpCount++;
                $tableRows[] = [
                    $modelName,
                    $id,
                    $current,
                    $disk->exists($current) ? $this->formatBytes($disk->size($current)) : 'N/A',
                    $current,
                    '-',
                    '-',
                    'Already WebP',
                ];
                continue;
            }

            // 2. Check physical existence on disk
            if (!$disk->exists($current)) {
                $skippedCount++;
                $tableRows[] = [
                    $modelName,
                    $id,
                    $current,
                    'N/A',
                    '-',
                    '-',
                    '-',
                    'Missing on disk',
                ];
                continue;
            }

            $origSize = $disk->size($current);
            $info = pathinfo($current);
            $newFilename = $info['filename'] . '.webp';
            $newPath = ($info['dirname'] && $info['dirname'] !== '.' ? $info['dirname'] . '/' : '') . $newFilename;

            // Check if already processed in this batch (duplicate paths)
            if (isset($convertedFiles[$current])) {
                $assignedNewPath = $convertedFiles[$current];
                if ($force) {
                    $updater($assignedNewPath);
                }
                $tableRows[] = [
                    $modelName,
                    $id,
                    $current,
                    $this->formatBytes($origSize),
                    $assignedNewPath,
                    'Reused',
                    '-',
                    $force ? 'Updated DB (Reused WebP)' : 'Dry-Run (Reused WebP)',
                ];
                continue;
            }

            // 3. Process image with GD to WebP
            $fullPath = $disk->path($current);
            $webpData = ImageUploadService::processImageToWebp(
                $fullPath,
                $preset['maxWidth'],
                $preset['maxHeight'],
                $preset['quality']
            );

            if ($webpData === null) {
                $skippedCount++;
                $tableRows[] = [
                    $modelName,
                    $id,
                    $current,
                    $this->formatBytes($origSize),
                    '-',
                    '-',
                    '-',
                    'Cannot decode GD',
                ];
                continue;
            }

            $newSize = strlen($webpData);
            $savings = max(0, $origSize - $newSize);
            $totalOrigBytes += $origSize;
            $totalNewBytes  += $newSize;

            if (!$force) {
                $toConvertCount++;
                $tableRows[] = [
                    $modelName,
                    $id,
                    $current,
                    $this->formatBytes($origSize),
                    $newPath,
                    $this->formatBytes($newSize),
                    $this->formatBytes($savings),
                    'Ready to convert',
                ];
            } else {
                // FORCE: Backup original file
                $backupFile = $backupDir . '/' . $current;
                $backupFileDir = dirname($backupFile);
                if (!file_exists($backupFileDir)) {
                    @mkdir($backupFileDir, 0755, true);
                }
                @file_put_contents($backupFile, $disk->get($current));

                // Save converted WebP file
                $disk->put($newPath, $webpData);

                // Update Database
                $updater($newPath);

                // Remove original file on disk if path differs
                if ($current !== $newPath && $disk->exists($current)) {
                    $disk->delete($current);
                }

                $convertedFiles[$current] = $newPath;
                $convertedCount++;

                $tableRows[] = [
                    $modelName,
                    $id,
                    $current,
                    $this->formatBytes($origSize),
                    $newPath,
                    $this->formatBytes($newSize),
                    $this->formatBytes($savings),
                    'Converted & Saved',
                ];
            }
        }

        $this->table(
            ['Model', 'ID', 'Current Path', 'Current Size', 'Target Path', 'WebP Size', 'Savings', 'Status'],
            $tableRows
        );

        $totalSavings = max(0, $totalOrigBytes - $totalNewBytes);
        $percentSavings = $totalOrigBytes > 0 ? round(($totalSavings / $totalOrigBytes) * 100, 1) : 0;

        $this->line('');
        if (!$force) {
            $this->info("Scanned: {$scannedCount} | To Convert: {$toConvertCount} | Already WebP: {$alreadyWebpCount} | Skipped/Missing: {$skippedCount}");
            $this->info("Projected savings: " . $this->formatBytes($totalSavings) . " ({$percentSavings}%)");
            $this->line('[DRY-RUN] No files were modified and no database rows were altered. Run with --force to execute.');
        } else {
            $this->info("Scanned: {$scannedCount} | Converted: {$convertedCount} | Already WebP: {$alreadyWebpCount} | Skipped/Missing: {$skippedCount}");
            $this->info("Originals backed up to: storage/app/image-backups/{$timestamp}/");
            $this->info("[APPLIED] Total space saved: " . $this->formatBytes($totalSavings) . " ({$percentSavings}%)");
        }

        return self::SUCCESS;
    }

    /**
     * Collect candidate, event, and student rows to inspect.
     *
     * @return array<int, array{model: string, id: string|int, path: string, preset: array, updater: \Closure}>
     */
    protected function collectTargetItems(): array
    {
        $items = [];

        // 1. Kegiatan (Event)
        try {
            $kegiatans = DB::table('kegiatan')->select('id', 'foto')->whereNotNull('foto')->get();
            foreach ($kegiatans as $kegiatan) {
                $items[] = [
                    'model'   => 'Kegiatan',
                    'id'      => $kegiatan->id,
                    'path'    => (string) $kegiatan->foto,
                    'preset'  => ImageUploadService::PRESETS['event'],
                    'updater' => fn (string $newPath) => DB::table('kegiatan')->where('id', $kegiatan->id)->update(['foto' => $newPath]),
                ];
            }
        } catch (\Throwable) {
            // Table may not exist in minimal testing
        }

        // 2. Kandidat (Candidate)
        try {
            $kandidats = DB::table('kandidat')->select('id', 'foto')->whereNotNull('foto')->get();
            foreach ($kandidats as $kandidat) {
                $items[] = [
                    'model'   => 'Kandidat',
                    'id'      => $kandidat->id,
                    'path'    => (string) $kandidat->foto,
                    'preset'  => ImageUploadService::PRESETS['candidate'],
                    'updater' => fn (string $newPath) => DB::table('kandidat')->where('id', $kandidat->id)->update(['foto' => $newPath]),
                ];
            }
        } catch (\Throwable) {
            // Table may not exist in minimal testing
        }

        // 3. Mahasiswa / User (Student Avatar)
        try {
            $users = DB::table('mahasiswa')->select('nim', 'avatar')->whereNotNull('avatar')->get();
            foreach ($users as $user) {
                $items[] = [
                    'model'   => 'Mahasiswa',
                    'id'      => $user->nim,
                    'path'    => (string) $user->avatar,
                    'preset'  => ImageUploadService::PRESETS['student'],
                    'updater' => fn (string $newPath) => DB::table('mahasiswa')->where('nim', $user->nim)->update(['avatar' => $newPath]),
                ];
            }
        } catch (\Throwable) {
            // Table may not exist in minimal testing
        }

        return $items;
    }

    /**
     * Format byte values to readable units.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }
}
