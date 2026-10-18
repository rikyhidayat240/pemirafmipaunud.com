<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CoarsenVoteTimestamps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'votes:coarsen-timestamps
        {--force : Terapkan perubahan nyata ke database (default adalah dry-run)}
        {--confirm-production : Konfirmasi eksplisit tambahan agar diizinkan berjalan di APP_ENV=production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kurangi presisi waktu (created_at & updated_at) pada tabel surat_suara menjadi presisi jam (HH:00:00)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $confirmProduction = (bool) $this->option('confirm-production');

        if ($force && app()->environment('production') && ! $confirmProduction) {
            if (! $this->input->isInteractive() || ! $this->confirm('PERINGATAN: Anda berada di APP_ENV=production. Yakin ingin mengubah timestamp surat_suara?', false)) {
                $this->error('Ditolak: Eksekusi --force pada APP_ENV=production wajib menyertakan opsi --confirm-production atau konfirmasi interaktif.');
                return self::FAILURE;
            }
        }

        $rows = DB::table('surat_suara')
            ->select('id', 'nim', 'id_kegiatan', 'created_at', 'updated_at')
            ->get();

        $updates = [];

        foreach ($rows as $row) {
            $coarsenedCreatedAt = $row->created_at
                ? Carbon::parse($row->created_at)->startOfHour()->format('Y-m-d H:i:s')
                : null;
            $coarsenedUpdatedAt = $row->updated_at
                ? Carbon::parse($row->updated_at)->startOfHour()->format('Y-m-d H:i:s')
                : null;

            if ($coarsenedCreatedAt !== $row->created_at || $coarsenedUpdatedAt !== $row->updated_at) {
                $updates[] = [
                    'id'          => $row->id ?? null,
                    'nim'         => $row->nim,
                    'id_kegiatan' => $row->id_kegiatan,
                    'created_at'  => $coarsenedCreatedAt,
                    'updated_at'  => $coarsenedUpdatedAt,
                ];
            }
        }

        $affectedCount = count($updates);
        $totalCount = $rows->count();

        if (! $force) {
            $this->info(sprintf(
                '[DRY-RUN] Tidak ada perubahan yang disimpan. %d dari %d baris surat_suara akan dibulatkan ke presisi jam (HH:00:00). Gunakan --force untuk menerapkan.',
                $affectedCount,
                $totalCount
            ));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $item) {
                $query = DB::table('surat_suara');
                if ($item['id'] !== null) {
                    $query->where('id', $item['id']);
                } else {
                    $query->where('nim', $item['nim'])
                        ->where('id_kegiatan', $item['id_kegiatan']);
                }

                $query->update([
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ]);
            }
        });

        $this->info(sprintf(
            '[APPLIED] Berhasil membulatkan timestamp pada %d dari %d baris surat_suara ke presisi jam (HH:00:00).',
            $affectedCount,
            $totalCount
        ));

        return self::SUCCESS;
    }
}
