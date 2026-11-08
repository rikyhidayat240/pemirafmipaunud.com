<?php

namespace App\Console\Commands;

use App\Models\Kegiatan;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetDemoVotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'votes:reset-demo
        {--kegiatan= : Daftar ID kegiatan dipisahkan koma (default: kegiatan aktif terbaru BEM & tiap HIMA di bilik suara)}
        {--all-events : Periksa dan proses seluruh kegiatan di database}
        {--before= : Cutoff waktu demo "YYYY-MM-DD HH:MM:SS" (default: awal jam waktu_mulai tiap kegiatan)}
        {--nim= : Batasi hanya pada daftar NIM tertentu (dipisahkan koma)}
        {--reset-tally-if-no-real-votes : Reset jumlah_suara kandidat ke 0 HANYA jika belum ada suara asli pada kegiatan tersebut}
        {--force : Terapkan perubahan nyata ke database (default adalah dry-run)}
        {--confirm-production : Konfirmasi eksplisit tambahan agar diizinkan berjalan di APP_ENV=production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buka kembali hak pilih peserta demo pada kegiatan yang diedit ulang tanpa menyentuh suara pemilih asli';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $confirmProduction = (bool) $this->option('confirm-production');
        $resetEmptyTally = (bool) $this->option('reset-tally-if-no-real-votes');
        $allEventsFlag = (bool) $this->option('all-events');

        $explicitCutoff = null;
        if ($this->option('before')) {
            try {
                $explicitCutoff = Carbon::parse((string) $this->option('before'));
            } catch (\Throwable $e) {
                $this->error('Format --before tidak valid. Gunakan format "YYYY-MM-DD HH:MM:SS", misal --before="2026-11-01 08:00:00".');
                return self::FAILURE;
            }
        }

        if ($force && app()->environment('production') && ! $confirmProduction) {
            if (! $this->input->isInteractive() || ! $this->confirm('PERINGATAN: Anda berada di APP_ENV=production. Lanjutkan membuka kunci surat_suara demo yang cocok?', false)) {
                $this->error('Ditolak: Eksekusi --force pada APP_ENV=production wajib menyertakan opsi --confirm-production atau konfirmasi interaktif.');
                return self::FAILURE;
            }
        }

        $nimFilter = [];
        if ($this->option('nim')) {
            $nimFilter = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('nim')))));
        }

        // Identifikasi kegiatan aktif yang dipilih bilik suara (/vote) saat ini:
        // 1 kegiatan fakultas (BEM) terbaru + 1 kegiatan terbaru per Program Studi (HIMA)
        $activeBem = Kegiatan::query()
            ->where('waktu_selesai', '>', now())
            ->where('ruang_lingkup', 'fakultas')
            ->orderByDesc('waktu_mulai')
            ->orderByDesc('id')
            ->first();

        $activeHimas = Kegiatan::query()
            ->where('waktu_selesai', '>', now())
            ->where('ruang_lingkup', 'program studi')
            ->orderByDesc('waktu_mulai')
            ->orderByDesc('id')
            ->get()
            ->unique('id_program_studi')
            ->values();

        $activeBoothIds = [];
        if ($activeBem) {
            $activeBoothIds[] = (int) $activeBem->id;
        }
        foreach ($activeHimas as $hima) {
            $activeBoothIds[] = (int) $hima->id;
        }

        $allKegiatan = Kegiatan::with('programStudi')
            ->orderByDesc('tahun')
            ->orderByDesc('waktu_mulai')
            ->orderByDesc('id')
            ->get();

        if ($allKegiatan->isEmpty()) {
            $this->warn('Tidak ada data kegiatan di database.');
            return self::SUCCESS;
        }

        $targetIds = [];
        if ($this->option('kegiatan')) {
            $targetIds = array_values(array_filter(array_map('intval', explode(',', (string) $this->option('kegiatan')))));
        } elseif ($allEventsFlag) {
            $targetIds = $allKegiatan->pluck('id')->map(fn ($id) => (int) $id)->all();
        } else {
            $targetIds = $activeBoothIds;
        }

        $overviewRows = [];
        $planByKegiatan = [];

        foreach ($allKegiatan as $kegiatan) {
            $isBoothActive = in_array((int) $kegiatan->id, $activeBoothIds, true);
            $isTargeted = in_array((int) $kegiatan->id, $targetIds, true);

            $cutoff = $explicitCutoff
                ? $explicitCutoff->copy()
                : ($kegiatan->waktu_mulai ? $kegiatan->waktu_mulai->copy()->startOfHour() : now()->startOfHour());

            $votedRows = DB::table('surat_suara')
                ->leftJoin('mahasiswa', 'mahasiswa.nim', '=', 'surat_suara.nim')
                ->where('surat_suara.id_kegiatan', $kegiatan->id)
                ->where('surat_suara.has_vote', true)
                ->select([
                    'surat_suara.id',
                    'surat_suara.nim',
                    'surat_suara.has_vote',
                    'surat_suara.ttd',
                    'surat_suara.created_at',
                    'surat_suara.updated_at',
                    'mahasiswa.nama as nama_mahasiswa',
                ])
                ->orderBy('surat_suara.updated_at')
                ->orderBy('surat_suara.nim')
                ->get();

            $demoRows = [];
            $realRows = [];
            $hourlyHistogram = [];

            foreach ($votedRows as $row) {
                $rawTime = $row->updated_at ?? $row->created_at;
                $voteTime = $rawTime ? Carbon::parse($rawTime) : Carbon::now();
                $hourKey = $voteTime->copy()->startOfHour()->format('Y-m-d H:00:00');
                $hourlyHistogram[$hourKey] = ($hourlyHistogram[$hourKey] ?? 0) + 1;

                $isBeforeCutoff = $voteTime->lt($cutoff);
                $matchesNim = empty($nimFilter) || in_array((string) $row->nim, $nimFilter, true);

                if ($isBeforeCutoff && $matchesNim) {
                    $demoRows[] = $row;
                } else {
                    $realRows[] = $row;
                }
            }

            $tallySum = (int) DB::table('kandidat')
                ->where('id_kegiatan', $kegiatan->id)
                ->sum('jumlah_suara');

            $scopeLabel = $kegiatan->ruang_lingkup === 'fakultas'
                ? 'Fakultas (BEM)'
                : 'Prodi: ' . ($kegiatan->programStudi?->nama ?? ('#' . $kegiatan->id_program_studi));

            $statusLabel = $isBoothActive ? '[AKTIF BILIK SUARA]' : '[NONAKTIF/LAMA]';
            if ($isTargeted) {
                $statusLabel .= ' *TARGET*';
            }

            $overviewRows[] = [
                $kegiatan->id,
                $kegiatan->nama,
                $scopeLabel,
                $statusLabel,
                $kegiatan->waktu_mulai?->format('Y-m-d H:i') ?? '-',
                $cutoff->format('Y-m-d H:i:s'),
                count($demoRows),
                count($realRows),
                $tallySum,
            ];

            if ($isTargeted) {
                $planByKegiatan[$kegiatan->id] = [
                    'kegiatan'  => $kegiatan,
                    'cutoff'    => $cutoff,
                    'demo_rows' => $demoRows,
                    'real_rows' => $realRows,
                    'tally_sum' => $tallySum,
                    'histogram' => $hourlyHistogram,
                ];
            }
        }

        $this->info('=== RINGKASAN AUDIT KEGIATAN & SURAT SUARA (PEMIRA) ===');
        $this->table(
            ['ID', 'Nama Kegiatan', 'Lingkup', 'Status', 'Waktu Mulai', 'Cutoff Demo (<)', 'Suara Demo', 'Suara Asli', 'Tally Kandidat'],
            $overviewRows
        );

        if (empty($planByKegiatan)) {
            $this->warn('Tidak ada kegiatan target yang aktif saat ini. Gunakan --kegiatan=<ID> atau --all-events bila ingin memilih kegiatan secara spesifik.');
            return self::SUCCESS;
        }

        $totalDemoToReset = 0;

        foreach ($planByKegiatan as $kegiatanId => $plan) {
            /** @var Kegiatan $kegiatan */
            $kegiatan = $plan['kegiatan'];
            /** @var Carbon $cutoff */
            $cutoff = $plan['cutoff'];
            $demoCount = count($plan['demo_rows']);
            $realCount = count($plan['real_rows']);
            $tallySum = $plan['tally_sum'];
            $totalDemoToReset += $demoCount;

            $this->line('');
            $this->info(sprintf('--- Detail Kegiatan #%d: %s (Cutoff Demo < %s) ---', $kegiatanId, $kegiatan->nama, $cutoff->format('Y-m-d H:i:s')));

            if (! empty($plan['histogram'])) {
                $this->line('Distribusi jam suara masuk:');
                foreach ($plan['histogram'] as $hour => $count) {
                    $bucketTag = Carbon::parse($hour)->lt($cutoff) ? 'DEMO (< cutoff)' : 'ASLI (>= cutoff)';
                    $this->line(sprintf('  - %s : %d pemilih [%s]', $hour, $count, $bucketTag));
                }
            }

            if ($demoCount > 0) {
                $voterPreview = array_map(fn ($r) => [
                    $r->nim,
                    $r->nama_mahasiswa ?? '-',
                    (string) ($r->updated_at ?? $r->created_at),
                ], $plan['demo_rows']);
                $this->table(['NIM Peserta Demo', 'Nama', 'Waktu Vote Demo'], $voterPreview);
            } else {
                $this->line('Tidak ditemukan baris surat_suara demo (< cutoff) pada kegiatan ini.');
            }

            if ($tallySum > $realCount && ($demoCount > 0 || ! $resetEmptyTally)) {
                $delta = $tallySum - $realCount;
                $this->warn(sprintf(
                    '[CATATAN INTEGRITAS TALLY] Kegiatan #%d memiliki total jumlah_suara kandidat (%d) lebih besar %d suara dibanding suara asli sejak cutoff (%d). Karena asas kerahasiaan suara (VOT-01), surat_suara tidak menyimpan pilihan kandidat sehingga jumlah_suara kandidat TIDAK dikurangi otomatis saat sudah ada suara asli.',
                    $kegiatanId,
                    $tallySum,
                    $delta,
                    $realCount
                ));
            }
        }

        if (! $force) {
            $this->line('');
            $this->info(sprintf(
                '[DRY-RUN] Tidak ada perubahan yang disimpan. Total %d baris surat_suara peserta demo siap dibuka kembali (0 suara asli tersentuh). Tambahkan --force (dan --confirm-production di production) untuk menerapkan.',
                $totalDemoToReset
            ));
            return self::SUCCESS;
        }

        $appliedTotal = 0;
        $candidateTtdsToPurge = [];
        $nowCoarsened = Carbon::now()->startOfHour()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($planByKegiatan, $resetEmptyTally, $nowCoarsened, &$appliedTotal, &$candidateTtdsToPurge) {
            foreach ($planByKegiatan as $kegiatanId => $plan) {
                /** @var Kegiatan $kegiatan */
                $kegiatan = $plan['kegiatan'];
                /** @var Carbon $cutoff */
                $cutoff = $plan['cutoff'];
                $demoRows = $plan['demo_rows'];
                $realCount = count($plan['real_rows']);
                $demoIds = array_map(fn ($r) => $r->id, $demoRows);

                foreach ($demoRows as $r) {
                    if (! empty($r->ttd)) {
                        $candidateTtdsToPurge[$r->ttd] = true;
                    }
                }

                $resetCount = 0;
                if (! empty($demoIds)) {
                    $resetCount = DB::table('surat_suara')
                        ->where('id_kegiatan', $kegiatanId)
                        ->whereIn('id', $demoIds)
                        ->where('has_vote', true)
                        ->update([
                            'has_vote'   => false,
                            'ttd'        => null,
                            'updated_at' => $nowCoarsened,
                        ]);
                }

                $tallyWasReset = false;
                if ($resetEmptyTally) {
                    if ($realCount === 0) {
                        DB::table('kandidat')
                            ->where('id_kegiatan', $kegiatanId)
                            ->update(['jumlah_suara' => 0]);
                        $tallyWasReset = true;
                    } else {
                        $this->warn(sprintf(
                            '[PROTECTED] Menolak mereset jumlah_suara kandidat pada Kegiatan #%d karena sudah ada %d suara pemilih asli.',
                            $kegiatanId,
                            $realCount
                        ));
                    }
                }

                if ($resetCount > 0 || $tallyWasReset) {
                    AuditLogService::log('kegiatan.reset_demo', 'kegiatan', (string) $kegiatanId, [
                        'nama_kegiatan'   => $kegiatan->nama,
                        'cutoff_sebelum'  => $cutoff->format('Y-m-d H:i:s'),
                        'jumlah_direset'  => $resetCount,
                        'suara_asli_aman' => $realCount,
                        'tally_direset'   => $tallyWasReset,
                    ]);
                }

                $appliedTotal += $resetCount;
            }
        });

        // Hapus berkas TTD demo lama HANYA jika tidak lagi dipakai oleh surat_suara aktif lain (mis. pasangan BEM+HIMA)
        foreach (array_keys($candidateTtdsToPurge) as $ttdPath) {
            $stillReferenced = DB::table('surat_suara')
                ->where('ttd', $ttdPath)
                ->where('has_vote', true)
                ->exists();

            if (! $stillReferenced && Storage::disk('public')->exists($ttdPath)) {
                Storage::disk('public')->delete($ttdPath);
            }
        }

        $this->line('');
        $this->info(sprintf(
            '[APPLIED] Berhasil membuka kembali %d baris surat_suara peserta demo pada PEMIRA. Seluruh suara pemilih asli tetap utuh.',
            $appliedTotal
        ));

        return self::SUCCESS;
    }
}
